<?php

namespace App\Http\Controllers\Intake;

use App\Enums\FormResponseStatus;
use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\IntakeNote;
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

        $schemas = $formSchemaService->all();

        $allIntakes = Intake::query()
            ->where('patient_id', $patientId)
            ->with([
                'formResponses:id,intake_id,schema_key,status',
                'flags' => function ($query): void {
                    $query->with('formResponse')->whereNull('resolved_at');
                },
            ])
            ->oldest()
            ->get();

        /** @var list<array<string, mixed>> $intakes */
        $intakes = $allIntakes->map(function (Intake $intake) use ($schemas, $intakeId): array {
            /** @var array<string, FormResponseStatus> $responseStatuses */
            $responseStatuses = $intake->formResponses->pluck('status', 'schema_key')->all();

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
                    'status' => $responseStatuses[$key] ?? FormResponseStatus::NotStarted,
                ];
            }, $schemas);

            $completed = count(array_filter($forms, fn (array $form): bool => $form['status'] === FormResponseStatus::Completed));

            $timeEstimate = array_sum(array_map(
                function (array $form): int {
                    if ($form['status'] === FormResponseStatus::Completed) {
                        return 0;
                    }

                    /** @var int $minutes */
                    $minutes = $form['estimated_minutes'] ?? 0;

                    return $minutes;
                },
                $forms,
            ));

            return [
                'id' => $intake->id,
                'child_name' => $intake->child_name,
                'status' => $intake->status,
                'is_current' => $intake->id === $intakeId,
                'forms' => $forms,
                'progress' => [
                    'completed' => $completed,
                    'total' => count($forms),
                ],
                'time_estimate' => $timeEstimate,
                'flags' => $intake->flags,
            ];
        })->all();

        // Notes across all patient intakes
        $intakeIds = $allIntakes->pluck('id');
        $notes = IntakeNote::query()
            ->whereIn('intake_id', $intakeIds)
            ->with(['user', 'patient'])
            ->latest()
            ->get();

        return Inertia::render('intake/Dashboard', [
            'intakes' => $intakes,
            'notes' => $notes,
        ]);
    }
}
