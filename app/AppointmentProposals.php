<?php

namespace App;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppointmentProposals
{
    /** Called with the appointment locked and availability checked in a transaction. */
    public function propose(Appointment $appointment, Doctor $doctor, CarbonInterface $time): void
    {
        $deadline = now()->addMinutes(max(1, config('appointments.proposal_hold_minutes')))->min($time);
        $appointment->update([
            'requested_details' => [
                'scheduled_at' => $appointment->scheduled_at->toDateTimeString(),
                'doctor_id' => $appointment->doctor_id,
                'doctor_name' => $appointment->doctor_name,
                'department' => $appointment->department,
            ],
            'proposal_token' => (string) Str::uuid(),
            'proposal_expires_at' => $deadline,
            'scheduled_at' => $time,
            'doctor_id' => $doctor->id,
            'doctor_name' => $doctor->name,
            'department' => $doctor->department,
            'status' => 'Awaiting patient approval',
        ]);
        $appointment->patient->portalNotifications()->create(['target' => ['kind' => 'appointment', 'id' => $appointment->id],
            'type' => 'appointment_proposed',
            'title' => 'Please approve your proposed appointment',
            'body' => "The clinic proposes {$doctor->name} on {$time->format('M j, Y g:i A')}. Review the change in Appointments and respond by {$deadline->format('M j, Y g:i A')} (".config('app.timezone').'). This visit is not confirmed yet.',
        ]);
    }

    /** Restore the request for staff review; the proposed slot is released. */
    public function returnToReview(Appointment $appointment, bool $expired = false): void
    {
        $appointment->update([
            ...$appointment->requested_details,
            'status' => 'Pending assignment',
            'requested_details' => null,
            'proposal_token' => null,
            'proposal_expires_at' => null,
        ]);
        $appointment->patient->portalNotifications()->create(['target' => ['kind' => 'appointment', 'id' => $appointment->id],
            'type' => $expired ? 'appointment_proposal_expired' : 'appointment_proposal_declined',
            'title' => $expired ? 'Appointment proposal expired' : 'Another schedule requested',
            'body' => $expired
                ? 'Your approval deadline passed. The held slot was released and your request returned to staff review. No visit was confirmed.'
                : 'The proposed slot was released. Staff will review your request for another schedule. You can update your preferred time in Appointments.',
        ]);
    }

    public function expire(?int $patientId = null): void
    {
        Appointment::query()->where('status', 'Awaiting patient approval')->where('proposal_expires_at', '<=', now())
            ->when($patientId !== null, fn (Builder $query): Builder => $query->where('patient_id', $patientId))
            ->chunkById(100, function (Collection $appointments): void {
                foreach ($appointments as $appointment) {
                    DB::transaction(function () use ($appointment): void {
                        $locked = Appointment::query()->lockForUpdate()->find($appointment->id);
                        if ($locked?->status === 'Awaiting patient approval' && $locked->proposal_expires_at->lte(now())) {
                            $this->returnToReview($locked, true);
                        }
                    }, 5);
                }
            });
    }
}
