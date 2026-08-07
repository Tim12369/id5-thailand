<?php

namespace App\Http\Requests\CommandCenter;

use App\Models\CommandCenterIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident instanceof CommandCenterIncident
            && $this->user()->can('updateIncident', $incident);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(CommandCenterIncident::STATUSES)],
        ];
    }
}
