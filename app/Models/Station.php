<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Station extends Model
{
    protected $fillable = [
        'code', 'slug', 'name_th', 'name_en', 'name_zh',
        'province', 'region',
        'address_th', 'address_en', 'address_zh',
        'phone', 'email', 'website', 'facebook_url', 'line_url', 'google_maps_url',
        'image_path', 'parent_organization', 'online_services_available', 'last_verified_at',
        'lat', 'lng',
        'opening_hours', 'is_active', 'sort_order',
        'ops_status', 'active_officers', 'online_cameras', 'active_incidents',
    ];

    public const OPS_STATUSES = ['normal', 'warning', 'incident'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'is_active'                  => 'boolean',
            'online_services_available'  => 'boolean',
            'opening_hours'               => 'array',
            'lat'                         => 'float',
            'lng'                         => 'float',
            'last_verified_at'            => 'date',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function cameras(): HasMany
    {
        return $this->hasMany(CommandCenterCamera::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(CommandCenterAlert::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(CommandCenterIncident::class);
    }

    public function getOpsStatusColor(): string
    {
        return match ($this->ops_status) {
            'incident' => '#ef4444',
            'warning'  => '#f59e0b',
            default    => '#10b981',
        };
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeByProvince(Builder $q, string $province): Builder
    {
        return $q->where('province', $province);
    }

    public function scopeByRegion(Builder $q, string $region): Builder
    {
        return $q->where('region', $region);
    }

    public function scopeSearch(Builder $q, string $term): Builder
    {
        return $q->where(function (Builder $q) use ($term) {
            $like = "%{$term}%";
            $q->where('code', 'like', $like)
                ->orWhere('name_th', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('name_zh', 'like', $like)
                ->orWhere('address_th', 'like', $like)
                ->orWhere('address_en', 'like', $like)
                ->orWhere('address_zh', 'like', $like)
                ->orWhere('province', 'like', $like)
                ->orWhere('region', 'like', $like);
        });
    }

    public function getDistanceInKmTo(float $lat, float $lng): float
    {
        if ($this->lat === null || $this->lng === null) {
            return INF;
        }

        $earthRadiusKm = 6371;

        $latDelta = deg2rad($lat - $this->lat);
        $lngDelta = deg2rad($lng - $this->lng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($this->lat)) * cos(deg2rad($lat)) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    public function getLocalizedName(): string
    {
        return match (App::getLocale()) {
            'en' => $this->name_en ?: $this->name_th,
            'zh' => $this->name_zh ?: $this->name_th,
            default => $this->name_th,
        };
    }

    public function getLocalizedAddress(): ?string
    {
        return match (App::getLocale()) {
            'en' => $this->address_en ?: $this->address_th,
            'zh' => $this->address_zh ?: $this->address_th,
            default => $this->address_th,
        };
    }

    public function getTodayHoursAttribute(): ?string
    {
        if (!$this->opening_hours) return null;
        $day = strtolower(now()->format('D')); // mon, tue, etc.
        return $this->opening_hours[$day] ?? null;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    /**
     * "Get Directions" always routes by the office's address text -- not by
     * stored coordinates -- so it works correctly even for an office whose
     * address couldn't be auto-geocoded, and always reflects the real,
     * admin-entered address rather than a possibly-stale lat/lng. A manually
     * entered google_maps_url (e.g. from CSV import of verified data) wins
     * if present; otherwise this is built from the localized address, or the
     * office name + province as a last resort.
     */
    public function getGoogleMapsUrlAttribute(): ?string
    {
        $manual = $this->attributes['google_maps_url'] ?? null;

        if ($manual) {
            return $manual;
        }

        $query = $this->getLocalizedAddress() ?: trim($this->getLocalizedName() . ', ' . $this->province);

        if ($query === '' || $query === ',') {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($query);
    }
}
