<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiredAppointmentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_pending_requests_notify_once_and_remain_pending(): void
    {
        $this->travelTo(now()->startOfDay()->setTime(12, 0));
        $appointment = $this->requestAt(now()->subHour()->toDateTimeString());

        $this->actingAs($appointment->patient->user)->getJson(route('notifications.feed'))
            ->assertOk()->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Appointment request needs rescheduling')
            ->assertJsonPath('notifications.0.url', route('appointments.index', ['appointment' => $appointment->id]));
        $this->getJson(route('notifications.feed'))->assertOk()->assertJsonPath('unread_count', 1);
        $this->patch(route('notifications.read-all'))->assertRedirect();
        $this->getJson(route('notifications.feed'))->assertOk()->assertJsonPath('unread_count', 0);

        $this->assertDatabaseCount('portal_notifications', 1);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Pending assignment']);
    }

    public function test_only_the_current_patients_expired_pending_requests_are_notified(): void
    {
        $this->travelTo(now()->startOfDay()->setTime(12, 0));
        $patient = Patient::factory()->create();
        $this->requestAt(now()->addDay()->toDateTimeString(), $patient);
        $scheduled = $this->requestAt(now()->subHour()->toDateTimeString(), $patient);
        $scheduled->update(['status' => 'Scheduled']);
        $cancelled = $this->requestAt(now()->subHours(2)->toDateTimeString(), $patient);
        $cancelled->update(['status' => 'Cancelled']);
        $this->requestAt(now()->subHour()->toDateTimeString());

        $this->actingAs($patient->user)->getJson(route('notifications.feed'))->assertOk()->assertJsonCount(0, 'notifications');

        $this->assertDatabaseCount('portal_notifications', 0);
    }

    public function test_expired_request_can_be_rescheduled_and_notifies_again_only_if_the_new_time_passes(): void
    {
        $this->travelTo(now()->startOfDay()->setTime(12, 0));
        $appointment = $this->requestAt(now()->subHour()->toDateTimeString());
        $future = now()->addDay()->setTime(10, 0);
        $this->actingAs($appointment->patient->user)->getJson(route('notifications.feed'))->assertOk();

        $this->patchJson(route('appointments.update', $appointment), ['scheduled_at' => $future->toDateTimeString(), 'reason' => 'Check-up'])
            ->assertOk()->assertJsonPath('appointment.status', 'Pending assignment');
        $this->getJson(route('notifications.feed'))->assertOk();
        $this->assertDatabaseCount('portal_notifications', 2);
        $this->travelTo($future->copy()->addHour());
        $this->getJson(route('notifications.feed'))->assertOk();
        $this->getJson(route('notifications.feed'))->assertOk();

        $this->assertDatabaseCount('portal_notifications', 3);
    }

    public function test_expired_requests_are_visible_to_patients_and_first_in_staff_queue(): void
    {
        $this->travelTo(now()->startOfDay()->setTime(12, 0));
        $expired = $this->requestAt(now()->subHour()->toDateTimeString());
        $expired->update(['reason' => 'Expired request concern']);
        $future = $this->requestAt(now()->addDay()->toDateTimeString());
        $future->update(['reason' => 'Future request concern']);

        $this->actingAs($expired->patient->user)->get(route('appointments.index'))
            ->assertOk()->assertSee('Needs rescheduling')->assertSee('Reschedule')->assertSee('Cancel');
        $this->get(route('dashboard'))->assertOk()->assertSee('1 pending request needs rescheduling');
        $this->actingAs(User::factory()->create(['is_staff' => true]))->get(route('staff.requests'))
            ->assertOk()->assertSee('Needs rescheduling')->assertSeeInOrder(['Expired request concern', 'Future request concern']);
    }

    private function requestAt(string $time, ?Patient $patient = null): Appointment
    {
        return Appointment::factory()->for($patient ?? Patient::factory())->create([
            'scheduled_at' => $time, 'status' => 'Pending assignment', 'doctor_name' => 'Clinic assignment pending',
            'department' => 'Awaiting clinic review', 'reason' => 'Check-up',
        ]);
    }
}
