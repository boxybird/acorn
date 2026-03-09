<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FormSchemaService $formSchemaService): Response
    {
        /** @var int $intakeId */
        $intakeId = $request->session()->get('intake_id');

        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        /** @var Intake $intake */
        $intake = Intake::query()->findOrFail($intakeId);

        $schemas = $formSchemaService->all();

        /** @var array<string, string> $responseStatuses */
        $responseStatuses = \App\Models\FormResponse::query()
            ->where('intake_id', $intakeId)
            ->pluck('status', 'schema_key')
            ->all();

        $forms = array_map(function (array $schema) use ($responseStatuses): array {
            /** @var string $key */
            $key = $schema['key'];

            /** @var string $titleKey */
            $titleKey = $schema['title'];

            return [
                'key' => $key,
                'title' => __($titleKey),
                'icon' => $schema['icon'] ?? null,
                'estimated_minutes' => $schema['estimated_minutes'] ?? null,
                'status' => $responseStatuses[$key] ?? 'not_started',
            ];
        }, $schemas);

        $completed = count(array_filter($forms, fn (array $form): bool => $form['status'] === 'completed'));

        // Time estimate: sum estimated_minutes for non-completed forms
        $timeEstimate = 0;
        foreach ($forms as $form) {
            if ($form['status'] !== 'completed') {
                /** @var int $minutes */
                $minutes = $form['estimated_minutes'] ?? 0;
                $timeEstimate += $minutes;
            }
        }

        // All intakes for this patient (for child cards)
        $allIntakes = Intake::query()
            ->where('patient_id', $patientId)
            ->withCount([
                'formResponses as completed_forms_count' => function ($query): void {
                    $query->where('status', 'completed');
                },
            ])
            ->oldest()
            ->get()
            ->map(function (Intake $intake) use ($intakeId): array {
                /** @var int $completedCount */
                $completedCount = $intake->getAttribute('completed_forms_count');

                return [
                    'id' => $intake->id,
                    'child_name' => $intake->child_name,
                    'status' => $intake->status,
                    'completed_forms_count' => $completedCount,
                    'is_current' => $intake->id === $intakeId,
                ];
            })
            ->all();

        return Inertia::render('intake/Dashboard', [
            'forms' => $forms,
            'progress' => [
                'completed' => $completed,
                'total' => count($forms),
            ],
            'intake' => [
                'id' => $intake->id,
                'child_name' => $intake->child_name,
            ],
            'allIntakes' => array_values($allIntakes),
            'timeEstimate' => $timeEstimate,
            'flags' => $intake->flags()->with('formResponse')->whereNull('resolved_at')->get(),
            'notes' => $intake->notes()->with(['user', 'patient'])->latest()->get(),
        ]);
    }
}
