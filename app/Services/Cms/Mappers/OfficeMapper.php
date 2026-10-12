<?php

namespace App\Services\Cms\Mappers;

use App\Models\Station;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Immigration offices.
 *
 * `stations` is shared with the Command Center: the same row carries live
 * operational columns (`ops_status`, `active_officers`, `online_cameras`,
 * `active_incidents`) and is the target of `users.station_id`. This mapper
 * therefore writes only the public, descriptive fields and never touches the
 * operational ones — WordPress owns how an office is *described*, not how it is
 * *operating*.
 */
class OfficeMapper extends Mapper
{
    private const DAYS = [
        'mon' => 'hours_mon',
        'tue' => 'hours_tue',
        'wed' => 'hours_wed',
        'thu' => 'hours_thu',
        'fri' => 'hours_fri',
        'sat' => 'hours_sat',
        'sun' => 'hours_sun',
    ];

    public function upsert(array $payload): ?Model
    {
        $code = $this->field($payload, 'code');

        if (! $code) {
            return null;
        }

        $station = Station::firstOrNew(['wp_post_id' => $payload['wp_post_id']]);

        // Adopt the pre-existing row for this office code rather than creating a
        // duplicate, so the first import keeps every Command Center link and
        // every `users.station_id` reference intact.
        if (! $station->exists) {
            $existing = Station::where('code', $code)->first();

            if ($existing) {
                // Already owned by another WordPress item: only a published
                // replacement may take the code over (see mayWrite()).
                if (! $this->mayWrite($existing->wp_post_id, $payload)) {
                    return null;
                }

                $station = $existing;
            }
        }

        $station->fill([
            'code'                      => $code,
            'slug'                      => $this->uniqueSlug($payload, $station, $code),
            'name_th'                   => $payload['title'] ?: $code,
            'name_en'                   => $this->field($payload, 'name_en') ?? $payload['title'] ?? $code,
            'province'                  => $this->termName($payload, 'id5_province', $station->province ?? ''),
            'region'                    => $this->termName($payload, 'id5_region', $station->region ?? 'ภาคเหนือ'),
            'address_th'                => $this->field($payload, 'address_th'),
            'address_en'                => $this->field($payload, 'address_en'),
            'phone'                     => $this->field($payload, 'phone'),
            'email'                     => $this->field($payload, 'email'),
            'website'                   => $this->field($payload, 'website'),
            'facebook_url'              => $this->field($payload, 'facebook_url'),
            'line_url'                  => $this->field($payload, 'line_url'),
            'google_maps_url'           => $this->field($payload, 'google_maps_url'),
            'parent_organization'       => $this->field($payload, 'parent_organization'),
            'online_services_available' => $this->bool($payload, 'online_services_available'),
            'last_verified_at'          => $this->field($payload, 'last_verified_at'),
            'opening_hours'             => $this->openingHours($payload, $station),
            'is_active'                 => $this->bool($payload, 'is_active', true) && $this->isPublished($payload),
            'sort_order'                => $payload['menu_order'],
        ]);

        // Coordinates are only overwritten when an editor actually supplied
        // them, so clearing the fields never silently drops a geocoded pin.
        $lat = $this->field($payload, 'lat');
        $lng = $this->field($payload, 'lng');

        if (is_numeric($lat) && is_numeric($lng)) {
            $station->lat = (float) $lat;
            $station->lng = (float) $lng;
        }

        $station->image_path = $this->media->mirror($payload['image'] ?? null, $station->image_path);

        $station->wp_post_id    = $payload['wp_post_id'];
        $station->cms_synced_at = now();
        $station->save();

        return $station;
    }

    public function knownPostIds(string $postType): array
    {
        return Station::where('is_active', true)->whereNotNull('wp_post_id')->pluck('wp_post_id')->all();
    }

    /**
     * Never delete: a station row may still be referenced by staff accounts and
     * Command Center records. Hide it from the public site instead.
     */
    public function delete(int $wpPostId): void
    {
        Station::where('wp_post_id', $wpPostId)->update(['is_active' => false]);
    }

    private function uniqueSlug(array $payload, Station $station, string $code): string
    {
        $slug = $this->cleanSlug($payload['slug'] ?? null);

        if ($slug === null) {
            // No usable slug from WordPress (a Thai office name, or the post is
            // in the trash). An office that already has an address keeps it.
            if ($station->exists && filled($station->slug)) {
                return $station->slug;
            }

            // Otherwise build it the way the old admin and the CSV import do:
            // from the office code and English name.
            $slug = Str::slug($code.'-'.(string) $this->field($payload, 'name_en'))
                ?: 'office-'.$payload['wp_post_id'];
        }

        $taken = Station::where('slug', $slug)
            ->when($station->exists, fn ($q) => $q->where('id', '!=', $station->id))
            ->exists();

        return $taken ? $slug.'-'.$payload['wp_post_id'] : $slug;
    }

    private function termName(array $payload, string $taxonomy, string $fallback): string
    {
        $term = $this->term($payload, $taxonomy);

        return $term['name'] ?? $fallback;
    }

    /**
     * Turn the seven day fields back into the `{day: hours}` array the public
     * office pages already render. Days left blank mean closed and are omitted.
     */
    private function openingHours(array $payload, Station $station): ?array
    {
        $hours = [];

        foreach (self::DAYS as $day => $key) {
            $value = $this->field($payload, $key);

            if ($value) {
                $hours[$day] = (string) $value;
            }
        }

        // An editor who filled in nothing at all has not necessarily cleared the
        // schedule — keep what the site is already showing.
        return $hours ?: $station->opening_hours;
    }
}
