<?php

namespace Database\Seeders;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::firstOrCreate(['email' => 'patient@example.com'], [
            'name' => 'Maria Santos',
            'password' => bcrypt('password'),
        ]);

        $patient = Patient::firstOrCreate(['user_id' => $user->id], [
            'date_of_birth' => '1992-06-15',
            'sex' => 'Female',
            'phone' => '09171234567',
            'city' => 'Davao City',
            'province' => 'Davao del Sur',
        ]);

        MedicalRecord::firstOrCreate(['patient_id' => $patient->id, 'title' => 'Routine blood chemistry'], [
            'recorded_on' => '2026-09-18',
            'record_type' => 'Laboratory result',
            'provider_name' => 'SegHIS Diagnostic Center',
            'title' => 'Routine blood chemistry',
            'summary' => 'Routine screening laboratory test completed.',
            'result_summary' => 'Sample result: values are within the expected range.',
        ]);

        PortalNotification::firstOrCreate(['patient_id' => $patient->id, 'title' => 'Appointment reminder'], [
            'type' => 'appointment_reminder',
            'body' => 'This is a sample reminder. Please review your upcoming appointments.',
        ]);
    }
}
