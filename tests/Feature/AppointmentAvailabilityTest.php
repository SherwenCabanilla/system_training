<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public static function overlappingTimes(): array
    {
        return [['09:45'], ['10:00'], ['10:15']];
    }

    #[DataProvider('overlappingTimes')]
    public function test_overlapping_bookings_are_rejected(string $time): void
    {
        $this->freezeTime();
        $doctor = $this->doctor();
        $this->visit($doctor);
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->postJson(route('appointments.store'), [
            'booking_mode' => 'choose_doctor', 'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addWeek()->format('Y-m-d').' '.$time,
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('portal_notifications', 0);
    }

    public static function adjacentTimes(): array
    {
        return [['09:30'], ['10:30']];
    }

    #[DataProvider('adjacentTimes')]
    public function test_back_to_back_visits_are_allowed(string $time): void
    {
        $this->freezeTime();
        $doctor = $this->doctor();
        $this->visit($doctor);
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->postJson(route('appointments.store'), [
            'booking_mode' => 'choose_doctor', 'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addWeek()->format('Y-m-d').' '.$time,
        ])->assertCreated();

        $this->assertDatabaseCount('appointments', 2);
        $this->assertDatabaseCount('portal_notifications', 1);
    }

    public function test_different_doctors_can_accept_the_same_time(): void
    {
        $this->visit($this->doctor());
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->postJson(route('appointments.store'), [
            'booking_mode' => 'choose_doctor', 'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addWeek()->setTime(10, 0)->toDateTimeString(),
        ])->assertCreated();

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_rescheduling_rejects_conflicts_and_allows_keeping_the_current_time(): void
    {
        $doctor = $this->doctor();
        $this->visit($doctor);
        $visit = $this->visit($doctor, '11:00');
        $payload = ['doctor_id' => $doctor->id, 'scheduled_at' => $visit->scheduled_at->toDateTimeString()];

        $this->actingAs($visit->patient->user)->patchJson(route('appointments.update', $visit), $payload)->assertOk();
        $this->patchJson(route('appointments.update', $visit), [
            ...$payload, 'scheduled_at' => now()->addWeek()->setTime(10, 15)->toDateTimeString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseHas('appointments', ['id' => $visit->id, 'scheduled_at' => $payload['scheduled_at']]);
        $this->assertDatabaseCount('portal_notifications', 1);
    }

    public function test_assignment_rejects_busy_doctors_and_accepts_a_free_doctor(): void
    {
        $doctor = $this->doctor();
        $this->visit($doctor);
        $pending = $this->visit($doctor, '10:00', 'Pending assignment');
        $pending->update(['doctor_id' => null]);
        $staff = User::factory()->create(['is_staff' => true]);

        $this->actingAs($staff)->from(route('staff.requests'))->patch(route('staff.appointments.assignment', $pending), ['doctor_id' => $doctor->id])
            ->assertRedirect(route('staff.requests'))->assertSessionHasErrors('scheduled_at');

        $this->assertDatabaseHas('appointments', ['id' => $pending->id, 'status' => 'Pending assignment', 'doctor_id' => null]);
        $this->assertDatabaseCount('portal_notifications', 0);
        $freeDoctor = $this->doctor();
        $this->patch(route('staff.appointments.assignment', $pending), ['doctor_id' => $freeDoctor->id])->assertRedirect(route('staff.requests'));
        $this->assertDatabaseHas('appointments', ['id' => $pending->id, 'status' => 'Scheduled', 'doctor_id' => $freeDoctor->id]);
    }

    public function test_cancellation_releases_a_slot_and_restoring_cannot_double_book_it(): void
    {
        $doctor = $this->doctor();
        $visit = $this->visit($doctor);
        $staff = User::factory()->create(['is_staff' => true]);
        $this->actingAs($staff)->patch(route('staff.appointments.status', $visit), ['status' => 'Cancelled'])->assertRedirect();
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->postJson(route('appointments.store'), [
            'booking_mode' => 'choose_doctor', 'doctor_id' => $doctor->id, 'scheduled_at' => $visit->scheduled_at->toDateTimeString(),
        ])->assertCreated();
        $this->actingAs($staff)->patchJson(route('staff.appointments.status', $visit), ['status' => 'Scheduled'])
            ->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseHas('appointments', ['id' => $visit->id, 'status' => 'Cancelled']);
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_available_times_hide_overlaps_and_protect_other_patients(): void
    {
        $doctor = $this->doctor();
        $visit = $this->visit($doctor, '10:15');
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->getJson(route('appointments.availability', [
            'date' => $visit->scheduled_at->toDateString(), 'doctor_id' => $doctor->id,
        ]))->assertOk()->assertJsonStructure(['slots' => [['value', 'label']]])
            ->assertJsonMissing(['label' => '10:00 AM'])->assertJsonMissing(['label' => '10:30 AM'])
            ->assertJsonFragment(['label' => '9:30 AM'])->assertJsonFragment(['label' => '11:00 AM'])
            ->assertDontSee($visit->patient->user->email);
        $this->getJson(route('appointments.availability', [
            'date' => $visit->scheduled_at->toDateString(), 'doctor_id' => $doctor->id, 'appointment_id' => $visit->id,
        ]))->assertNotFound();
    }

    public function test_availability_excludes_the_patients_own_visit_when_editing(): void
    {
        $doctor = $this->doctor();
        $visit = $this->visit($doctor);

        $this->actingAs($visit->patient->user)->getJson(route('appointments.availability', [
            'date' => $visit->scheduled_at->toDateString(), 'doctor_id' => $doctor->id, 'appointment_id' => $visit->id,
        ]))->assertOk()->assertJsonFragment(['label' => '10:00 AM']);
    }

    public function test_pending_requests_do_not_reserve_a_doctor(): void
    {
        $this->visit($this->doctor());
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->postJson(route('appointments.store'), [
            'booking_mode' => 'clinic_assignment', 'scheduled_at' => now()->addWeek()->setTime(10, 0)->toDateTimeString(),
            'reason' => 'Shoulder pain',
        ])->assertCreated()->assertJsonPath('appointment.status', 'Pending assignment');

        $this->assertDatabaseHas('appointments', ['patient_id' => $patient->id, 'doctor_id' => null, 'status' => 'Pending assignment']);
    }

    public function test_inactive_doctors_have_no_slots_and_cannot_be_booked(): void
    {
        $doctor = $this->doctor();
        $doctor->update(['is_active' => false]);
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->getJson(route('appointments.availability', [
            'date' => now()->addWeek()->toDateString(), 'doctor_id' => $doctor->id,
        ]))->assertOk()->assertExactJson(['slots' => []]);
        $this->postJson(route('appointments.store'), [
            'booking_mode' => 'choose_doctor', 'doctor_id' => $doctor->id, 'scheduled_at' => now()->addWeek()->setTime(10, 0)->toDateTimeString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('doctor_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_availability_requires_authentication(): void
    {
        $this->getJson(route('appointments.availability', ['date' => now()->addWeek()->toDateString()]))->assertUnauthorized();
    }

    public function test_cross_midnight_visits_block_the_next_days_overlapping_slot(): void
    {
        $doctor = $this->doctor();
        $visit = $this->visit($doctor, '23:45');
        $patient = Patient::factory()->create();
        $nextDay = $visit->scheduled_at->copy()->addDay()->startOfDay();

        $this->actingAs($patient->user)->getJson(route('appointments.availability', [
            'date' => $nextDay->toDateString(), 'doctor_id' => $doctor->id,
        ]))->assertOk()->assertJsonMissing(['label' => '12:00 AM'])->assertJsonFragment(['label' => '8:00 AM']);
        $this->postJson(route('appointments.store'), [
            'booking_mode' => 'choose_doctor', 'doctor_id' => $doctor->id, 'scheduled_at' => $nextDay->toDateTimeString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_existing_off_grid_time_is_available_when_editing(): void
    {
        $doctor = $this->doctor();
        $visit = $this->visit($doctor, '10:15');

        $this->actingAs($visit->patient->user)->getJson(route('appointments.availability', [
            'date' => $visit->scheduled_at->toDateString(), 'doctor_id' => $doctor->id, 'appointment_id' => $visit->id,
        ]))->assertOk()->assertJsonFragment(['label' => '10:15 AM']);
    }

    public function test_editing_a_late_visit_checks_conflicts_on_the_following_day(): void
    {
        $doctor = $this->doctor();
        $visit = $this->visit($doctor, '23:45');
        $nextVisit = $this->visit($doctor);
        $nextVisit->update(['scheduled_at' => $visit->scheduled_at->copy()->addDay()->startOfDay()]);

        $this->actingAs($visit->patient->user)->getJson(route('appointments.availability', [
            'date' => $visit->scheduled_at->toDateString(), 'doctor_id' => $doctor->id, 'appointment_id' => $visit->id,
        ]))->assertOk()->assertJsonMissing(['label' => '11:45 PM'])->assertJsonFragment(['label' => '4:30 PM']);
    }

    public function test_today_only_offers_future_slots_and_invalid_dates_are_rejected(): void
    {
        $this->travelTo(now()->setTime(10, 15));
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->getJson(route('appointments.availability', ['date' => now()->toDateString()]))
            ->assertOk()->assertJsonMissing(['label' => '10:00 AM'])->assertJsonFragment(['label' => '10:30 AM']);
        $this->getJson(route('appointments.availability', ['date' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('date');
    }

    public static function outsideClinicHours(): array
    {
        return [['07:59'], ['16:31'], ['17:00'], ['23:00']];
    }

    #[DataProvider('outsideClinicHours')]
    public function test_requests_without_a_doctor_cannot_be_booked_outside_clinic_hours(string $time): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->postJson(route('appointments.store'), [
            'booking_mode' => 'clinic_assignment', 'reason' => 'Check-up',
            'scheduled_at' => now()->addWeek()->format('Y-m-d').' '.$time,
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('portal_notifications', 0);
    }

    public function test_available_slots_start_at_eight_and_end_at_four_thirty(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->getJson(route('appointments.availability', ['date' => now()->addWeek()->toDateString()]))
            ->assertOk()->assertJsonCount(18, 'slots')
            ->assertJsonPath('slots.0.label', '8:00 AM')->assertJsonPath('slots.17.label', '4:30 PM');
    }

    public function test_rescheduling_and_staff_assignment_enforce_clinic_hours(): void
    {
        $doctor = $this->doctor();
        $visit = $this->visit($doctor);
        $this->actingAs($visit->patient->user)->patchJson(route('appointments.update', $visit), [
            'doctor_id' => $doctor->id, 'scheduled_at' => now()->addWeek()->setTime(17, 0)->toDateTimeString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->assertDatabaseHas('appointments', ['id' => $visit->id, 'scheduled_at' => $visit->scheduled_at->toDateTimeString()]);
        $pending = $this->visit($doctor, '07:00', 'Pending assignment');
        $pending->update(['doctor_id' => null]);

        $this->actingAs(User::factory()->create(['is_staff' => true]))
            ->patchJson(route('staff.appointments.assignment', $pending), ['doctor_id' => $doctor->id])
            ->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseHas('appointments', ['id' => $pending->id, 'status' => 'Pending assignment', 'doctor_id' => null]);
        $this->assertDatabaseCount('portal_notifications', 0);
    }

    public static function clinicBoundaries(): array
    {
        return [['08:00'], ['16:30']];
    }

    #[DataProvider('clinicBoundaries')]
    public function test_first_and_last_appointments_can_be_booked(string $time): void
    {
        $patient = Patient::factory()->create();
        $doctor = $this->doctor();

        $this->actingAs($patient->user)->postJson(route('appointments.store'), [
            'booking_mode' => 'choose_doctor', 'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addWeek()->format('Y-m-d').' '.$time,
        ])->assertCreated();

        $this->assertDatabaseCount('appointments', 1);
    }

    private function doctor(): Doctor
    {
        return Doctor::factory()->create(['name' => 'Dr. Reyes', 'department' => 'General Medicine', 'specialty' => 'General Medicine', 'is_active' => true]);
    }

    private function visit(Doctor $doctor, string $time = '10:00', string $status = 'Scheduled'): Appointment
    {
        return Appointment::factory()->for(Patient::factory())->create([
            'doctor_id' => $doctor->id, 'doctor_name' => $doctor->name, 'department' => $doctor->department,
            'scheduled_at' => now()->addWeek()->format('Y-m-d').' '.$time, 'status' => $status,
        ]);
    }
}
