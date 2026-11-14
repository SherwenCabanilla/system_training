<?php

namespace App\Http\Controllers;

use App\AppointmentProposals;
use App\Models\Patient;
use App\Models\PortalNotification;
use App\NotifyExpiredAppointmentRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalNotificationController extends Controller
{
    public function feed(Request $request, NotifyExpiredAppointmentRequests $expiredRequests, AppointmentProposals $proposals): JsonResponse
    {
        abort_if($request->user()->is_staff, 403);
        $request->validate(['after' => ['nullable', 'integer', 'min:0']]);
        $patient = $request->user()->patient;
        if ($patient !== null) {
            $proposals->expire($patient->id);
            $expiredRequests->forPatient($patient);
        }
        $query = PortalNotification::query()->where('patient_id', $patient?->id);
        $revision = (int) (clone $query)->max('id');
        $unreadCount = (clone $query)->whereNull('read_at')->count();
        $notifications = (clone $query)->whereNull('read_at')
            ->where('id', '>', $request->integer('after'))
            ->whereNotIn('type', ['appointment_created', 'appointment_updated', 'appointment_cancelled', 'appointment_assignment_requested'])
            ->orderBy('id')->limit(20)->get(['id', 'type', 'title', 'body', 'target']);

        return response()->json([
            'unread_count' => $unreadCount,
            'revision' => $revision,
            'notifications' => $notifications->map(fn (PortalNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'url' => $notification->destinationUrl(),
                'link_label' => match ($notification->type) {
                    'appointment_assigned', 'appointment_status_updated', 'appointment_reminder', 'appointment_request_expired', 'appointment_proposed', 'appointment_proposal_expired', 'appointment_proposal_declined' => 'View appointments',
                    'vital_reading_available', 'medical_record_available' => 'View medical records',
                    default => 'View notifications',
                },
            ])->values(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request, NotifyExpiredAppointmentRequests $expiredRequests, AppointmentProposals $proposals): View
    {
        $patient = $this->patientFor($request);
        $proposals->expire($patient->id);
        $expiredRequests->forPatient($patient);
        $portalNotifications = $patient->portalNotifications()->latest()->get();

        return view('portal-notifications.index', compact('portalNotifications'));
    }

    public function markAsRead(Request $request, PortalNotification $portalNotification): RedirectResponse|JsonResponse
    {
        abort_unless($portalNotification->patient_id === $this->patientFor($request)->id, 404);
        if ($portalNotification->read_at === null) {
            $portalNotification->update(['read_at' => now()]);
        }
        if ($request->boolean('open')) {
            $url = $portalNotification->destinationUrl();

            return $request->expectsJson() ? response()->json(['url' => $url]) : redirect()->to($url);
        }

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
