<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
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
            'booking_mode' => ['required', Rule::in(['choose_doctor', 'clinic_assignment'])],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id', Rule::requiredIf($this->input('booking_mode') === 'choose_doctor')],
            'reason' => ['nullable', 'string', 'max:1000', Rule::requiredIf($this->input('booking_mode') === 'clinic_assignment')],
        ];
    }
}
