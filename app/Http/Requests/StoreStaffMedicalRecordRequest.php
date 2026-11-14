<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffMedicalRecordRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:5000'],
            'result_summary' => ['nullable', 'string', 'max:5000'],
            'record_vitals' => ['nullable', 'boolean'],
            'heart_rate' => ['exclude_unless:record_vitals,1', 'required', 'integer', 'between:20,250'],
            'temperature' => ['exclude_unless:record_vitals,1', 'required', 'numeric', 'between:30,45'],
            'systolic' => ['exclude_unless:record_vitals,1', 'required', 'integer', 'between:70,250'],
            'diastolic' => ['exclude_unless:record_vitals,1', 'required', 'integer', 'between:40,160'],
        ];
    }
}
