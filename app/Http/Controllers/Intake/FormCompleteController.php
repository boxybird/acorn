<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FormCompleteController extends Controller
{
    public function show(string $schemaKey, Request $request, FormSchemaService $formSchemaService): Response
    {
        $schema = $formSchemaService->get($schemaKey);

        if ($schema === null) {
            throw new NotFoundHttpException;
        }

        /** @var int $intakeId */
        $intakeId = $request->session()->get('intake_id');

        /** @var array<string, string> $responseStatuses */
        $responseStatuses = FormResponse::query()
            ->where('intake_id', $intakeId)
            ->pluck('status', 'schema_key')
            ->all();

        $allSchemas = $formSchemaService->all();
        $completed = 0;
        $nextForm = null;
        $foundCurrent = false;

        foreach ($allSchemas as $allSchema) {
            /** @var string $key */
            $key = $allSchema['key'];
            $isCompleted = ($responseStatuses[$key] ?? null) === 'completed';

            if ($isCompleted) {
                $completed++;
            }

            if ($foundCurrent && $nextForm === null && ! $isCompleted) {
                $nextForm = [
                    'key' => $key,
                    'title' => $allSchema['title'],
                ];
            }

            if ($key === $schemaKey) {
                $foundCurrent = true;
            }
        }

        return Inertia::render('intake/FormComplete', [
            'completedForm' => [
                'key' => $schema['key'],
                'title' => $schema['title'],
            ],
            'nextForm' => $nextForm,
            'progress' => [
                'completed' => $completed,
                'total' => count($allSchemas),
            ],
        ]);
    }
}
