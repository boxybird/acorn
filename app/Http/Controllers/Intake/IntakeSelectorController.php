<?php

namespace App\Http\Controllers\Intake;

use App\Actions\CreateChildIntake;
use App\Http\Controllers\Controller;
use App\Models\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IntakeSelectorController extends Controller
{
    public function choose(Intake $intake, Request $request): RedirectResponse
    {
        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        if ($intake->patient_id !== $patientId) {
            abort(403);
        }

        $request->session()->put('intake_id', $intake->id);

        return redirect()->route('intake.dashboard');
    }

    public function create(Request $request, CreateChildIntake $createChildIntake): RedirectResponse
    {
        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        $newIntake = $createChildIntake->handle($patientId);

        $request->session()->put('intake_id', $newIntake->id);

        return redirect()->route('intake.dashboard');
    }
}
