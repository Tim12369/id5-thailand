<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * The 4 division cards in the homepage "Explore ID5 Services" grid
 * (Administrative Affairs, Foreigner Services, Investigation, Immigration
 * Technology). A fixed set tied to real organizational divisions -- admins
 * edit the photo/text of existing rows via Website Management, they don't
 * create or delete rows (same pattern as Stations/PublicForm).
 */
class HomeDivisionCard extends Model
{
    protected $fillable = [
        'key', 'title_th', 'title_en', 'desc_th', 'desc_en', 'photo_path', 'sort_order',
    ];

    public function getLocalizedTitle(): string
    {
        return match (App::getLocale()) {
            'en' => $this->title_en ?: $this->title_th,
            default => $this->title_th,
        };
    }

    public function getLocalizedDescription(): ?string
    {
        return match (App::getLocale()) {
            'en' => $this->desc_en ?: $this->desc_th,
            default => $this->desc_th,
        };
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }
}
