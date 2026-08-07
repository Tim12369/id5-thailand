<?php

namespace App\Http\Requests\Internal\Cms;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_th' => ['required', 'string', 'max:500'],
            'title_en' => ['nullable', 'string', 'max:500'],
            'body_th' => ['required', 'string'],
            'body_en' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'published_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }
}
