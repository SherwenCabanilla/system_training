<?php

namespace App\Models;

use Database\Factories\PortalNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'title', 'body', 'read_at', 'target'])]
class PortalNotification extends Model
{
    /** @use HasFactory<PortalNotificationFactory> */
    use HasFactory;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function destinationUrl(): string
    {
        $kind = $this->target['kind'] ?? null;
        $id = (int) ($this->target['id'] ?? 0);
        if ($id > 0) {
            if ($kind === 'appointment') {
                return route('appointments.index', ['appointment' => $id]);
            }
            if (in_array($kind, ['vital', 'record'], true)) {
                return route('medical-records.index', ['record' => $kind.'-'.$id]);
            }
        }

        if (str_starts_with($this->type, 'appointment_')) {
            return route('appointments.index');
        }

        return $this->type === 'vital_reading_available' ? route('medical-records.index') : route('notifications.index');
    }

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'target' => 'array'];
    }
}
