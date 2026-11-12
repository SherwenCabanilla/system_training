<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\VitalReadingStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->is_staff) {
            return to_route('staff.dashboard');
        }

        $patient = $request->user()->patient()->firstOrCreate();
        $scheduledAppointments = $patient->appointments()->where('status', 'Scheduled');
        $nextAppointments = $patient->appointments()->whereIn('status', ['Scheduled', 'Pending assignment'])
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(2)
            ->get();
        $awaitingClinicUpdateAppointment = (clone $scheduledAppointments)
            ->where('scheduled_at', '<', now())
            ->latest('scheduled_at')
            ->first();
        $latestMedicalRecord = $patient->medicalRecords()->latest('recorded_on')->first();
        $latestVitalReading = $patient->vitalReadings()->latest('recorded_on')->first();
        $bloodPressureStatus = VitalReadingStatus::bloodPressure($latestVitalReading?->systolic, $latestVitalReading?->diastolic);
        $heartRateStatus = VitalReadingStatus::heartRate($latestVitalReading?->heart_rate);
        $temperatureStatus = VitalReadingStatus::temperature($latestVitalReading?->temperature);

        $doctors = Doctor::query()->where('is_active', true)->orderBy('department')->orderBy('name')->get();

        return view('dashboard', compact('doctors', 'nextAppointments', 'awaitingClinicUpdateAppointment', 'latestMedicalRecord', 'latestVitalReading', 'bloodPressureStatus', 'heartRateStatus', 'temperatureStatus'));
    }
}
