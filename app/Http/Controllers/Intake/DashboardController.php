<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FormSchemaService $formSchemaService): Response
    {
        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        $schemas = $formSchemaService->all();

        /** @var array<string, string> $responseStatuses */
        $responseStatuses = \App\Models\FormResponse::query()
            ->where('patient_id', $patientId)
            ->pluck('status', 'schema_key')
            ->all();

        $forms = array_map(function (array $schema) use ($responseStatuses): array {
            /** @var string $key */
            $key = $schema['key'];

            return [
                'key' => $key,
                'title' => $schema['title'],
                'icon' => $schema['icon'] ?? null,
                'estimated_minutes' => $schema['estimated_minutes'] ?? null,
                'status' => $responseStatuses[$key] ?? 'not_started',
            ];
        }, $schemas);

        $completed = count(array_filter($forms, fn (array $form): bool => $form['status'] === 'completed'));

        return Inertia::render('intake/Dashboard', [
            'forms' => $forms,
            'progress' => [
                'completed' => $completed,
                'total' => count($forms),
            ],
        ]);
    }
}
