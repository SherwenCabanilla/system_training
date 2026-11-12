<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_appointments_page_separates_upcoming_and_completed_visits(): void
    {
        $user = User::factory()->create();
        $patient = Patient::query()->create(['user_id' => $user->id]);

        $patient->appointments()->create([
            'scheduled_at' => now()->addWeek(),
            'doctor_name' => 'Dr. Ana Reyes',
            'department' => 'Internal Medicine',
            'status' => 'Scheduled',
        ]);
        $patient->appointments()->create([
            'scheduled_at' => now()->subWeek(),
            'doctor_name' => 'Dr. Nina Lim',
            'department' => 'Ophthalmology',
            'status' => 'Completed',
        ]);

        $response = $this->actingAs($user)->get(route('appointments.index'));

        $response->assertOk();
        $response->assertViewHas('upcomingAppointments', fn ($appointments) => $appointments->count() === 1);
        $response->assertViewHas('completedAppointments', fn ($appointments) => $appointments->count() === 1);
        $response->assertSeeInOrder(['Upcoming appointments', 'Dr. Ana Reyes', 'Appointment history', 'Dr. Nina Lim']);
    }

    public function test_patient_can_request_care_with_a_description_without_selecting_a_specialty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('appointments.store'), [
            'booking_mode' => 'clinic_assignment',
            'scheduled_at' => now()->addWeek()->toDateTimeString(),
            'reason' => 'My shoulder has been hurting for three days.',
        ]);

        $response->assertCreated()->assertJsonPath('appointment.status', 'Pending assignment');
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $user->patient->id,
            'doctor_id' => null,
            'reason' => 'My shoulder has been hurting for three days.',
            'department' => 'Awaiting clinic review',
        ]);
        $this->assertDatabaseHas('portal_notifications', [
            'patient_id' => $user->patient->id,
            'type' => 'appointment_assignment_requested',
        ]);
    }

    public function test_clinic_assignment_requires_a_description(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('appointments.store'), [
                'booking_mode' => 'clinic_assignment',
                'scheduled_at' => now()->addWeek()->toDateTimeString(),
                'reason' => '   ',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_patient_can_edit_a_pending_request_without_selecting_a_doctor(): void
    {
        $user = User::factory()->create();
        $patient = Patient::query()->create(['user_id' => $user->id]);
        $appointment = $patient->appointments()->create([
            'scheduled_at' => now()->addWeek(),
            'doctor_name' => 'Clinic assignment pending',
            'department' => 'Awaiting clinic review',
            'reason' => 'Shoulder pain',
            'status' => 'Pending assignment',
        ]);

        $this->actingAs($user)->patchJson(route('appointments.update', $appointment), [
            'scheduled_at' => now()->addWeeks(2)->toDateTimeString(),
            'reason' => 'Shoulder pain for three days',
        ])->assertOk()->assertJsonPath('appointment.status', 'Pending assignment');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_id' => null,
            'reason' => 'Shoulder pain for three days',
        ]);
    }

    public function test_overview_includes_booking_dialog_and_pending_requests(): void
    {
        $user = User::factory()->create();
        $patient = Patient::query()->create(['user_id' => $user->id]);
        $appointment = $patient->appointments()->create([
            'scheduled_at' => now()->addWeek(),
            'doctor_name' => 'Clinic assignment pending',
            'department' => 'Awaiting clinic review',
            'reason' => 'Shoulder pain',
            'status' => 'Pending assignment',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="open-appointment-modal"', false)
            ->assertSee('id="appointment-modal"', false)
            ->assertSee('id="appointment-confirmation-modal"', false)
            ->assertSee('Pending assignment')
            ->assertViewHas('nextAppointments', fn ($appointments) => $appointments->modelKeys() === [$appointment->id])
            ->assertViewHas('doctors');
    }
}
