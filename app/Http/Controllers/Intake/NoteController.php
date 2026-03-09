<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\IntakeNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        /** @var int $intakeId */
        $intakeId = $request->session()->get('intake_id');

        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        IntakeNote::query()->create([
            'intake_id' => $intakeId,
            'patient_id' => $patientId,
            'body' => $request->string('body')->value(),
        ]);

        return back();
    }
}
