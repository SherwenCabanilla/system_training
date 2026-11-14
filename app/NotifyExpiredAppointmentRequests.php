<?php

namespace App;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NotifyExpiredAppointmentRequests
{
    public function forPatient(Patient $patient): void
    {
        $patient->appointments()->where('status', 'Pending assignment')
            ->where('scheduled_at', '<=', now())->select('appointments.id')->chunkById(100, function (Collection $appointments) use ($patient): void {
                foreach ($appointments as $candidate) {
                    DB::transaction(function () use ($candidate, $patient): void {
                        $appointment = Appointment::query()->where('patient_id', $patient->id)
                            ->where('status', 'Pending assignment')->where('scheduled_at', '<=', now())
                            ->lockForUpdate()->find($candidate->id);
                        if ($appointment === null) {
                            return;
                        }

                        $patient->portalNotifications()->firstOrCreate([
                            'type' => 'appointment_request_expired',
                            'body' => "Your request #{$appointment->id} for {$appointment->scheduled_at->format('M j, Y g:i A')} was not confirmed before the preferred time passed. Choose a new preferred time or cancel the request. This was not a missed appointment.",
                        ], ['title' => 'Appointment request needs rescheduling', 'target' => ['kind' => 'appointment', 'id' => $appointment->id]]);
                    }, 5);
                }
            });
    }
}
