<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardAuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'target_type', 'target_id',
        'description', 'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        string $description,
        ?string $targetType = null,
        ?int $targetId = null,
        array $oldValues = [],
        array $newValues = []
    ): void {
        if (!auth()->check()) return;

        static::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'description' => $description,
            'old_values'  => $oldValues ?: null,
            'new_values'  => $newValues ?: null,
            'ip_address'  => request()->ip(),
            'user_agent'  => substr(request()->userAgent() ?? '', 0, 500),
        ]);
    }
}
