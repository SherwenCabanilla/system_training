<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    public function index(Request $request): View
    {
        $medicalRecords = $this->patientFor($request)->medicalRecords()->latest('recorded_on')->get();

        return view('medical-records.index', compact('medicalRecords'));
    }

    private function patientFor(Request $request): Patient
    {
        return $request->user()->patient()->firstOrCreate();
    }
}
