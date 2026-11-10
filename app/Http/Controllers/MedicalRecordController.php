<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $patient = $this->patientFor($request);
        $medicalRecords = $patient->medicalRecords()->latest('recorded_on')->get();
        $vitalReadings = $patient->vitalReadings()->with('appointment')->latest('recorded_on')->limit(7)->get()->sortBy('recorded_on')->values();
        $latestMedicalRecord = $medicalRecords->first();
        $recordTypeCounts = $medicalRecords->countBy('record_type')->sortDesc();
        $resultCount = $medicalRecords->whereNotNull('result_summary')->count();
        $bloodPressureStatus = $this->bloodPressureStatus($vitalReadings->last()?->systolic, $vitalReadings->last()?->diastolic);
        $heartRateStatus = $this->heartRateStatus($vitalReadings->last()?->heart_rate);
        $temperatureStatus = $this->temperatureStatus($vitalReadings->last()?->temperature);
        $healthActivities = $medicalRecords->map(fn ($medicalRecord) => [
            'date' => $medicalRecord->recorded_on,
            'type' => $medicalRecord->record_type,
            'title' => $medicalRecord->title,
            'provider' => $medicalRecord->provider_name,
            'summary' => $medicalRecord->summary,
            'result' => $medicalRecord->result_summary,
            'is_vital_reading' => false,
        ])->concat($vitalReadings->map(fn ($vitalReading) => [
            'date' => $vitalReading->recorded_on,
            'type' => 'Vital signs check',
            'title' => 'Heart rate, temperature, and blood pressure',
            'provider' => $vitalReading->appointment?->department ?? 'Clinical visit',
            'summary' => "Heart rate: {$vitalReading->heart_rate} bpm. Temperature: {$vitalReading->temperature} °C. Blood pressure: {$vitalReading->systolic}/{$vitalReading->diastolic} mmHg.",
            'result' => 'Recorded during a completed dummy appointment visit.',
            'is_vital_reading' => true,
            'heart_rate' => $vitalReading->heart_rate,
            'temperature' => $vitalReading->temperature,
            'blood_pressure' => "{$vitalReading->systolic}/{$vitalReading->diastolic}",
        ]))->sortByDesc('date')->values();
        $activityPage = LengthAwarePaginator::resolveCurrentPage('activity_page');
        $healthActivities = new LengthAwarePaginator(
            $healthActivities->forPage($activityPage, 5)->values(),
            $healthActivities->count(),
            5,
            $activityPage,
            ['path' => $request->url(), 'pageName' => 'activity_page'],
        );

        $viewData = compact('medicalRecords', 'vitalReadings', 'healthActivities', 'latestMedicalRecord', 'recordTypeCounts', 'resultCount', 'bloodPressureStatus', 'heartRateStatus', 'temperatureStatus');

        if ($request->expectsJson()) {
            return response()->json(['html' => view('medical-records._timeline', $viewData)->render()]);
        }

        return view('medical-records.index', $viewData);
    }

    private function patientFor(Request $request): Patient
    {
        return $request->user()->patient()->firstOrCreate();
    }

    /** @return array{label: string, class: string} */
    private function bloodPressureStatus(?int $systolic, ?int $diastolic): array
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

        return ['label' => 'Very high', 'class' => 'bg-rose-100 text-rose-800'];
    }

    /** @return array{label: string, class: string} */
    private function heartRateStatus(?int $heartRate): array
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

        return ['label' => 'High', 'class' => 'bg-rose-100 text-rose-800'];
    }

    /** @return array{label: string, class: string} */
    private function temperatureStatus(?string $temperature): array
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

        return ['label' => 'Fever range', 'class' => 'bg-rose-100 text-rose-800'];
    }
}
