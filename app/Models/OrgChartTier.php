<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgChartTier extends Model
{
    protected $fillable = ['section_id', 'label_th', 'label_en', 'sort_order'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(OrgChartSection::class, 'section_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrgChartMember::class, 'tier_id')->orderBy('sort_order');
    }
}
