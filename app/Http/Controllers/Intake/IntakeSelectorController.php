<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Models\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IntakeSelectorController extends Controller
{
    /** @var list<string> */
    private const array PARENT_LEVEL_SCHEMAS = ['demographics', 'insurance'];

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

    public function create(Request $request): RedirectResponse
    {
        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        $newIntake = Intake::query()->create(['patient_id' => $patientId]);

        $mostRecentIntake = Intake::query()
            ->where('patient_id', $patientId)
            ->where('id', '!=', $newIntake->id)
            ->latest('id')
            ->first();

        if ($mostRecentIntake) {
            $parentFormResponses = FormResponse::query()
                ->where('intake_id', $mostRecentIntake->id)
                ->whereIn('schema_key', self::PARENT_LEVEL_SCHEMAS)
                ->get();

            foreach ($parentFormResponses as $parentFormResponse) {
                FormResponse::query()->create([
                    'intake_id' => $newIntake->id,
                    'schema_key' => $parentFormResponse->schema_key,
                    'data' => $parentFormResponse->data,
                    'status' => 'in_progress',
                ]);
            }
        }

        $request->session()->put('intake_id', $newIntake->id);

        return redirect()->route('intake.dashboard');
    }
}
