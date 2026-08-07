<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function index(): View
    {
        $stations = Station::active()->orderBy('sort_order')->get();

        $offices = $stations->map(fn (Station $station) => $this->toArray($station))->values();

        $provinces = $stations->pluck('province')->unique()->filter()->sort()->values();
        $regions = $stations->pluck('region')->unique()->filter()->sort()->values();

        return view('public.offices.index', compact('offices', 'provinces', 'regions'));
    }

    public function show(Station $station): View
    {
        abort_unless($station->is_active, 404);

        $nearby = Station::active()
            ->where('id', '!=', $station->id)
            ->get()
            ->map(function (Station $other) use ($station) {
                $other->distance_km = $station->getDistanceInKmTo((float) $other->lat, (float) $other->lng);
                return $other;
            })
            ->sortBy('distance_km')
            ->take(3)
            ->map(fn (Station $other) => $this->toArray($other, $other->distance_km))
            ->values();

        return view('public.offices.show', [
            'station' => $station,
            'office'  => $this->toArray($station),
            'nearby'  => $nearby,
        ]);
    }

    private function toArray(Station $station, ?float $distanceKm = null): array
    {
        return [
            'id'                  => $station->id,
            'code'                => $station->code,
            'slug'                => $station->slug,
            'name'                => $station->getLocalizedName(),
            'name_th'             => $station->name_th,
            'name_en'             => $station->name_en,
            'province'            => $station->province,
            'region'              => $station->region,
            'address'             => $station->getLocalizedAddress(),
            'phone'               => $station->phone,
            'email'               => $station->email,
            'website'             => $station->website,
            'facebook_url'        => $station->facebook_url,
            'line_url'            => $station->line_url,
            'google_maps_url'     => $station->google_maps_url,
            'image_url'           => $station->image_url,
            'parent_organization' => $station->parent_organization,
            'online_services'     => $station->online_services_available,
            'last_verified_at'    => $station->last_verified_at?->format('Y-m-d'),
            'lat'                 => (float) $station->lat,
            'lng'                 => (float) $station->lng,
            'opening_hours'       => $station->opening_hours,
            'today_hours'         => $station->today_hours,
            'url'                 => route('public.offices.show', $station),
            'distance_km'         => $distanceKm,
        ];
    }
}
