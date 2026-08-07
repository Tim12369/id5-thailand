<?php

namespace App\Http\Requests\Internal\Cms;

use Illuminate\Foundation\Http\FormRequest;

class NewsRequest extends FormRequest
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
            'excerpt_th' => ['nullable', 'string'],
            'excerpt_en' => ['nullable', 'string'],
            'body_th' => ['required', 'string'],
            'body_en' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'thumbnail' => ['nullable', 'image', 'max:4096'],
            'published_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,published'],
        ];
    }
}
