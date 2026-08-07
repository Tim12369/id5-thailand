<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'usage_count'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tags');
    }
}
