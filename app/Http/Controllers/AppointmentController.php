<?php

namespace App\Http\Controllers;

use App\AppointmentAvailability;
use App\AppointmentProposals;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request, AppointmentProposals $proposals): View
    {
        $proposals->expire($this->patientFor($request)->id);
        $appointments = $this->patientFor($request)->appointments();
        $upcomingAppointments = (clone $appointments)
            ->whereIn('status', ['Scheduled', 'Pending assignment', 'Awaiting patient approval'])
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->paginate(10);
        $completedAppointments = (clone $appointments)
            ->whereIn('status', ['Completed', 'Missed', 'Cancelled'])
            ->orderByDesc('scheduled_at')
            ->get();

        $doctors = Doctor::query()->where('is_active', true)->orderBy('department')->orderBy('name')->get();

        return view('appointments.index', compact('upcomingAppointments', 'completedAppointments', 'doctors'));
    }

    public function availability(Request $request, AppointmentAvailability $availability): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'appointment_id' => ['nullable', 'integer'],
        ]);
        $editingAppointment = null;
        $exceptId = null;
        if ($request->filled('appointment_id')) {
            $editingAppointment = $request->user()->patient?->appointments()->findOrFail($request->integer('appointment_id'));
            $exceptId = $editingAppointment?->id;
            abort_if($exceptId === null, 404);
        }
        $slots = $availability->slots(
            $request->user()->patient?->id,
            $request->filled('doctor_id') ? $request->integer('doctor_id') : null,
            $request->date('date'),
            $editingAppointment,
        );

        return response()->json(['slots' => $slots]);
    }

    public function store(StoreAppointmentRequest $request, AppointmentAvailability $availability): JsonResponse
    {
        return DB::transaction(function () use ($request, $availability): JsonResponse {
            $scheduledAt = $request->date('scheduled_at')->setTimezone(config('app.timezone'));
            $availability->ensureWithinClinicHours($scheduledAt);
            $patient = $this->patientFor($request);
            $availability->ensurePatientAvailable($patient->id, $scheduledAt);
            $isClinicAssignment = $request->string('booking_mode')->toString() === 'clinic_assignment';
            $doctor = $isClinicAssignment ? null : $availability->ensureAvailable($request->integer('doctor_id'), $scheduledAt);
            $appointment = $patient->appointments()->create([
                'scheduled_at' => $scheduledAt,
                'doctor_id' => $doctor?->id,
                'doctor_name' => $doctor?->name ?? 'Clinic assignment pending',
                'department' => $doctor?->department ?? 'Awaiting clinic review',
                'reason' => $request->string('reason')->toString() ?: null,
                'status' => $isClinicAssignment ? 'Pending assignment' : 'Scheduled',
            ]);
            $patient->portalNotifications()->create(['target' => ['kind' => 'appointment', 'id' => $appointment->id],
                'type' => $isClinicAssignment ? 'appointment_assignment_requested' : 'appointment_created',
                'title' => $isClinicAssignment ? 'Clinic assignment requested' : 'Appointment request submitted',
                'body' => $isClinicAssignment
                    ? "Your appointment request for {$appointment->scheduled_at->format('M j, Y g:i A')} was sent to the clinic for assignment."
                    : "Your {$appointment->department} appointment with {$appointment->doctor_name} is scheduled for {$appointment->scheduled_at->format('M j, Y g:i A')}.",
            ]);

            return response()->json(['message' => 'Appointment created.', 'appointment' => $appointment], 201);
        }, 5);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment, AppointmentAvailability $availability): JsonResponse
    {
        return DB::transaction(function () use ($request, $appointment, $availability): JsonResponse {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            $scheduledAt = $request->date('scheduled_at')->setTimezone(config('app.timezone'));
            $availability->ensureWithinClinicHours($scheduledAt);
            $patient = $this->patientFor($request);
            abort_unless($appointment->patient_id === $patient->id, 404);
            abort_if($appointment->status === 'Awaiting patient approval', 422, 'Respond to the proposed schedule before editing this request.');
            $availability->ensurePatientAvailable($patient->id, $scheduledAt, $appointment->id);
            if ($appointment->doctor_id !== null && ! $request->filled('doctor_id')) {
                throw ValidationException::withMessages(['doctor_id' => 'This visit now has an assigned doctor. Please refresh before editing.']);
            }
            $doctor = $request->filled('doctor_id') ? $availability->ensureAvailable($request->integer('doctor_id'), $scheduledAt, $appointment->id) : null;
            $appointment->update([
                ...$request->safe()->except('doctor_id'),
                'scheduled_at' => $scheduledAt,
                'doctor_id' => $doctor?->id,
                'doctor_name' => $doctor?->name ?? $appointment->doctor_name,
                'department' => $doctor?->department ?? $appointment->department,
            ]);
            $patient->portalNotifications()->create(['target' => ['kind' => 'appointment', 'id' => $appointment->id],
                'type' => 'appointment_updated',
                'title' => 'Appointment updated',
                'body' => "Your appointment with {$appointment->doctor_name} was updated for {$appointment->scheduled_at->format('M j, Y g:i A')}.",
            ]);

            return response()->json(['message' => 'Appointment updated.', 'appointment' => $appointment]);
        }, 5);
    }

    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        return DB::transaction(function () use ($request, $appointment): JsonResponse {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            $patient = $this->patientFor($request);
            abort_unless($appointment->patient_id === $patient->id, 404);
            abort_if($appointment->status === 'Awaiting patient approval', 409, 'Review the proposal and use Cancel request to cancel it.');
            $patient->portalNotifications()->create(['target' => ['kind' => 'appointment', 'id' => $appointment->id],
                'type' => 'appointment_cancelled',
                'title' => 'Appointment cancelled',
                'body' => "Your {$appointment->department} appointment with {$appointment->doctor_name} on {$appointment->scheduled_at->format('M j, Y g:i A')} was cancelled.",
            ]);
            $appointment->delete();

            return response()->json(['message' => 'Appointment deleted.']);
        }, 5);
    }

    private function patientFor(Request $request): Patient
    {
        return $request->user()->patient()->firstOrCreate();
    }
}
