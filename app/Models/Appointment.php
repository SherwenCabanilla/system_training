<?php

namespace App\Models;

use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['doctor_id', 'scheduled_at', 'doctor_name', 'department', 'reason', 'status', 'requested_details', 'proposal_token', 'proposal_expires_at'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function vitalReadings(): HasMany
    {
        return $this->hasMany(VitalReading::class);
    }

    /** @param Builder<Appointment> $query */
    #[Scope]
    protected function occupyingSlot(Builder $query, bool $includePending = false): void
    {
        $query->where(function (Builder $query) use ($includePending): void {
            $query->whereIn('status', $includePending ? ['Scheduled', 'Pending assignment'] : ['Scheduled'])
                ->orWhere(function (Builder $query): void {
                    $query->where('status', 'Awaiting patient approval')->where('proposal_expires_at', '>', now());
                });
        });
    }

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'requested_details' => 'array', 'proposal_expires_at' => 'datetime'];
    }
}
