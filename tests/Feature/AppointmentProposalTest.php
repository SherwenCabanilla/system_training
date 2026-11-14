<?php

namespace Tests\Feature;

use App\AppointmentAvailability;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentProposalTest extends TestCase
{
    use RefreshDatabase;

    private function proposal(): Appointment
    {
        $this->freezeTime();
        $appointment = Appointment::factory()->for(Patient::factory())->create([
            'scheduled_at' => now()->addDays(3)->setTime(10, 0), 'status' => 'Pending assignment',
            'doctor_name' => 'Clinic assignment pending', 'department' => 'Awaiting clinic review', 'reason' => 'Review',
        ]);
        $doctor = Doctor::factory()->create(['name' => 'Dr. Reyes', 'department' => 'General Medicine', 'specialty' => 'General Medicine', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['is_staff' => true]))
            ->patchJson(route('staff.appointments.assignment', $appointment), [
                'doctor_id' => $doctor->id, 'scheduled_at' => now()->addDays(3)->setTime(11, 0)->toDateTimeString(),
            ])->assertOk();

        return $appointment->refresh();
    }

    public function test_proposal_shows_original_details_and_acceptance_confirms_once(): void
    {
        $appointment = $this->proposal();
        $token = $appointment->proposal_token;
        $this->assertSame('Awaiting patient approval', $appointment->status);
        $this->assertSame(now()->addDay()->toDateTimeString(), $appointment->proposal_expires_at->toDateTimeString());
        $this->actingAs($appointment->patient->user)->get(route('appointments.index'))->assertOk()
            ->assertSee('Originally requested')->assertSee('10:00 AM')->assertSee('11:00 AM')->assertSee('Accept schedule');
        $this->getJson(route('notifications.feed'))->assertOk()->assertJsonFragment(['url' => route('appointments.index', ['appointment' => $appointment->id])]);
        $notification = $appointment->patient->portalNotifications()->first();
        $this->patchJson(route('notifications.read', $notification), ['open' => true])->assertOk();
        $this->assertSame('Awaiting patient approval', $appointment->fresh()->status);
        $this->patchJson(route('appointments.proposal', $appointment), ['decision' => 'accept', 'proposal_token' => $token])->assertOk();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Scheduled', 'proposal_expires_at' => null]);
        $this->patchJson(route('appointments.proposal', $appointment), ['decision' => 'accept', 'proposal_token' => $token])->assertConflict();
        $this->assertDatabaseCount('portal_notifications', 2);
    }

    public function test_held_slot_blocks_same_doctor_and_patient_but_not_another_doctor(): void
    {
        $appointment = $this->proposal();
        $other = Patient::factory()->create();
        $otherDoctor = Doctor::factory()->create(['name' => 'Dr. Reyes', 'department' => 'General Medicine', 'specialty' => 'General Medicine', 'is_active' => true]);
        $payload = ['booking_mode' => 'choose_doctor', 'doctor_id' => $appointment->doctor_id, 'scheduled_at' => $appointment->scheduled_at->toDateTimeString()];
        $this->actingAs($other->user)->getJson(route('appointments.availability', ['doctor_id' => $appointment->doctor_id, 'date' => $appointment->scheduled_at->toDateString()]))
            ->assertOk()->assertJsonMissing(['label' => '11:00 AM']);
        $this->postJson(route('appointments.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $payload['doctor_id'] = $otherDoctor->id;
        $this->actingAs($appointment->patient->user)->postJson(route('appointments.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->actingAs($other->user)->postJson(route('appointments.store'), $payload)->assertCreated();
    }

    public function test_decline_restores_request_and_releases_hold(): void
    {
        $appointment = $this->proposal();
        $this->actingAs($appointment->patient->user)->patchJson(route('appointments.proposal', $appointment), [
            'decision' => 'decline', 'proposal_token' => $appointment->proposal_token,
        ])->assertOk();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Pending assignment', 'doctor_id' => null, 'scheduled_at' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(), 'proposal_token' => null]);
        $this->getJson(route('appointments.availability', ['doctor_id' => $appointment->doctor_id, 'date' => $appointment->scheduled_at->toDateString()]))->assertOk()->assertJsonFragment(['label' => '11:00 AM']);
        $this->assertDatabaseHas('portal_notifications', ['type' => 'appointment_proposal_declined', 'patient_id' => $appointment->patient_id]);
    }

    public function test_cancellation_moves_proposal_to_history(): void
    {
        $appointment = $this->proposal();
        $this->actingAs($appointment->patient->user)->patchJson(route('appointments.proposal', $appointment), [
            'decision' => 'cancel', 'proposal_token' => $appointment->proposal_token,
        ])->assertOk();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Cancelled', 'proposal_expires_at' => null]);
        $this->get(route('appointments.index'))->assertOk()->assertViewHas('completedAppointments', fn ($items) => $items->contains('id', $appointment->id));
    }

    public function test_expired_proposal_cannot_be_accepted_and_returns_to_review_once(): void
    {
        $appointment = $this->proposal();
        $this->travel(24)->hours();
        $this->actingAs($appointment->patient->user)->patchJson(route('appointments.proposal', $appointment), [
            'decision' => 'accept', 'proposal_token' => $appointment->proposal_token,
        ])->assertConflict();
        $this->artisan('appointments:expire-proposals')->assertSuccessful();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Pending assignment', 'proposal_token' => null]);
        $this->assertDatabaseCount('portal_notifications', 2);
    }

    public function test_expiry_command_releases_the_hold_without_a_patient_visit(): void
    {
        $appointment = $this->proposal();
        $this->travel(24)->hours();
        $this->artisan('appointments:expire-proposals')->assertSuccessful();
        $this->artisan('appointments:expire-proposals')->assertSuccessful();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Pending assignment']);
        $this->assertDatabaseCount('portal_notifications', 2);
    }

    public function test_responses_require_ownership_and_current_token(): void
    {
        $appointment = $this->proposal();
        $payload = ['decision' => 'accept', 'proposal_token' => $appointment->proposal_token];
        auth()->logout();
        $this->patchJson(route('appointments.proposal', $appointment), $payload)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->patchJson(route('appointments.proposal', $appointment), $payload)->assertNotFound();
        $this->actingAs($appointment->patient->user)->patchJson(route('staff.appointments.proposal', $appointment), $payload)->assertForbidden();
        $payload['proposal_token'] = '00000000-0000-4000-8000-000000000000';
        $this->patchJson(route('appointments.proposal', $appointment), $payload)->assertConflict();
        $this->patchJson(route('appointments.proposal', $appointment), [])->assertUnprocessable()->assertJsonValidationErrors(['decision', 'proposal_token']);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Awaiting patient approval']);
        $this->assertDatabaseCount('portal_notifications', 1);
    }

    public function test_staff_must_explicitly_record_phone_acceptance_and_cannot_bypass_approval(): void
    {
        $appointment = $this->proposal();
        $payload = ['decision' => 'accept', 'proposal_token' => $appointment->proposal_token];
        $this->patchJson(route('staff.appointments.status', $appointment), ['status' => 'Scheduled'])->assertUnprocessable();
        $this->patchJson(route('staff.appointments.proposal', $appointment), $payload)->assertUnprocessable()->assertJsonValidationErrors('phone_acceptance');
        $this->patchJson(route('staff.appointments.proposal', $appointment), [...$payload, 'phone_acceptance' => true])->assertOk();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Scheduled']);
        $this->assertStringContainsString('acceptance by phone', $appointment->patient->portalNotifications()->latest('id')->first()->body);
    }

    public function test_patient_cannot_edit_or_use_stale_cancel_while_approval_is_pending(): void
    {
        $appointment = $this->proposal();
        $this->actingAs($appointment->patient->user)->patchJson(route('appointments.update', $appointment), [
            'doctor_id' => $appointment->doctor_id, 'scheduled_at' => $appointment->scheduled_at->toDateTimeString(),
        ])->assertUnprocessable();
        $this->deleteJson(route('appointments.destroy', $appointment))->assertConflict();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Awaiting patient approval']);
    }

    public function test_near_term_proposals_expire_at_the_visit_start_and_ignore_expired_holds(): void
    {
        $appointment = $this->proposal();
        $this->actingAs($appointment->patient->user)->patchJson(route('appointments.proposal', $appointment), [
            'decision' => 'decline', 'proposal_token' => $appointment->proposal_token,
        ])->assertOk();
        $this->travelTo(now()->startOfDay()->setTime(8, 0));
        $time = now()->setTime(10, 0);
        $this->actingAs(User::factory()->create(['is_staff' => true]))->patchJson(route('staff.appointments.assignment', $appointment), [
            'doctor_id' => $appointment->doctor_id, 'scheduled_at' => $time->toDateTimeString(),
        ])->assertOk();
        $this->assertSame($time->toDateTimeString(), $appointment->refresh()->proposal_expires_at->toDateTimeString());
        $this->travelTo($time);
        $this->assertFalse(app(AppointmentAvailability::class)->conflicts($appointment->doctor_id, $time)->exists());
        $this->actingAs($appointment->patient->user)->get(route('appointments.index'))->assertOk();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Pending assignment']);
    }

    public function test_changing_a_requested_doctor_requires_approval_even_at_the_same_time(): void
    {
        $appointment = $this->proposal();
        $originalDoctor = $appointment->doctor_id;
        $this->actingAs($appointment->patient->user)->patchJson(route('appointments.proposal', $appointment), [
            'decision' => 'decline', 'proposal_token' => $appointment->proposal_token,
        ])->assertOk();
        $appointment->refresh()->update(['doctor_id' => $originalDoctor, 'doctor_name' => 'Requested doctor']);
        $replacement = Doctor::factory()->create(['name' => 'Replacement doctor', 'department' => 'General Medicine', 'specialty' => 'General Medicine', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['is_staff' => true]))->patchJson(route('staff.appointments.assignment', $appointment), [
            'doctor_id' => $replacement->id,
        ])->assertOk();
        $this->assertSame('Awaiting patient approval', $appointment->refresh()->status);
        $this->assertSame($originalDoctor, $appointment->requested_details['doctor_id']);
    }

    public function test_acceptance_rechecks_doctor_availability(): void
    {
        $appointment = $this->proposal();
        $appointment->doctor->update(['is_active' => false]);
        $this->actingAs($appointment->patient->user)->patchJson(route('appointments.proposal', $appointment), [
            'decision' => 'accept', 'proposal_token' => $appointment->proposal_token,
        ])->assertUnprocessable()->assertJsonValidationErrors('doctor_id');
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Awaiting patient approval']);
        $this->assertDatabaseCount('portal_notifications', 1);
    }
}
