<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PortalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_mark_all_their_unread_notifications_as_read(): void
    {
        $this->freezeTime();
        $patient = Patient::factory()->create();
        $attributes = ['type' => 'reminder', 'title' => 'Visit reminder', 'body' => 'Your appointment is coming up.'];
        PortalNotification::factory()->for($patient)->count(2)->create($attributes);
        $readNotification = PortalNotification::factory()->for($patient)->create([
            ...$attributes, 'read_at' => now()->subDay(),
        ]);
        $otherNotification = PortalNotification::factory()->for(Patient::factory())->create($attributes);

        $this->actingAs($patient->user)->get(route('notifications.index'))
            ->assertOk()->assertSee('Mark all as read')->assertSee('2 unread notifications');

        $this->patch(route('notifications.read-all'))->assertRedirect(route('notifications.index'));

        $this->assertDatabaseMissing('portal_notifications', ['patient_id' => $patient->id, 'read_at' => null]);
        $this->assertDatabaseHas('portal_notifications', ['id' => $readNotification->id, 'read_at' => now()->subDay()->toDateTimeString()]);
        $this->assertDatabaseHas('portal_notifications', ['id' => $otherNotification->id, 'read_at' => null]);
        $this->get(route('notifications.index'))->assertOk()
            ->assertDontSee('Mark all as read')->assertDontSee('unread notifications');
    }

    public function test_mark_all_as_read_succeeds_without_notifications(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->patch(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'));

        $this->assertDatabaseCount('portal_notifications', 0);
        $this->get(route('notifications.index'))->assertOk()->assertDontSee('Mark all as read');
    }

    public function test_guests_cannot_mark_notifications_as_read(): void
    {
        $notification = PortalNotification::factory()->for(Patient::factory())->create([
            'type' => 'reminder', 'title' => 'Visit reminder', 'body' => 'Your appointment is coming up.',
        ]);

        $this->patch(route('notifications.read-all'))->assertRedirect(route('login'));

        $this->assertDatabaseHas('portal_notifications', ['id' => $notification->id, 'read_at' => null]);
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
