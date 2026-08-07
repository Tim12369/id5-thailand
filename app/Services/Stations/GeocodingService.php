<?php

namespace App\Services\Stations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Silently resolves an office's coordinates from its address via Google's
 * Geocoding API, so admins never have to know or enter latitude/longitude
 * themselves -- they just type the address, and the map pin follows.
 *
 * Failure is never fatal: if the address can't be geocoded (typo, API quota,
 * no key configured), this returns null and the caller simply leaves
 * lat/lng unset -- the office still saves, still lists, and "Get Directions"
 * still works (it links by address text, not by coordinates). We never
 * fabricate a coordinate.
 */
class GeocodingService
{
    public function geocode(string $address): ?array
    {
        $apiKey = config('services.google_maps.api_key');
        $address = trim($address);

        if (! $apiKey || $address === '') {
            return null;
        }

        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'region' => 'th',
                'key' => $apiKey,
            ]);

            $body = $response->json();

            if (($body['status'] ?? null) !== 'OK') {
                return null;
            }

            $location = $body['results'][0]['geometry']['location'] ?? null;

            if (! $location) {
                return null;
            }

            return ['lat' => (float) $location['lat'], 'lng' => (float) $location['lng']];
        } catch (\Throwable $e) {
            Log::warning('Geocoding request failed', ['address' => $address, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
