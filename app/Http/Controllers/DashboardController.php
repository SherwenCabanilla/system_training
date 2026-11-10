<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $patient = $request->user()->patient()->firstOrCreate();
        $nextAppointment = $patient->appointments()->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->first();
        $latestMedicalRecord = $patient->medicalRecords()->latest('recorded_on')->first();
        $latestVitalReading = $patient->vitalReadings()->latest('recorded_on')->first();
        $upcomingAppointmentCount = $patient->appointments()->where('scheduled_at', '>=', now())->count();
        $unreadNotificationCount = $patient->portalNotifications()->whereNull('read_at')->count();

        return view('dashboard', compact('nextAppointment', 'latestMedicalRecord', 'latestVitalReading', 'upcomingAppointmentCount', 'unreadNotificationCount'));
    }
}
