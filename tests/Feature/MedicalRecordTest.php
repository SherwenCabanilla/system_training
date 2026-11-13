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

    public function test_chart_tabs_share_the_patients_recent_measurements(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->for($user)->create();
        $patient->vitalReadings()->create([
            'recorded_on' => '2026-11-01', 'heart_rate' => 72, 'temperature' => 36.7, 'systolic' => 120, 'diastolic' => 80,
        ]);
        $patient->vitalReadings()->create([
            'recorded_on' => '2026-11-02', 'heart_rate' => 110, 'temperature' => 38.2, 'systolic' => 145, 'diastolic' => 90,
        ]);

        $response = $this->actingAs($user)->get(route('medical-records.index'));

        $response->assertOk()->assertSee('trend-tab-heart_rate')->assertSee('trend-tab-temperature')
            ->assertSee('trend-tab-blood_pressure')->assertSee('View exact readings');
        preg_match("/data-readings='([^']*)'/", $response->getContent(), $matches);
        $readings = json_decode(html_entity_decode($matches[1]), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(2, $readings);
        $this->assertSame(['Nov 1, 2026', 'Nov 2, 2026'], array_column($readings, 'date'));
        $this->assertSame(110, $readings[1]['heart_rate']);
        $this->assertSame(38.2, (float) $readings[1]['temperature']);
        $this->assertSame(145, $readings[1]['systolic']);
        $this->assertSame(90, $readings[1]['diastolic']);
    }
}
