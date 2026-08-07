<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    public const CATEGORY_IMAGE = 'image';
    public const CATEGORY_VIDEO = 'video';
    public const CATEGORY_DOCUMENT = 'document';
    public const CATEGORY_OTHER = 'other';

    protected $table = 'media';

    protected $fillable = [
        'folder_id', 'disk', 'path', 'original_name', 'mime_type',
        'size_bytes', 'width', 'height', 'alt_text', 'uploaded_by',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class, 'media_media_tag');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function category(): string
    {
        return match (true) {
            str_starts_with($this->mime_type, 'image/') => self::CATEGORY_IMAGE,
            str_starts_with($this->mime_type, 'video/') => self::CATEGORY_VIDEO,
            in_array($this->mime_type, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ], true) => self::CATEGORY_DOCUMENT,
            default => self::CATEGORY_OTHER,
        };
    }

    public function formattedSize(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024 || $unit === end($units)) {
                return round($value, 1) . " {$unit}";
            }
            $value /= 1024;
        }

        return "{$bytes} B";
    }
}
