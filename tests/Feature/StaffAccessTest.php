<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_user_can_view_the_staff_dashboard(): void
    {
        $staffUser = User::factory()->create(['is_staff' => true]);

        $this->actingAs($staffUser)->get(route('staff.dashboard'))->assertOk();
    }

    public function test_patient_user_cannot_view_the_staff_dashboard(): void
    {
        $patientUser = User::factory()->create();

        $this->actingAs($patientUser)->get(route('staff.dashboard'))->assertForbidden();
    }
}
