<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Models\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntakeSelectorController extends Controller
{
    /** @var list<string> */
    private const array PARENT_LEVEL_SCHEMAS = ['demographics', 'insurance'];

    public function index(Request $request): Response
    {
        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        $intakes = Intake::query()
            ->where('patient_id', $patientId)
            ->withCount([
                'formResponses as completed_forms_count' => function ($query): void {
                    $query->where('status', 'completed');
                },
            ])
            ->latest()
            ->get()
            ->map(function (Intake $intake): array {
                /** @var int $completedCount */
                $completedCount = $intake->getAttribute('completed_forms_count');

                return [
                    'id' => $intake->id,
                    'child_name' => $intake->child_name,
                    'status' => $intake->status,
                    'progress' => [
                        'completed' => $completedCount,
                        'total' => 6,
                    ],
                    'created_at' => $intake->created_at?->diffForHumans(),
                    'updated_at' => $intake->updated_at?->diffForHumans(),
                ];
            });

        return Inertia::render('intake/IntakeSelector', [
            'intakes' => $intakes,
        ]);
    }

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
