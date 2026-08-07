<?php

namespace App\Http\Requests\Internal\Cms;

use Illuminate\Foundation\Http\FormRequest;

class AlertRequest extends FormRequest
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
            'excerpt_th' => ['required', 'string'],
            'excerpt_en' => ['nullable', 'string'],
            'alert_level' => ['required', 'in:info,warning,danger'],
            'published_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }
}
