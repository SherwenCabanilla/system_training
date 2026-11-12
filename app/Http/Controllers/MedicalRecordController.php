<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\VitalReadingStatus;
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
        $bloodPressureStatus = VitalReadingStatus::bloodPressure($vitalReadings->last()?->systolic, $vitalReadings->last()?->diastolic);
        $heartRateStatus = VitalReadingStatus::heartRate($vitalReadings->last()?->heart_rate);
        $temperatureStatus = VitalReadingStatus::temperature($vitalReadings->last()?->temperature);
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
            'title' => $vitalReading->appointment?->doctor_name ?? 'Clinical care team',
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
}
