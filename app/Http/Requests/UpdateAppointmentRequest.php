<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id', Rule::requiredIf($this->route('appointment')->doctor_id !== null)],
            'reason' => ['nullable', 'string', 'max:1000', Rule::requiredIf($this->route('appointment')->doctor_id === null)],
        ];
    }
}
