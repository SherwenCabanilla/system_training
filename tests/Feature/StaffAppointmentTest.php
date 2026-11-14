<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_entry_redirects_to_the_staff_workspace(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_dashboard_prioritizes_overdue_visits_before_todays_queue(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $patient = Patient::query()->create(['user_id' => User::factory()->create()->id]);
        $patient->appointments()->create([
            'scheduled_at' => now()->subDay(),
            'doctor_name' => 'Dr. Ana Reyes',
            'department' => 'Internal Medicine',
            'status' => 'Scheduled',
        ]);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Overdue visit outcomes', "Today's schedule"])
            ->assertSee('Complete &amp; add measurements', false)
            ->assertSee('Mark missed')
            ->assertSee('Cancel visit');
    }

    public function test_staff_can_update_an_appointment_status_and_notify_the_patient(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $patient = Patient::query()->create(['user_id' => User::factory()->create()->id]);
        $appointment = $patient->appointments()->create([
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'doctor_name' => 'Dr. Ana Reyes',
            'department' => 'Internal Medicine',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($staff)->patch(route('staff.appointments.status', $appointment), [
            'status' => 'Completed',
        ]);

        $response->assertRedirect(route('staff.dashboard'));
        $response->assertSessionHas('status', 'Appointment marked Completed.');
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Completed']);
        $this->assertDatabaseHas('portal_notifications', [
            'patient_id' => $patient->id,
            'type' => 'appointment_status_updated',
            'title' => 'Appointment status updated',
        ]);
    }

    public function test_patient_cannot_update_an_appointment_status_from_the_staff_route(): void
    {
        $patientUser = User::factory()->create();
        $patient = Patient::query()->create(['user_id' => $patientUser->id]);
        $appointment = $patient->appointments()->create([
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'doctor_name' => 'Dr. Ana Reyes',
            'department' => 'Internal Medicine',
            'status' => 'Scheduled',
        ]);

        $this->actingAs($patientUser)
            ->patch(route('staff.appointments.status', $appointment), ['status' => 'Completed'])
            ->assertForbidden();
    }

    public function test_staff_can_record_measurements_for_a_passed_scheduled_appointment(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $patient = Patient::query()->create(['user_id' => User::factory()->create()->id]);
        $appointment = $patient->appointments()->create([
            'scheduled_at' => now()->subDay(),
            'doctor_name' => 'Dr. Ana Reyes',
            'department' => 'Internal Medicine',
            'status' => 'Scheduled',
        ]);

        $this->actingAs($staff)
            ->put(route('staff.appointments.vital-reading', $appointment), [
                'recorded_on' => now()->toDateString(),
                'heart_rate' => 72,
                'temperature' => 36.8,
                'systolic' => 118,
                'diastolic' => 76,
            ])
            ->assertRedirect(route('staff.dashboard'))
            ->assertSessionHas('status', 'Visit measurements saved and the patient was notified.');

        $this->assertDatabaseHas('vital_readings', [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'heart_rate' => 72,
            'systolic' => 118,
            'diastolic' => 76,
        ]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Completed']);
        $this->assertDatabaseHas('portal_notifications', [
            'patient_id' => $patient->id,
            'type' => 'vital_reading_available',
            'title' => 'New visit measurements available',
        ]);
    }

    public function test_staff_must_select_a_supported_appointment_status(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $patient = Patient::query()->create(['user_id' => User::factory()->create()->id]);
        $appointment = $patient->appointments()->create([
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'doctor_name' => 'Dr. Ana Reyes',
            'department' => 'Internal Medicine',
            'status' => 'Scheduled',
        ]);

        $this->actingAs($staff)
            ->from(route('staff.dashboard'))
            ->patch(route('staff.appointments.status', $appointment), ['status' => 'Unknown'])
            ->assertRedirect(route('staff.dashboard'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Scheduled']);
    }

    public function test_staff_pages_separate_requests_upcoming_visits_and_history(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $patient = Patient::query()->create(['user_id' => User::factory()->create()->id]);
        $request = $patient->appointments()->create([
            'scheduled_at' => now()->addWeek()->setTime(10, 0),
            'doctor_name' => 'Clinic assignment pending',
            'department' => 'Awaiting clinic review',
            'reason' => '<script>alert("concern")</script>',
            'status' => 'Pending assignment',
        ]);
        $upcoming = $patient->appointments()->create([
            'scheduled_at' => now()->addWeek()->setTime(10, 0),
            'doctor_name' => 'Dr. Future Visit',
            'department' => 'General Medicine',
            'status' => 'Scheduled',
        ]);
        $completed = $patient->appointments()->create([
            'scheduled_at' => now()->subDay(),
            'doctor_name' => 'Dr. Completed Visit',
            'department' => 'General Medicine',
            'status' => 'Completed',
        ]);

        $this->actingAs($staff)->get(route('staff.requests'))
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->modelKeys() === [$request->id])
            ->assertSee($request->reason)
            ->assertDontSee($request->reason, false);
        $this->get(route('staff.upcoming'))
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->modelKeys() === [$upcoming->id]);
        $this->get(route('staff.history'))
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->modelKeys() === [$completed->id])
            ->assertSee('Add measurements');
    }

    public function test_new_staff_pages_require_a_staff_account(): void
    {
        foreach (['staff.requests', 'staff.upcoming', 'staff.history'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }

        $this->actingAs(User::factory()->create());

        foreach (['staff.requests', 'staff.upcoming', 'staff.history'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_assignment_requests_are_paginated(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $patient = Patient::query()->create(['user_id' => User::factory()->create()->id]);
        for ($index = 0; $index < 11; $index++) {
            $patient->appointments()->create([
                'scheduled_at' => now()->addDays($index + 1),
                'doctor_name' => 'Clinic assignment pending',
                'department' => 'Awaiting clinic review',
                'reason' => 'Request '.$index,
                'status' => 'Pending assignment',
            ]);
        }

        $this->actingAs($staff)->get(route('staff.requests'))
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->count() === 10 && $appointments->total() === 11);
        $this->get(route('staff.requests', ['page' => 2]))
            ->assertOk()
            ->assertSee('Request 10')
            ->assertViewHas('appointments', fn ($appointments) => $appointments->count() === 1);
    }

    public function test_staff_can_propose_a_new_time_through_ajax_and_notify_the_patient(): void
    {
        $appointment = $this->pendingRequest();
        $doctor = $this->assignmentDoctor();
        $time = now()->addWeek()->setTime(14, 30);

        $this->actingAs(User::factory()->create(['is_staff' => true]))
            ->patchJson(route('staff.appointments.assignment', $appointment), [
                'doctor_id' => $doctor->id, 'scheduled_at' => $time->toDateTimeString(),
            ])->assertOk()->assertJsonPath('appointment_id', $appointment->id);

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'doctor_id' => $doctor->id, 'status' => 'Awaiting patient approval', 'scheduled_at' => $time->toDateTimeString()]);
        $this->assertDatabaseHas('portal_notifications', ['patient_id' => $appointment->patient_id, 'type' => 'appointment_proposed']);
        $this->get(route('staff.requests'))->assertOk()->assertSee('Awaiting patient approval');
    }

    public function test_staff_availability_filters_doctor_and_patient_conflicts(): void
    {
        $appointment = $this->pendingRequest();
        $doctor = $this->assignmentDoctor();
        $otherPatient = $this->pendingRequest();
        $otherPatient->update(['doctor_id' => $doctor->id, 'status' => 'Scheduled', 'scheduled_at' => $appointment->scheduled_at->copy()->setTime(11, 0)]);
        $samePatient = $this->pendingRequest();
        $samePatient->patient()->associate($appointment->patient)->save();
        $samePatient->update(['scheduled_at' => $appointment->scheduled_at->copy()->setTime(12, 0)]);

        $this->actingAs(User::factory()->create(['is_staff' => true]))
            ->getJson(route('staff.appointments.availability', ['appointment' => $appointment, 'doctor_id' => $doctor->id, 'date' => $appointment->scheduled_at->toDateString()]))
            ->assertOk()->assertJsonFragment(['label' => '10:00 AM'])
            ->assertJsonMissing(['label' => '11:00 AM'])->assertJsonMissing(['label' => '12:00 PM']);
        $this->patchJson(route('staff.appointments.assignment', $appointment), ['doctor_id' => $doctor->id, 'scheduled_at' => $otherPatient->scheduled_at->toDateTimeString()])
            ->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Pending assignment', 'scheduled_at' => $appointment->scheduled_at->toDateTimeString()]);
        $this->assertDatabaseCount('portal_notifications', 0);
    }

    public function test_patient_accounts_cannot_access_staff_availability_or_assign_requests(): void
    {
        $appointment = $this->pendingRequest();
        $doctor = $this->assignmentDoctor();
        $url = route('staff.appointments.availability', ['appointment' => $appointment, 'doctor_id' => $doctor->id, 'date' => $appointment->scheduled_at->toDateString()]);

        $this->getJson($url)->assertUnauthorized();
        $this->actingAs($appointment->patient->user)->getJson($url)->assertForbidden();
        $this->patchJson(route('staff.appointments.assignment', $appointment), ['doctor_id' => $doctor->id])->assertForbidden();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Pending assignment']);
        $this->assertDatabaseCount('portal_notifications', 0);
    }

    public function test_confirming_an_already_assigned_request_does_not_notify_twice(): void
    {
        $appointment = $this->pendingRequest();
        $doctor = $this->assignmentDoctor();
        $staff = User::factory()->create(['is_staff' => true]);
        $this->actingAs($staff)->patchJson(route('staff.appointments.assignment', $appointment), ['doctor_id' => $doctor->id])->assertOk();

        $this->patchJson(route('staff.appointments.assignment', $appointment), ['doctor_id' => $doctor->id])->assertUnprocessable();
        $this->getJson(route('staff.appointments.availability', ['appointment' => $appointment, 'doctor_id' => $doctor->id, 'date' => $appointment->scheduled_at->toDateString()]))->assertUnprocessable();

        $this->assertDatabaseCount('portal_notifications', 1);
    }

    public function test_expired_requests_need_a_future_time_before_confirmation(): void
    {
        $appointment = $this->pendingRequest();
        $appointment->update(['scheduled_at' => now()->subDay()->setTime(10, 0)]);
        $doctor = $this->assignmentDoctor();

        $this->actingAs(User::factory()->create(['is_staff' => true]))
            ->patchJson(route('staff.appointments.assignment', $appointment), ['doctor_id' => $doctor->id])
            ->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->patchJson(route('staff.appointments.assignment', $appointment), [
            'doctor_id' => $doctor->id, 'scheduled_at' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
        ])->assertOk();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Awaiting patient approval']);
    }

    private function pendingRequest(): Appointment
    {
        return Appointment::factory()->for(Patient::factory())->create([
            'scheduled_at' => now()->addWeek()->setTime(10, 0), 'doctor_name' => 'Clinic assignment pending',
            'department' => 'Awaiting clinic review', 'reason' => 'Shoulder pain', 'status' => 'Pending assignment',
        ]);
    }

    private function assignmentDoctor(): Doctor
    {
        return Doctor::factory()->create(['name' => 'Dr. Reyes', 'department' => 'General Medicine', 'specialty' => 'General Medicine', 'is_active' => true]);
    }
}
