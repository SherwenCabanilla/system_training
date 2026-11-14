<?php

namespace App\Http\Controllers;

use App\AppointmentAvailability;
use App\AppointmentProposals;
use App\Http\Requests\AssignStaffAppointmentRequest;
use App\Http\Requests\StoreStaffVitalReadingRequest;
use App\Http\Requests\UpdateStaffAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function __invoke(AppointmentProposals $proposals): View
    {
        $proposals->expire();
        $scheduledAppointments = Appointment::query()
            ->with(['patient.user:id,name,email', 'vitalReadings'])
            ->where('status', 'Scheduled');
        $assignmentRequestCount = Appointment::query()->whereIn('status', ['Pending assignment', 'Awaiting patient approval'])->count();
        $startOfToday = now()->startOfDay();
        $startOfTomorrow = now()->addDay()->startOfDay();
        $appointmentsNeedingOutcome = (clone $scheduledAppointments)
            ->where('scheduled_at', '<', $startOfToday)
            ->latest('scheduled_at')
            ->paginate(5, ['*'], 'overdue_page')
            ->withQueryString();
        $todayAppointments = (clone $scheduledAppointments)
            ->where('scheduled_at', '>=', $startOfToday)
            ->where('scheduled_at', '<', $startOfTomorrow)
            ->orderBy('scheduled_at')
            ->paginate(5, ['*'], 'today_page')
            ->withQueryString();
        $upcomingAppointmentCount = (clone $scheduledAppointments)
            ->where('scheduled_at', '>=', $startOfTomorrow)
            ->count();

        return view('staff.dashboard', compact('assignmentRequestCount', 'appointmentsNeedingOutcome', 'todayAppointments', 'upcomingAppointmentCount'));
    }

    public function requests(AppointmentProposals $proposals): View
    {
        $proposals->expire();
        $appointments = Appointment::query()
            ->with('patient.user:id,name,email')
            ->whereIn('status', ['Pending assignment', 'Awaiting patient approval'])
            ->orderBy('scheduled_at')->orderBy('id')
            ->paginate(10);
        $doctors = Doctor::query()->where('is_active', true)->orderBy('department')->orderBy('name')->get();

        return view('staff.appointments', [
            'appointments' => $appointments,
            'doctors' => $doctors,
            'title' => 'Assignment requests',
            'description' => 'Oldest requests appear first. Review expired preferences and assign an available doctor and future time.',
            'isAssignment' => true,
        ]);
    }

    public function upcoming(): View
    {
        $appointments = Appointment::query()
            ->with(['patient.user:id,name,email', 'vitalReadings'])
            ->where('status', 'Scheduled')
            ->where('scheduled_at', '>=', now()->addDay()->startOfDay())
            ->orderBy('scheduled_at')
            ->paginate(10);

        return view('staff.appointments', [
            'appointments' => $appointments,
            'title' => 'Upcoming appointments',
            'description' => 'Confirmed visits from tomorrow onward, ordered by appointment time.',
            'isAssignment' => false,
        ]);
    }

    public function history(): View
    {
        $appointments = Appointment::query()
            ->with(['patient.user:id,name,email', 'vitalReadings'])
            ->whereIn('status', ['Completed', 'Missed', 'Cancelled'])
            ->latest('updated_at')
            ->paginate(10);

        return view('staff.appointments', [
            'appointments' => $appointments,
            'title' => 'Visit history',
            'description' => 'Review completed, missed, and cancelled visits. Add or update measurements for completed care.',
            'isAssignment' => false,
        ]);
    }

    public function assignmentAvailability(Request $request, Appointment $appointment, AppointmentAvailability $availability): JsonResponse
    {
        abort_unless($appointment->status === 'Pending assignment', 422, 'This request has already been assigned. Refresh the list.');
        $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        return response()->json(['slots' => $availability->slots(
            $appointment->patient_id, $request->integer('doctor_id'), $request->date('date'), $appointment,
        )])->header('Cache-Control', 'private, no-store');
    }

    public function assignAppointment(AssignStaffAppointmentRequest $request, Appointment $appointment, AppointmentAvailability $availability, AppointmentProposals $proposals): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($request, $appointment, $availability, $proposals): RedirectResponse|JsonResponse {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless($appointment->status === 'Pending assignment', 422, 'This request has already been assigned. Refresh the list.');

            $scheduledAt = $request->filled('scheduled_at')
                ? $request->date('scheduled_at')->setTimezone(config('app.timezone'))
                : $appointment->scheduled_at;
            if (! $scheduledAt->isFuture()) {
                throw ValidationException::withMessages(['scheduled_at' => 'Choose a future time to confirm this appointment.']);
            }
            $availability->ensurePatientAvailable($appointment->patient_id, $scheduledAt, $appointment->id);
            $doctor = $availability->ensureAvailable($request->integer('doctor_id'), $scheduledAt, $appointment->id);
            $needsApproval = ! $appointment->scheduled_at->equalTo($scheduledAt)
                || ($appointment->doctor_id !== null && $appointment->doctor_id !== $doctor->id);
            if ($needsApproval) {
                $proposals->propose($appointment, $doctor, $scheduledAt);
            } else {
                $appointment->update(['scheduled_at' => $scheduledAt, 'doctor_id' => $doctor->id, 'doctor_name' => $doctor->name, 'department' => $doctor->department, 'status' => 'Scheduled']);
                $appointment->patient->portalNotifications()->create(['target' => ['kind' => 'appointment', 'id' => $appointment->id], 'type' => 'appointment_assigned', 'title' => 'Appointment confirmed', 'body' => "Your appointment with {$doctor->name} is confirmed for {$appointment->scheduled_at->format('M j, Y g:i A')}."]);
            }
            $message = $needsApproval ? 'Proposal sent. The slot is held while awaiting patient approval.' : 'Appointment confirmed and patient notified.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'appointment_id' => $appointment->id]);
            }

            return to_route('staff.requests')->with('status', $needsApproval ? $message : 'Doctor assigned and patient notified.');
        }, 5);
    }

    public function updateAppointmentStatus(UpdateStaffAppointmentStatusRequest $request, Appointment $appointment, AppointmentAvailability $availability): RedirectResponse
    {
        return DB::transaction(function () use ($request, $appointment, $availability): RedirectResponse {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_if($appointment->status === 'Awaiting patient approval', 422, 'The patient must approve this proposal first.');
            $status = $request->string('status')->toString();
            if ($status === 'Scheduled') {
                abort_if($appointment->doctor_id === null, 422, 'Assign a doctor before scheduling this visit.');
                $availability->ensurePatientAvailable($appointment->patient_id, $appointment->scheduled_at, $appointment->id);
                $availability->ensureAvailable($appointment->doctor_id, $appointment->scheduled_at, $appointment->id);
            }
            $appointment->update(['status' => $status]);
            $appointment->patient->portalNotifications()->create(['target' => ['kind' => 'appointment', 'id' => $appointment->id],
                'type' => 'appointment_status_updated',
                'title' => 'Appointment status updated',
                'body' => "Your appointment with {$appointment->doctor_name} on {$appointment->scheduled_at->format('M j, Y g:i A')} is now marked {$status}.",
            ]);

            return to_route('staff.dashboard')->with('status', "Appointment marked {$status}.");
        }, 5);
    }

    public function storeVitalReading(StoreStaffVitalReadingRequest $request, Appointment $appointment): RedirectResponse
    {
        return DB::transaction(function () use ($request, $appointment): RedirectResponse {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_if($appointment->status === 'Awaiting patient approval', 422, 'The patient must approve this proposal first.');
            $appointment->update(['status' => 'Completed']);
            $vitalReading = $appointment->patient->vitalReadings()->updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    ...$request->safe()->only(['recorded_on', 'heart_rate', 'temperature', 'systolic', 'diastolic']),
                ],
            );
            $appointment->patient->portalNotifications()->create(['target' => ['kind' => 'vital', 'id' => $vitalReading->id],
                'type' => 'vital_reading_available',
                'title' => $vitalReading->wasRecentlyCreated ? 'New visit measurements available' : 'Visit measurements updated',
                'body' => "New measurements from your {$appointment->department} visit are available in Medical Records.",
            ]);

            return to_route('staff.dashboard')->with('status', 'Visit measurements saved and the patient was notified.');
        }, 5);
    }
}
