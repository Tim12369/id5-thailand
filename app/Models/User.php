<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'position',
        'department',
        'station_id',
        'phone',
        'avatar_path',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    /**
     * Officers restricted to viewing only their own assigned office
     * (e.g. checkpoint officers), rather than the full network.
     */
    public function isScopedToOwnStation(): bool
    {
        return $this->hasRole('immigration_checkpoint_officer') && !$this->hasRole('administrator');
    }

    public function hasPermission(string $slug): bool
    {
        return $this->roles->loadMissing('permissions')
            ->pluck('permissions')
            ->flatten()
            ->contains('slug', $slug);
    }
}
