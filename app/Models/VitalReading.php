<?php

namespace App\Models;

use Database\Factories\VitalReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['appointment_id', 'recorded_on', 'heart_rate', 'temperature', 'systolic', 'diastolic'])]
class VitalReading extends Model
{
    /** @use HasFactory<VitalReadingFactory> */
    use HasFactory;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'temperature' => 'decimal:1',
        ];
    }
}
