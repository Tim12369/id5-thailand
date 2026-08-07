<?php

namespace App\Http\Controllers\Internal\Website;

use App\Models\Station;
use App\Http\Controllers\Controller;
use App\Services\Stations\GeocodingService;
use App\Services\Stations\StationImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Manages the public-facing office directory (Station): name, address,
 * contact info, and visibility. Deliberately does not expose ops_status/
 * active_officers/online_cameras/active_incidents -- those belong to Command
 * Center's operational domain.
 *
 * By design, admins only ever type what they actually know about a real
 * office -- name, address, phone, email, website, region, province, and
 * optionally a Google Maps link. There is no coordinate entry: the address is
 * silently geocoded on save (see GeocodingService) so the public map still
 * gets a pin, without ever asking an admin to find or enter latitude/
 * longitude. "Get Directions" on the public site uses the admin-supplied
 * Google Maps link when one is set (Station::getGoogleMapsUrlAttribute),
 * otherwise falls back to a directions link built from the address text --
 * either way it still works correctly even on an address that can't be
 * geocoded.
 *
 * Stations are real government offices, so deletion is guarded: a station
 * with linked Command Center data (users, cameras, alerts, incidents) can
 * only be turned off (is_active), never hard-deleted, since that data is
 * keyed to the station row and would be orphaned. A station with no such
 * dependents -- e.g. one added by mistake -- can be deleted outright (see
 * destroy()).
 */
class ContactController extends Controller
{
    public function __construct(private GeocodingService $geocoder) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('website.view'), 403);

        $stations = Station::withCount(['users', 'cameras', 'alerts', 'incidents'])
            ->orderBy('sort_order')->get()
            ->map(fn (Station $station) => [
                'id' => $station->id,
                'code' => $station->code,
                'name_th' => $station->name_th,
                'name_en' => $station->name_en,
                'phone' => $station->phone,
                'email' => $station->email,
                'province' => $station->province,
                'region' => $station->region,
                'is_active' => $station->is_active,
                'has_website' => filled($station->website),
                'has_pin' => $station->lat !== null && $station->lng !== null,
                'canDelete' => $station->users_count + $station->cameras_count + $station->alerts_count + $station->incidents_count === 0,
                'editUrl' => route('internal.website-contact.edit', $station),
                'deleteUrl' => route('internal.website-contact.destroy', $station),
            ]);

        return view('internal.website.contact.index', compact('stations'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        return view('internal.website.contact.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        $data = $this->validated($request);
        // name_en has no DB default and isn't nullable -- an office known
        // only by its Thai name still needs something stored there.
        $data['name_en'] = $data['name_en'] ?? $data['name_th'];
        $data['is_active'] = $request->boolean('is_active', true);
        $data['code'] = $this->generateUniqueCode();
        $data['slug'] = Str::slug($data['code'] . '-' . $data['name_en']);

        $geocoded = $this->applyGeocoding($data);

        Station::create($data);

        return redirect()->route('internal.website-contact.index')
            ->with('success', 'Office added.' . ($geocoded ? '' : ' Note: we could not automatically place this address on the map — double-check it for typos. Everything else (contact info, directions, listing) still works.'));
    }

    public function edit(Request $request, Station $station): View
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        return view('internal.website.contact.edit', compact('station'));
    }

    public function update(Request $request, Station $station): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        $data = $this->validated($request);
        $data['name_en'] = $data['name_en'] ?? $data['name_th'];
        $data['is_active'] = $request->boolean('is_active');

        $geocoded = $this->applyGeocoding($data);

        $station->update($data);

        return redirect()->route('internal.website-contact.index')
            ->with('success', 'Contact information updated.' . ($geocoded ? '' : ' Note: we could not automatically place this address on the map — double-check it for typos. Everything else still works.'));
    }

    /**
     * Real offices with linked Command Center data (users, cameras, alerts,
     * incidents) are never hard-deleted -- that data is keyed to the station
     * row and would be orphaned. Deactivate those instead. A station with no
     * such dependents (e.g. one added by mistake) can be removed outright.
     */
    public function destroy(Request $request, Station $station): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        $dependents = $station->loadCount(['users', 'cameras', 'alerts', 'incidents']);
        $total = $dependents->users_count + $dependents->cameras_count + $dependents->alerts_count + $dependents->incidents_count;

        if ($total > 0) {
            return redirect()->route('internal.website-contact.index')
                ->with('error', "\"{$station->name_th}\" can't be deleted — it has linked Command Center data (users, cameras, alerts, or incidents). Turn it off instead (edit → uncheck \"Show on public directory\").");
        }

        $station->delete();

        return redirect()->route('internal.website-contact.index')->with('success', 'Office deleted.');
    }

    public function import(Request $request): View
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        return view('internal.website.contact.import');
    }

    public function importStore(Request $request, StationImportService $importer): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $importer->importFromFile($request->file('file')->getRealPath());

        $summary = "Created {$result['created']}, updated {$result['updated']}, skipped {$result['skipped']}.";

        return redirect()->route('internal.website-contact.index')
            ->with('success', $summary)
            ->with('importErrors', $result['errors']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'address_th' => ['required', 'string', 'max:1000'],
            'address_en' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'google_maps_url' => ['nullable', 'url', 'max:500'],
            'province' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        // A nullable field the request simply didn't submit is dropped from
        // validate()'s result entirely (not even present as null), so calling
        // code below can't safely do $data['name_en'] without this.
        return array_merge(['name_en' => null, 'address_en' => null, 'phone' => null, 'email' => null, 'website' => null, 'google_maps_url' => null], $data);
    }

    /**
     * Geocodes the office's address (preferring the English address for
     * Latin-script accuracy, falling back to Thai) and mutates $data with
     * lat/lng when successful. Returns whether geocoding succeeded.
     */
    private function applyGeocoding(array &$data): bool
    {
        $address = trim(($data['address_en'] ?? $data['address_th']) . ', ' . $data['province'] . ', Thailand', ', ');

        $coords = $this->geocoder->geocode($address);

        if (! $coords) {
            return false;
        }

        $data['lat'] = $coords['lat'];
        $data['lng'] = $coords['lng'];

        return true;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'OFF-' . Str::upper(Str::random(6));
        } while (Station::where('code', $code)->exists());

        return $code;
    }
}
