<?php

namespace App;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AppointmentAvailability
{
    public const DURATION_MINUTES = 30;

    public const OPENING_HOUR = 8;

    public const CLOSING_HOUR = 17;

    public function isWithinClinicHours(CarbonInterface $start): bool
    {
        $localStart = $start->copy()->setTimezone(config('app.timezone'));

        return $localStart->gte($localStart->copy()->startOfDay()->setHour(self::OPENING_HOUR))
            && $localStart->copy()->addMinutes(self::DURATION_MINUTES)->lte($localStart->copy()->startOfDay()->setHour(self::CLOSING_HOUR));
    }

    public function ensureWithinClinicHours(CarbonInterface $start): void
    {
        if (! $this->isWithinClinicHours($start)) {
            throw ValidationException::withMessages(['scheduled_at' => 'Appointments are available from 8:00 AM to 5:00 PM. The last 30-minute visit starts at 4:30 PM.']);
        }
    }

    /** @return Builder<Appointment> */
    public function conflicts(int $doctorId, CarbonInterface $start, ?int $exceptId = null): Builder
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'Scheduled')
            ->where('scheduled_at', '>', $start->copy()->subMinutes(self::DURATION_MINUTES))
            ->where('scheduled_at', '<', $start->copy()->addMinutes(self::DURATION_MINUTES))
            ->when($exceptId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptId));
    }

    /** Must be called inside the transaction that saves the appointment. */
    public function ensurePatientAvailable(int $patientId, CarbonInterface $start, ?int $exceptId = null): void
    {
        Patient::query()->lockForUpdate()->findOrFail($patientId);
        $conflict = Appointment::query()
            ->where('patient_id', $patientId)
            ->whereIn('status', ['Scheduled', 'Pending assignment'])
            ->where('scheduled_at', '>', $start->copy()->subMinutes(self::DURATION_MINUTES))
            ->where('scheduled_at', '<', $start->copy()->addMinutes(self::DURATION_MINUTES))
            ->when($exceptId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptId))
            ->lockForUpdate()->first();

        if ($conflict !== null) {
            throw ValidationException::withMessages(['scheduled_at' => 'This patient already has a pending or confirmed appointment at an overlapping time. Choose another time, or cancel or reschedule the existing appointment.']);
        }
    }

    /** Must be called inside the transaction that saves the appointment. */
    public function ensureAvailable(int $doctorId, CarbonInterface $start, ?int $exceptId = null): Doctor
    {
        $this->ensureWithinClinicHours($start);
        $doctor = Doctor::query()->lockForUpdate()->findOrFail($doctorId);

        if (! $doctor->is_active) {
            throw ValidationException::withMessages(['doctor_id' => 'This doctor is no longer available. Please choose another doctor.']);
        }

        if ($this->conflicts($doctorId, $start, $exceptId)->lockForUpdate()->first() !== null) {
            throw ValidationException::withMessages(['scheduled_at' => 'This doctor already has a visit that overlaps this 30-minute appointment. Please choose another time or doctor.']);
        }

        return $doctor;
    }
}
