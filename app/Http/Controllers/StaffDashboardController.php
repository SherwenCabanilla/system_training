<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function __invoke(): View
    {
        $patients = Patient::query()->with('user:id,name,email')->latest()->get();
        $upcomingAppointmentCount = Appointment::query()->where('scheduled_at', '>=', now())->count();

        return view('staff.dashboard', compact('patients', 'upcomingAppointmentCount'));
    }
}
