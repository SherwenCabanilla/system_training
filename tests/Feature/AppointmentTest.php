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
        $response->assertSeeInOrder(['Upcoming appointments', 'Dr. Ana Reyes', 'Completed appointments', 'Dr. Nina Lim']);
    }
}
