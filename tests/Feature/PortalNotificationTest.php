<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_card_marks_it_read_and_returns_its_safe_destination(): void
    {
        $this->freezeTime();
        $patient = Patient::factory()->create();
        $notification = PortalNotification::factory()->for($patient)->create([
            'type' => 'appointment_proposed', 'title' => 'Review proposal', 'body' => '<script>unsafe</script>',
            'target' => ['kind' => 'appointment', 'id' => 25],
        ]);
        $this->actingAs($patient->user)->get(route('notifications.index'))->assertOk()
            ->assertSee('data-open-notification')->assertSee($notification->body)->assertDontSee($notification->body, false);
        $this->assertNull($notification->fresh()->read_at);
        $this->patchJson(route('notifications.read', $notification), ['open' => true, 'url' => 'https://example.com'])
            ->assertOk()->assertJsonPath('url', route('appointments.index', ['appointment' => 25]));
        $this->assertDatabaseHas('portal_notifications', ['id' => $notification->id, 'read_at' => now()->toDateTimeString()]);
        $this->travel(1)->hours();
        $this->patch(route('notifications.read', $notification), ['open' => true])->assertRedirect(route('appointments.index', ['appointment' => 25]));
        $this->assertSame(now()->subHour()->toDateTimeString(), $notification->fresh()->read_at->toDateTimeString());
    }

    public function test_opening_another_patients_notification_is_rejected(): void
    {
        $notification = PortalNotification::factory()->for(Patient::factory())->create([
            'type' => 'appointment_assigned', 'title' => 'Private', 'body' => 'Private appointment',
        ]);
        $this->patchJson(route('notifications.read', $notification), ['open' => true])->assertUnauthorized();
        $this->actingAs(Patient::factory()->create()->user)->patchJson(route('notifications.read', $notification), ['open' => true])->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_legacy_and_unknown_notification_targets_use_local_fallbacks(): void
    {
        $patient = Patient::factory()->create();
        $notification = PortalNotification::factory()->for($patient)->create([
            'type' => 'vital_reading_available', 'title' => 'Measurements', 'body' => 'Updated',
        ]);
        $this->actingAs($patient->user)->patchJson(route('notifications.read', $notification), ['open' => true])
            ->assertOk()->assertJsonPath('url', route('medical-records.index'));
        $notification->update(['type' => 'system_alert', 'target' => ['kind' => 'https://example.com', 'id' => 1]]);
        $this->patchJson(route('notifications.read', $notification), ['open' => true])
            ->assertOk()->assertJsonPath('url', route('notifications.index'));
    }

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

    public function test_notification_feed_only_exposes_the_patients_unread_external_updates(): void
    {
        $patient = Patient::factory()->create();
        $attributes = ['type' => 'appointment_assigned', 'title' => 'Appointment confirmed', 'body' => 'Your visit is confirmed.'];
        $notification = PortalNotification::factory()->for($patient)->create($attributes);
        PortalNotification::factory()->for($patient)->create([...$attributes, 'read_at' => now()]);
        PortalNotification::factory()->for($patient)->create([...$attributes, 'type' => 'appointment_created']);
        $other = PortalNotification::factory()->for(Patient::factory())->create([...$attributes, 'body' => 'Private other patient update']);

        $response = $this->actingAs($patient->user)->getJson(route('notifications.feed'));

        $response->assertOk()->assertJsonPath('unread_count', 2)->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.id', $notification->id)
            ->assertJsonPath('notifications.0.url', route('appointments.index'))
            ->assertJsonMissing(['body' => $other->body]);
        $this->assertDatabaseHas('portal_notifications', ['id' => $notification->id, 'read_at' => null]);
    }

    public function test_notification_cursor_skips_previously_shown_updates_without_marking_them_read(): void
    {
        $patient = Patient::factory()->create();
        $attributes = ['type' => 'system_alert', 'title' => 'Clinic update', 'body' => 'New clinic information.'];
        $seen = PortalNotification::factory()->for($patient)->create($attributes);
        $new = PortalNotification::factory()->for($patient)->create($attributes);

        $this->actingAs($patient->user)->getJson(route('notifications.feed', ['after' => $seen->id]))
            ->assertOk()->assertJsonPath('unread_count', 2)->assertJsonPath('revision', $new->id)
            ->assertJsonCount(1, 'notifications')->assertJsonPath('notifications.0.id', $new->id)
            ->assertJsonPath('notifications.0.url', route('notifications.index'));

        $this->assertDatabaseHas('portal_notifications', ['id' => $seen->id, 'read_at' => null]);
    }

    public function test_notification_feed_is_bounded_and_can_fetch_the_next_batch(): void
    {
        $patient = Patient::factory()->create();
        PortalNotification::factory()->for($patient)->count(21)->create([
            'type' => 'appointment_reminder', 'title' => 'Reminder', 'body' => 'Your visit is coming up.',
        ]);

        $response = $this->actingAs($patient->user)->getJson(route('notifications.feed'))
            ->assertOk()->assertJsonCount(20, 'notifications')->assertJsonPath('unread_count', 21);
        $this->getJson(route('notifications.feed', ['after' => $response->json('notifications.19.id')]))
            ->assertOk()->assertJsonCount(1, 'notifications');
    }

    public function test_feed_rejects_guests_staff_and_invalid_cursors(): void
    {
        $this->getJson(route('notifications.feed'))->assertUnauthorized();
        $this->actingAs(User::factory()->create(['is_staff' => true]))->getJson(route('notifications.feed'))->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson(route('notifications.feed', ['after' => -1]))
            ->assertUnprocessable()->assertJsonValidationErrors('after');
    }

    public function test_patient_without_records_gets_an_empty_feed_without_creating_a_patient(): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('notifications.feed'))
            ->assertOk()->assertExactJson(['unread_count' => 0, 'revision' => 0, 'notifications' => []]);

        $this->assertDatabaseCount('patients', 0);
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
