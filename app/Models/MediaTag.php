<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaTag extends Model
{
    protected $fillable = ['name'];

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_media_tag');
    }
}
