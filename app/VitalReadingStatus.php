<?php

namespace App;

class VitalReadingStatus
{
    /** @return array{label: string, class: string} */
    public static function bloodPressure(?int $systolic, ?int $diastolic): array
    {
        if ($systolic === null || $diastolic === null) {
            return ['label' => 'No reading', 'class' => 'bg-slate-100 text-slate-700'];
        }

        if ($systolic < 120 && $diastolic < 80) {
            return ['label' => 'Normal', 'class' => 'bg-emerald-100 text-emerald-800'];
        }

        if ($systolic < 130 && $diastolic < 80) {
            return ['label' => 'Needs attention', 'class' => 'bg-amber-100 text-amber-800'];
        }

        if ($systolic < 140 && $diastolic < 90) {
            return ['label' => 'High', 'class' => 'bg-orange-100 text-orange-800'];
        }

        return ['label' => 'Very high', 'class' => 'bg-red-600 text-white shadow-sm'];
    }

    /** @return array{label: string, class: string} */
    public static function heartRate(?int $heartRate): array
    {
        if ($heartRate === null) {
            return ['label' => 'No reading', 'class' => 'bg-slate-100 text-slate-700'];
        }

        if ($heartRate < 60) {
            return ['label' => 'Low', 'class' => 'bg-amber-100 text-amber-800'];
        }

        if ($heartRate <= 100) {
            return ['label' => 'Normal', 'class' => 'bg-emerald-100 text-emerald-800'];
        }

        return ['label' => 'High', 'class' => 'bg-red-600 text-white shadow-sm'];
    }

    /** @return array{label: string, class: string} */
    public static function temperature(?string $temperature): array
    {
        if ($temperature === null) {
            return ['label' => 'No reading', 'class' => 'bg-slate-100 text-slate-700'];
        }

        if ((float) $temperature <= 37.3) {
            return ['label' => 'Normal', 'class' => 'bg-emerald-100 text-emerald-800'];
        }

        if ((float) $temperature < 38) {
            return ['label' => 'Warm', 'class' => 'bg-amber-100 text-amber-800'];
        }

        return ['label' => 'Fever range', 'class' => 'bg-red-600 text-white shadow-sm'];
    }
}
