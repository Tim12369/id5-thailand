<?php

namespace App\Services\Stations;

use App\Models\Station;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Bulk-imports office directory data from a CSV of MANUALLY VERIFIED records
 * (compiled by an officer from official government sources -- there is no
 * public Immigration Bureau API to pull from automatically). Rows are
 * upserted by `code` so re-running an import is always safe: existing
 * offices are updated, new ones are inserted, nothing is ever duplicated,
 * and nothing is deleted (matches the "add + deactivate, no hard delete"
 * admin policy -- set is_active=0 in the CSV to hide an office instead).
 *
 * Shared by both `php artisan stations:import` and the admin Bulk Import
 * upload screen, so the two paths can never drift out of sync.
 */
class StationImportService
{
    public function __construct(private GeocodingService $geocoder) {}

    private const REQUIRED_COLUMNS = ['code', 'name_th'];

    private const OPTIONAL_COLUMNS = [
        'name_en', 'name_zh', 'province', 'region',
        'address_th', 'address_en', 'address_zh',
        'phone', 'email', 'website', 'facebook_url', 'line_url', 'google_maps_url',
        'parent_organization', 'online_services_available', 'last_verified_at',
        'lat', 'lng', 'is_active',
    ];

    /**
     * @return array{created: int, updated: int, skipped: int, errors: array<int, array{row: int, message: string}>}
     */
    public function importFromFile(string $path): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $result['errors'][] = ['row' => 0, 'message' => 'Could not open file.'];
            return $result;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $result['errors'][] = ['row' => 0, 'message' => 'File is empty or not a valid CSV.'];
            return $result;
        }

        $header = array_map(fn ($h) => Str::snake(trim((string) $h)), $header);
        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if (! empty($missing)) {
            fclose($handle);
            $result['errors'][] = ['row' => 0, 'message' => 'Missing required column(s): ' . implode(', ', $missing)];
            return $result;
        }

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // blank line
            }

            $data = array_combine($header, array_pad($row, count($header), null));

            try {
                $this->importRow($data, $rowNum, $result);
            } catch (\Throwable $e) {
                $result['errors'][] = ['row' => $rowNum, 'message' => $e->getMessage()];
                $result['skipped']++;
            }
        }

        fclose($handle);

        Log::info('Station bulk import completed', $result);

        return $result;
    }

    private function importRow(array $data, int $rowNum, array &$result): void
    {
        $code = trim((string) ($data['code'] ?? ''));
        $nameTh = trim((string) ($data['name_th'] ?? ''));

        if ($code === '' || $nameTh === '') {
            $result['errors'][] = ['row' => $rowNum, 'message' => 'Missing required code or name_th — skipped.'];
            $result['skipped']++;
            return;
        }

        $lat = $this->numericOrNull($data['lat'] ?? null);
        $lng = $this->numericOrNull($data['lng'] ?? null);

        if (($data['lat'] ?? '') !== '' && $lat === null) {
            $result['errors'][] = ['row' => $rowNum, 'message' => "Invalid latitude for code {$code} — coordinate skipped."];
        }
        if (($data['lng'] ?? '') !== '' && $lng === null) {
            $result['errors'][] = ['row' => $rowNum, 'message' => "Invalid longitude for code {$code} — coordinate skipped."];
        }

        $existing = Station::where('code', $code)->first();

        // province has no DB default -- a brand-new row must supply one, or
        // the insert fails outright. region does have a DB default, but
        // silently applying it to an office whose real region is unknown
        // would insert wrong data, so both are required for NEW rows. An
        // update to an existing office doesn't need them re-supplied since
        // the columns already have values.
        if (! $existing) {
            foreach (['province', 'region'] as $required) {
                if (trim((string) ($data[$required] ?? '')) === '') {
                    $result['errors'][] = ['row' => $rowNum, 'message' => "Missing required {$required} for new office {$code} — skipped."];
                    $result['skipped']++;
                    return;
                }
            }
        }

        $attributes = ['name_th' => $nameTh];
        foreach (self::OPTIONAL_COLUMNS as $col) {
            if (! array_key_exists($col, $data) || trim((string) $data[$col]) === '') {
                continue;
            }

            // An invalid coordinate is skipped (error already logged above)
            // rather than overwriting a previously-good value with null.
            if ($col === 'lat') {
                if ($lat !== null) $attributes['lat'] = $lat;
                continue;
            }
            if ($col === 'lng') {
                if ($lng !== null) $attributes['lng'] = $lng;
                continue;
            }

            $attributes[$col] = match ($col) {
                'is_active', 'online_services_available' => filter_var($data[$col], FILTER_VALIDATE_BOOLEAN),
                default => trim((string) $data[$col]),
            };
        }

        if (! $existing) {
            // name_en has no DB default and isn't nullable.
            $attributes['name_en'] = $attributes['name_en'] ?? $nameTh;
            $attributes['slug'] = Str::slug($code . '-' . $attributes['name_en']);
            $attributes['is_active'] = $attributes['is_active'] ?? true;
        }

        // Only geocode when there's no coordinate on file at all yet -- a CSV
        // that already supplies verified lat/lng, or a row whose station
        // already has coordinates, is left untouched (never overwrite a good
        // value with a best-effort guess, and avoid needless API calls).
        $needsCoords = ! isset($attributes['lat']) && ($existing === null || $existing->lat === null || $existing->lng === null);
        if ($needsCoords) {
            $address = $attributes['address_en'] ?? $attributes['address_th'] ?? $existing?->address_en ?? $existing?->address_th;
            $province = $attributes['province'] ?? $existing?->province;

            if ($address) {
                $coords = $this->geocoder->geocode(trim("{$address}, {$province}, Thailand", ', '));
                if ($coords) {
                    $attributes['lat'] = $coords['lat'];
                    $attributes['lng'] = $coords['lng'];
                } else {
                    $result['errors'][] = ['row' => $rowNum, 'message' => "Could not auto-locate address for code {$code} on the map — office saved without a map pin."];
                }
            }
        }

        Station::updateOrCreate(['code' => $code], $attributes);

        $existing ? $result['updated']++ : $result['created']++;
    }

    private function numericOrNull(mixed $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
