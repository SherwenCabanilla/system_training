<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondToAppointmentProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->routeIs('staff.appointments.proposal')) {
            return $this->user()?->is_staff ?? false;
        }

        return $this->user() !== null && ! $this->user()->is_staff;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'proposal_token' => ['required', 'uuid'],
            'decision' => ['required', $this->routeIs('staff.appointments.proposal') ? 'in:accept' : 'in:accept,decline,cancel'],
            'phone_acceptance' => $this->routeIs('staff.appointments.proposal') ? ['required', 'accepted'] : ['prohibited'],
        ];
    }
}
