<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $appointments = $this->patientFor($request)->appointments()->orderBy('scheduled_at')->get();

        return view('appointments.index', compact('appointments'));
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->patientFor($request)->appointments()->create($request->validated());

        return response()->json(['message' => 'Appointment created.', 'appointment' => $appointment], 201);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        abort_unless($appointment->patient_id === $this->patientFor($request)->id, 404);
        $appointment->update($request->validated());

        return response()->json(['message' => 'Appointment updated.', 'appointment' => $appointment]);
    }

    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        abort_unless($appointment->patient_id === $this->patientFor($request)->id, 404);
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted.']);
    }

    private function patientFor(Request $request): Patient
    {
        return $request->user()->patient()->firstOrCreate();
    }
}
