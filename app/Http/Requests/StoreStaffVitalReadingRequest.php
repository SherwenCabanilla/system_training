<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffVitalReadingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_staff ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recorded_on' => ['required', 'date'],
            'heart_rate' => ['required', 'integer', 'between:20,250'],
            'temperature' => ['required', 'numeric', 'between:30,45'],
            'systolic' => ['required', 'integer', 'between:70,250'],
            'diastolic' => ['required', 'integer', 'between:40,160'],
        ];
    }
}
