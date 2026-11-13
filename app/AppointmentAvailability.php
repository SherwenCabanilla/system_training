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

    /** @return list<array{value: string, label: string}> */
    public function slots(?int $patientId, ?int $doctorId, CarbonInterface $date, ?Appointment $editingAppointment = null): array
    {
        $day = $date->copy()->startOfDay();
        $exceptId = $editingAppointment?->id;
        $busy = Appointment::query()
            ->where(function (Builder $query) use ($patientId, $doctorId): void {
                $query->where(function (Builder $query) use ($patientId): void {
                    $query->where('patient_id', $patientId)->whereIn('status', ['Scheduled', 'Pending assignment']);
                });
                if ($doctorId !== null) {
                    $query->orWhere(function (Builder $query) use ($doctorId): void {
                        $query->where('doctor_id', $doctorId)->where('status', 'Scheduled');
                    });
                }
            })
            ->where('scheduled_at', '>', $day->copy()->subMinutes(self::DURATION_MINUTES))
            ->where('scheduled_at', '<', $day->copy()->addDay()->addMinutes(self::DURATION_MINUTES))
            ->when($exceptId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptId))
            ->get(['scheduled_at']);
        $isActive = $doctorId === null || Doctor::query()->whereKey($doctorId)->where('is_active', true)->exists();
        $starts = [];
        for ($minute = self::OPENING_HOUR * 60; $minute <= self::CLOSING_HOUR * 60 - self::DURATION_MINUTES && $isActive; $minute += self::DURATION_MINUTES) {
            $starts[] = $day->copy()->addMinutes($minute);
        }
        if ($isActive && $editingAppointment?->scheduled_at->isSameDay($day)) {
            $starts[] = $editingAppointment->scheduled_at;
        }
        $slots = [];
        foreach (collect($starts)->unique(fn (CarbonInterface $start): string => $start->format('H:i'))->sort() as $start) {
            $overlaps = $busy->contains(fn (Appointment $visit): bool => $visit->scheduled_at->gt($start->copy()->subMinutes(self::DURATION_MINUTES))
                && $visit->scheduled_at->lt($start->copy()->addMinutes(self::DURATION_MINUTES)));
            if ($start->isFuture() && $this->isWithinClinicHours($start) && ! $overlaps) {
                $slots[] = ['value' => $start->format('Y-m-d\TH:i'), 'label' => $start->format('g:i A')];
            }
        }

        return $slots;
    }

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
