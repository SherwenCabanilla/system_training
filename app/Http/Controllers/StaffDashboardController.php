<?php

namespace App\Http\Controllers;

use App\AppointmentAvailability;
use App\Http\Requests\AssignStaffAppointmentRequest;
use App\Http\Requests\StoreStaffVitalReadingRequest;
use App\Http\Requests\UpdateStaffAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function __invoke(): View
    {
        $scheduledAppointments = Appointment::query()
            ->with(['patient.user:id,name,email', 'vitalReadings'])
            ->where('status', 'Scheduled');
        $assignmentRequestCount = Appointment::query()->where('status', 'Pending assignment')->count();
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

    public function requests(): View
    {
        $appointments = Appointment::query()
            ->with('patient.user:id,name,email')
            ->where('status', 'Pending assignment')
            ->orderBy('scheduled_at')
            ->paginate(10);
        $doctors = Doctor::query()->where('is_active', true)->orderBy('department')->orderBy('name')->get();

        return view('staff.appointments', [
            'appointments' => $appointments,
            'doctors' => $doctors,
            'title' => 'Assignment requests',
            'description' => 'Read the patient concern, then assign a suitable doctor to confirm their visit.',
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

    public function assignAppointment(AssignStaffAppointmentRequest $request, Appointment $appointment, AppointmentAvailability $availability): RedirectResponse
    {
        return DB::transaction(function () use ($request, $appointment, $availability): RedirectResponse {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless($appointment->status === 'Pending assignment', 422);

            $availability->ensurePatientAvailable($appointment->patient_id, $appointment->scheduled_at, $appointment->id);
            $doctor = $availability->ensureAvailable($request->integer('doctor_id'), $appointment->scheduled_at, $appointment->id);
            $appointment->update(['doctor_id' => $doctor->id, 'doctor_name' => $doctor->name, 'department' => $doctor->department, 'status' => 'Scheduled']);
            $appointment->patient->portalNotifications()->create(['type' => 'appointment_assigned', 'title' => 'Appointment confirmed', 'body' => "Your appointment with {$doctor->name} is confirmed for {$appointment->scheduled_at->format('M j, Y g:i A')}."]);

            return to_route('staff.requests')->with('status', 'Doctor assigned and patient notified.');
        }, 5);
    }

    public function updateAppointmentStatus(UpdateStaffAppointmentStatusRequest $request, Appointment $appointment, AppointmentAvailability $availability): RedirectResponse
    {
        return DB::transaction(function () use ($request, $appointment, $availability): RedirectResponse {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            $status = $request->string('status')->toString();
            if ($status === 'Scheduled') {
                abort_if($appointment->doctor_id === null, 422, 'Assign a doctor before scheduling this visit.');
                $availability->ensurePatientAvailable($appointment->patient_id, $appointment->scheduled_at, $appointment->id);
                $availability->ensureAvailable($appointment->doctor_id, $appointment->scheduled_at, $appointment->id);
            }
            $appointment->update(['status' => $status]);
            $appointment->patient->portalNotifications()->create([
                'type' => 'appointment_status_updated',
                'title' => 'Appointment status updated',
                'body' => "Your appointment with {$appointment->doctor_name} on {$appointment->scheduled_at->format('M j, Y g:i A')} is now marked {$status}.",
            ]);

            return to_route('staff.dashboard')->with('status', "Appointment marked {$status}.");
        }, 5);
    }

    public function storeVitalReading(StoreStaffVitalReadingRequest $request, Appointment $appointment): RedirectResponse
    {
        $appointment->update(['status' => 'Completed']);
        $vitalReading = $appointment->patient->vitalReadings()->updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                ...$request->safe()->only(['recorded_on', 'heart_rate', 'temperature', 'systolic', 'diastolic']),
            ],
        );
        $appointment->patient->portalNotifications()->create([
            'type' => 'vital_reading_available',
            'title' => $vitalReading->wasRecentlyCreated ? 'New visit measurements available' : 'Visit measurements updated',
            'body' => "New measurements from your {$appointment->department} visit are available in Medical Records.",
        ]);

        return to_route('staff.dashboard')->with('status', 'Visit measurements saved and the patient was notified.');
    }
}
