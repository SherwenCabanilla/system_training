<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
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

    public function test_medical_records_dashboard_provides_record_summary_data(): void
    {
        $user = User::factory()->create();
        $patient = Patient::query()->create(['user_id' => $user->id]);
        $patient->medicalRecords()->create([
            'recorded_on' => '2026-11-01',
            'record_type' => 'Laboratory result',
            'provider_name' => 'SegHIS Diagnostic Center',
            'title' => 'Routine blood chemistry',
            'summary' => 'Sample laboratory result.',
            'result_summary' => 'Within the expected range.',
        ]);

        $response = $this->actingAs($user)->get(route('medical-records.index'));

        $response->assertOk();
        $response->assertViewHas('latestMedicalRecord');
        $response->assertViewHas('resultCount', 1);
        $response->assertSee('Routine blood chemistry');
        $response->assertSee('Laboratory result');
    }
}
