<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(): Response
    {
        $lengthAwarePaginator = Patient::query()
            ->withCount('formResponses')
            ->latest()
            ->paginate(20);

        return Inertia::render('staff/PatientList', [
            'patients' => $lengthAwarePaginator,
        ]);
    }

    public function show(Patient $patient): Response
    {
        $patient->load(['formResponses', 'documents', 'signatures']);

        return Inertia::render('staff/PatientDetail', [
            'patient' => $patient,
            'formResponses' => $patient->formResponses,
        ]);
    }
}
