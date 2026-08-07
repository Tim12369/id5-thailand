<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleAttachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'article_id', 'filename', 'original_name',
        'path', 'disk', 'mime_type', 'size_bytes', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1_048_576) return round($bytes / 1_048_576, 1) . ' MB';
        if ($bytes >= 1_024)     return round($bytes / 1_024, 0) . ' KB';
        return $bytes . ' B';
    }
}
