<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PortalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $portalNotifications = $this->patientFor($request)->portalNotifications()->latest()->get();

        return view('portal-notifications.index', compact('portalNotifications'));
    }

    public function markAsRead(Request $request, PortalNotification $portalNotification): RedirectResponse
    {
        abort_unless($portalNotification->patient_id === $this->patientFor($request)->id, 404);
        $portalNotification->update(['read_at' => now()]);

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->patientFor($request)->portalNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return to_route('notifications.index');
    }

    private function patientFor(Request $request): Patient
    {
        return $request->user()->patient()->firstOrCreate();
    }
}
