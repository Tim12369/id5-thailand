<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgChartMember extends Model
{
    protected $fillable = ['tier_id', 'name_th', 'name_en', 'rank_th', 'position_th', 'photo_path', 'sort_order'];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(OrgChartTier::class, 'tier_id');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }
}
