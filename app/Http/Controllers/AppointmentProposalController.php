<?php

namespace App\Http\Controllers;

use App\AppointmentAvailability;
use App\AppointmentProposals;
use App\Http\Requests\RespondToAppointmentProposalRequest;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AppointmentProposalController extends Controller
{
    public function update(RespondToAppointmentProposalRequest $request, Appointment $appointment, AppointmentAvailability $availability, AppointmentProposals $proposals): JsonResponse
    {
        $isStaff = $request->routeIs('staff.appointments.proposal');
        abort_unless($isStaff || $appointment->patient_id === $request->user()->patient?->id, 404);

        return DB::transaction(function () use ($request, $appointment, $availability, $proposals, $isStaff): JsonResponse {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless($appointment->status === 'Awaiting patient approval' && $appointment->proposal_token === $request->string('proposal_token')->toString(), 409, 'This proposal has changed. Refresh your appointments before responding.');
            if ($appointment->proposal_expires_at->lte(now()) || ! $appointment->scheduled_at->isFuture()) {
                $proposals->returnToReview($appointment, true);

                return response()->json(['message' => 'This proposal expired. Your request has returned to staff review.'], 409);
            }
            $decision = $request->string('decision')->toString();
            if ($decision === 'decline') {
                $proposals->returnToReview($appointment);
            } else {
                if ($decision === 'accept') {
                    abort_if($appointment->doctor_id === null, 422, 'This doctor is no longer available. Request another schedule.');
                    $availability->ensurePatientAvailable($appointment->patient_id, $appointment->scheduled_at, $appointment->id);
                    $availability->ensureAvailable($appointment->doctor_id, $appointment->scheduled_at, $appointment->id);
                }
                $appointment->update(['status' => $decision === 'accept' ? 'Scheduled' : 'Cancelled', 'proposal_expires_at' => null, 'proposal_token' => null]);
                $appointment->patient->portalNotifications()->create(['target' => ['kind' => 'appointment', 'id' => $appointment->id],
                    'type' => $decision === 'accept' ? 'appointment_assigned' : 'appointment_cancelled',
                    'title' => $decision === 'accept' ? 'Appointment confirmed' : 'Appointment cancelled',
                    'body' => $decision === 'accept'
                        ? "Your appointment with {$appointment->doctor_name} is confirmed for {$appointment->scheduled_at->format('M j, Y g:i A')}.".($isStaff ? " Staff member #{$request->user()->id} recorded your acceptance by phone." : '')
                        : 'Your appointment request was cancelled and the held slot released.',
                ]);
            }

            return response()->json(['message' => match ($decision) {
                'accept' => 'Appointment confirmed.',
                'decline' => 'Request returned to staff review. You can edit your preferred time.',
                default => 'Appointment cancelled.',
            }]);
        }, 5);
    }
}
