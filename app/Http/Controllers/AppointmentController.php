<?php

namespace App\Http\Controllers;

use App\AppointmentAvailability;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $appointments = $this->patientFor($request)->appointments();
        $upcomingAppointments = (clone $appointments)
            ->whereIn('status', ['Scheduled', 'Pending assignment'])
            ->orderBy('scheduled_at')
            ->get();
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
        $day = $request->date('date')->startOfDay();
        $doctorId = $request->filled('doctor_id') ? $request->integer('doctor_id') : null;
        $patientId = $request->user()->patient?->id;
        $busy = Appointment::query()
            ->where(function (Builder $query) use ($patientId, $doctorId): void {
                $query->where(function (Builder $query) use ($patientId): void {
                    $query->where('patient_id', $patientId)->whereIn('status', ['Scheduled', 'Pending assignment']);
                });
                if ($doctorId !== null) {
                    $query->orWhere(function (Builder $query) use ($doctorId): void {
                        $query->where('doctor_id', $doctorId)->where('status', 'Scheduled');
                    });
                }
            })
            ->where('scheduled_at', '>', $day->copy()->subMinutes(AppointmentAvailability::DURATION_MINUTES))
            ->where('scheduled_at', '<', $day->copy()->addDay()->addMinutes(AppointmentAvailability::DURATION_MINUTES))
            ->when($exceptId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptId))
            ->get(['scheduled_at']);
        $isActive = $doctorId === null || Doctor::query()->whereKey($doctorId)->where('is_active', true)->exists();
        $starts = [];
        for ($minute = AppointmentAvailability::OPENING_HOUR * 60; $minute <= AppointmentAvailability::CLOSING_HOUR * 60 - AppointmentAvailability::DURATION_MINUTES && $isActive; $minute += AppointmentAvailability::DURATION_MINUTES) {
            $starts[] = $day->copy()->addMinutes($minute);
        }
        if ($isActive && $editingAppointment?->scheduled_at->isSameDay($day)) {
            $starts[] = $editingAppointment->scheduled_at;
        }
        $slots = [];
        foreach (collect($starts)->unique(fn (CarbonInterface $start): string => $start->format('H:i'))->sort() as $start) {
            $overlaps = $busy->contains(fn (Appointment $visit): bool => $visit->scheduled_at->gt($start->copy()->subMinutes(AppointmentAvailability::DURATION_MINUTES))
                && $visit->scheduled_at->lt($start->copy()->addMinutes(AppointmentAvailability::DURATION_MINUTES)));
            if ($start->isFuture() && $availability->isWithinClinicHours($start) && ! $overlaps) {
                $slots[] = ['value' => $start->format('Y-m-d\TH:i'), 'label' => $start->format('g:i A')];
            }
        }

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
            $patient->portalNotifications()->create([
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
            $patient->portalNotifications()->create([
                'type' => 'appointment_updated',
                'title' => 'Appointment updated',
                'body' => "Your appointment with {$appointment->doctor_name} was updated for {$appointment->scheduled_at->format('M j, Y g:i A')}.",
            ]);

            return response()->json(['message' => 'Appointment updated.', 'appointment' => $appointment]);
        }, 5);
    }

    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        $patient = $this->patientFor($request);
        abort_unless($appointment->patient_id === $patient->id, 404);
        $patient->portalNotifications()->create([
            'type' => 'appointment_cancelled',
            'title' => 'Appointment cancelled',
            'body' => "Your {$appointment->department} appointment with {$appointment->doctor_name} on {$appointment->scheduled_at->format('M j, Y g:i A')} was cancelled.",
        ]);
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted.']);
    }

    private function patientFor(Request $request): Patient
    {
        return $request->user()->patient()->firstOrCreate();
    }
}
