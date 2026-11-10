<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\PortalNotification;
use App\Models\User;
use App\Models\VitalReading;
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
        foreach ($this->doctors() as $doctor) {
            Doctor::firstOrCreate(['name' => $doctor['name']], $doctor);
        }

        // User::factory(10)->create();

        $user = User::updateOrCreate(['email' => 'patient@example.com'], [
            'name' => 'Maria Santos',
            'password' => bcrypt('password'),
        ]);

        User::updateOrCreate(['email' => 'staff@example.com'], [
            'name' => 'Alex Rivera',
            'password' => bcrypt('password'),
            'is_staff' => true,
        ]);

        $patient = Patient::firstOrCreate(['user_id' => $user->id], [
            'date_of_birth' => '1992-06-15',
            'sex' => 'Female',
            'phone' => '09171234567',
            'city' => 'Davao City',
            'province' => 'Davao del Sur',
        ]);

        foreach ($this->appointments() as $appointment) {
            Appointment::firstOrCreate(
                ['patient_id' => $patient->id, 'scheduled_at' => $appointment['scheduled_at']],
                [...$appointment, 'patient_id' => $patient->id],
            );
        }

        foreach ($this->medicalRecords() as $medicalRecord) {
            MedicalRecord::firstOrCreate(
                ['patient_id' => $patient->id, 'title' => $medicalRecord['title']],
                [...$medicalRecord, 'patient_id' => $patient->id],
            );
        }

        foreach ($this->vitalReadings() as $vitalReading) {
            $appointment = $patient->appointments()->whereDate('scheduled_at', $vitalReading['recorded_on'])->first();

            VitalReading::updateOrCreate(
                ['patient_id' => $patient->id, 'recorded_on' => $vitalReading['recorded_on']],
                [...$vitalReading, 'patient_id' => $patient->id, 'appointment_id' => $appointment?->id],
            );
        }

        foreach ($this->notifications() as $portalNotification) {
            PortalNotification::firstOrCreate(
                ['patient_id' => $patient->id, 'title' => $portalNotification['title']],
                [...$portalNotification, 'patient_id' => $patient->id],
            );
        }
    }

    /** @return array<int, array<string, string>> */
    private function appointments(): array
    {
        return [
            ['scheduled_at' => '2026-11-14 09:00:00', 'doctor_name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'reason' => 'Routine follow-up', 'status' => 'Scheduled'],
            ['scheduled_at' => '2026-11-21 10:30:00', 'doctor_name' => 'Dr. Paolo Cruz', 'department' => 'Dermatology', 'reason' => 'Skin consultation', 'status' => 'Scheduled'],
            ['scheduled_at' => '2026-12-02 14:00:00', 'doctor_name' => 'Dr. Liza Garcia', 'department' => 'Cardiology', 'reason' => 'Blood-pressure review', 'status' => 'Scheduled'],
            ['scheduled_at' => '2026-12-10 08:30:00', 'doctor_name' => 'Dr. Mark Santos', 'department' => 'Dental Clinic', 'reason' => 'Dental cleaning', 'status' => 'Scheduled'],
            ['scheduled_at' => '2026-12-18 13:00:00', 'doctor_name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'reason' => 'Wellness consultation', 'status' => 'Scheduled'],
            ['scheduled_at' => '2026-10-03 09:30:00', 'doctor_name' => 'Dr. Nina Lim', 'department' => 'Ophthalmology', 'reason' => 'Vision screening', 'status' => 'Completed'],
            ['scheduled_at' => '2026-09-11 11:00:00', 'doctor_name' => 'Dr. Paolo Cruz', 'department' => 'Dermatology', 'reason' => 'Follow-up check', 'status' => 'Completed'],
            ['scheduled_at' => '2026-08-22 15:00:00', 'doctor_name' => 'Dr. Liza Garcia', 'department' => 'Cardiology', 'reason' => 'ECG review', 'status' => 'Completed'],
            ['scheduled_at' => '2026-07-16 10:00:00', 'doctor_name' => 'Dr. Maria Dela Cruz', 'department' => 'Laboratory', 'reason' => 'Annual laboratory work', 'status' => 'Completed'],
            ['scheduled_at' => '2026-06-08 08:00:00', 'doctor_name' => 'Dr. Mark Santos', 'department' => 'Dental Clinic', 'reason' => 'Routine oral checkup', 'status' => 'Completed'],
            ['scheduled_at' => '2026-10-28 09:00:00', 'doctor_name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'reason' => 'Vital-signs review', 'status' => 'Completed'],
            ['scheduled_at' => '2026-10-29 09:00:00', 'doctor_name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'reason' => 'Vital-signs review', 'status' => 'Completed'],
            ['scheduled_at' => '2026-10-30 09:00:00', 'doctor_name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'reason' => 'Vital-signs review', 'status' => 'Completed'],
            ['scheduled_at' => '2026-10-31 09:00:00', 'doctor_name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'reason' => 'Vital-signs review', 'status' => 'Completed'],
            ['scheduled_at' => '2026-11-01 09:00:00', 'doctor_name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'reason' => 'Vital-signs review', 'status' => 'Completed'],
            ['scheduled_at' => '2026-11-02 09:00:00', 'doctor_name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'reason' => 'Vital-signs review', 'status' => 'Completed'],
            ['scheduled_at' => '2026-11-03 09:00:00', 'doctor_name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'reason' => 'Vital-signs review', 'status' => 'Completed'],
        ];
    }

    /** @return array<int, array<string, string>> */
    private function doctors(): array
    {
        return [
            ['name' => 'Dr. Ana Reyes', 'department' => 'Internal Medicine', 'specialty' => 'Primary and preventive care', 'availability_note' => 'Weekdays, 9:00 AM to 3:00 PM'],
            ['name' => 'Dr. Paolo Cruz', 'department' => 'Dermatology', 'specialty' => 'Skin, hair, and nail care', 'availability_note' => 'Tuesdays and Thursdays'],
            ['name' => 'Dr. Liza Garcia', 'department' => 'Cardiology', 'specialty' => 'Heart health and blood pressure', 'availability_note' => 'Mondays and Fridays'],
            ['name' => 'Dr. Mark Santos', 'department' => 'Dental Clinic', 'specialty' => 'Preventive dental care', 'availability_note' => 'Weekdays, morning sessions'],
            ['name' => 'Dr. Nina Lim', 'department' => 'Ophthalmology', 'specialty' => 'Eye and vision care', 'availability_note' => 'Wednesdays and Saturdays'],
            ['name' => 'Dr. Maria Dela Cruz', 'department' => 'Laboratory', 'specialty' => 'Diagnostic laboratory services', 'availability_note' => 'Weekdays, 7:00 AM to 4:00 PM'],
        ];
    }

    /** @return array<int, array<string, int|float|string>> */
    private function vitalReadings(): array
    {
        return [
            ['recorded_on' => '2026-10-28', 'heart_rate' => 74, 'temperature' => 36.7, 'systolic' => 118, 'diastolic' => 78],
            ['recorded_on' => '2026-10-29', 'heart_rate' => 71, 'temperature' => 36.8, 'systolic' => 120, 'diastolic' => 80],
            ['recorded_on' => '2026-10-30', 'heart_rate' => 76, 'temperature' => 37.1, 'systolic' => 122, 'diastolic' => 81],
            ['recorded_on' => '2026-10-31', 'heart_rate' => 72, 'temperature' => 36.9, 'systolic' => 116, 'diastolic' => 76],
            ['recorded_on' => '2026-11-01', 'heart_rate' => 69, 'temperature' => 36.6, 'systolic' => 117, 'diastolic' => 77],
            ['recorded_on' => '2026-11-02', 'heart_rate' => 73, 'temperature' => 36.8, 'systolic' => 119, 'diastolic' => 79],
            ['recorded_on' => '2026-11-03', 'heart_rate' => 70, 'temperature' => 36.7, 'systolic' => 115, 'diastolic' => 75],
        ];
    }

    /** @return array<int, array<string, string>> */
    private function medicalRecords(): array
    {
        return [
            ['recorded_on' => '2026-10-03', 'record_type' => 'Consultation', 'provider_name' => 'SegHIS Eye Center', 'title' => 'Vision screening', 'summary' => 'Sample vision screening consultation completed.', 'result_summary' => 'Sample result: routine follow-up in one year.'],
            ['recorded_on' => '2026-09-18', 'record_type' => 'Laboratory result', 'provider_name' => 'SegHIS Diagnostic Center', 'title' => 'Routine blood chemistry', 'summary' => 'Routine screening laboratory test completed.', 'result_summary' => 'Sample result: values are within the expected range.'],
            ['recorded_on' => '2026-08-22', 'record_type' => 'Diagnostic test', 'provider_name' => 'SegHIS Cardiology', 'title' => 'ECG assessment', 'summary' => 'Sample electrocardiogram assessment completed.', 'result_summary' => 'Sample result: no urgent findings recorded.'],
            ['recorded_on' => '2026-07-16', 'record_type' => 'Laboratory result', 'provider_name' => 'SegHIS Diagnostic Center', 'title' => 'Complete blood count', 'summary' => 'Sample complete blood count result recorded.', 'result_summary' => 'Sample result: values are within the expected range.'],
            ['recorded_on' => '2026-06-08', 'record_type' => 'Consultation', 'provider_name' => 'SegHIS Dental Clinic', 'title' => 'Routine oral checkup', 'summary' => 'Sample dental wellness consultation completed.', 'result_summary' => 'Sample result: continue regular oral care.'],
            ['recorded_on' => '2026-05-12', 'record_type' => 'Consultation', 'provider_name' => 'SegHIS Internal Medicine', 'title' => 'Wellness consultation', 'summary' => 'Sample annual wellness consultation recorded.', 'result_summary' => 'Sample result: maintain healthy lifestyle practices.'],
            ['recorded_on' => '2026-04-04', 'record_type' => 'Laboratory result', 'provider_name' => 'SegHIS Diagnostic Center', 'title' => 'Urinalysis', 'summary' => 'Sample urinalysis result recorded.', 'result_summary' => 'Sample result: routine findings only.'],
            ['recorded_on' => '2026-03-15', 'record_type' => 'Consultation', 'provider_name' => 'SegHIS Dermatology', 'title' => 'Skin consultation', 'summary' => 'Sample dermatology consultation recorded.', 'result_summary' => 'Sample result: follow provider guidance.'],
            ['recorded_on' => '2026-02-20', 'record_type' => 'Diagnostic test', 'provider_name' => 'SegHIS Radiology', 'title' => 'Chest X-ray', 'summary' => 'Sample chest X-ray screening completed.', 'result_summary' => 'Sample result: no urgent findings recorded.'],
            ['recorded_on' => '2026-01-10', 'record_type' => 'Consultation', 'provider_name' => 'SegHIS Internal Medicine', 'title' => 'New year health review', 'summary' => 'Sample health review consultation recorded.', 'result_summary' => 'Sample result: routine preventive care advised.'],
        ];
    }

    /** @return array<int, array<string, string>> */
    private function notifications(): array
    {
        return [
            ['type' => 'appointment_reminder', 'title' => 'Appointment reminder: Internal Medicine', 'body' => 'Sample reminder for your November 14 appointment.'],
            ['type' => 'appointment_reminder', 'title' => 'Appointment reminder: Dermatology', 'body' => 'Sample reminder for your November 21 appointment.'],
            ['type' => 'record_ready', 'title' => 'New laboratory result available', 'body' => 'A sample routine blood chemistry result is ready to view.'],
            ['type' => 'record_ready', 'title' => 'New consultation record available', 'body' => 'A sample vision screening record is available.'],
            ['type' => 'system_alert', 'title' => 'Keep your profile updated', 'body' => 'Please confirm your contact and emergency details.'],
            ['type' => 'appointment_reminder', 'title' => 'Appointment reminder: Cardiology', 'body' => 'Sample reminder for your December 2 appointment.'],
            ['type' => 'system_alert', 'title' => 'Portal security reminder', 'body' => 'Do not share your password with anyone.'],
            ['type' => 'record_ready', 'title' => 'Sample ECG record available', 'body' => 'A sample ECG assessment was added to your records.'],
            ['type' => 'appointment_reminder', 'title' => 'Appointment reminder: Dental Clinic', 'body' => 'Sample reminder for your December 10 appointment.'],
            ['type' => 'system_alert', 'title' => 'Welcome to the demo portal', 'body' => 'All information in this portal is dummy data for demonstration.'],
        ];
    }
}
