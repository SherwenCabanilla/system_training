<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $appointments = $this->patientFor($request)->appointments();
        $upcomingAppointments = (clone $appointments)
            ->where('status', '!=', 'Completed')
            ->orderBy('scheduled_at')
            ->get();
        $completedAppointments = (clone $appointments)
            ->where('status', 'Completed')
            ->orderByDesc('scheduled_at')
            ->get();

        $doctors = Doctor::query()->where('is_active', true)->orderBy('department')->orderBy('name')->get();

        return view('appointments.index', compact('upcomingAppointments', 'completedAppointments', 'doctors'));
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $doctor = Doctor::findOrFail($request->integer('doctor_id'));
        $patient = $this->patientFor($request);
        $appointment = $patient->appointments()->create([
            ...$request->safe()->except('doctor_id'),
            'doctor_id' => $doctor->id,
            'doctor_name' => $doctor->name,
            'department' => $doctor->department,
        ]);
        $patient->portalNotifications()->create([
            'type' => 'appointment_created',
            'title' => 'Appointment request submitted',
            'body' => "Your {$appointment->department} appointment with {$appointment->doctor_name} is scheduled for {$appointment->scheduled_at->format('M j, Y g:i A')}.",
        ]);

        return response()->json(['message' => 'Appointment created.', 'appointment' => $appointment], 201);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $patient = $this->patientFor($request);
        abort_unless($appointment->patient_id === $patient->id, 404);
        $doctor = Doctor::findOrFail($request->integer('doctor_id'));
        $appointment->update([
            ...$request->safe()->except('doctor_id'),
            'doctor_id' => $doctor->id,
            'doctor_name' => $doctor->name,
            'department' => $doctor->department,
        ]);
        $patient->portalNotifications()->create([
            'type' => 'appointment_updated',
            'title' => 'Appointment updated',
            'body' => "Your appointment with {$appointment->doctor_name} was updated for {$appointment->scheduled_at->format('M j, Y g:i A')}.",
        ]);

        return response()->json(['message' => 'Appointment updated.', 'appointment' => $appointment]);
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
