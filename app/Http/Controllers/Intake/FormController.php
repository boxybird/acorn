<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Jobs\SyncPatientToMonday;
use App\Models\FormResponse;
use App\Models\Patient;
use App\Services\FormSchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FormController extends Controller
{
    public function show(string $schemaKey, Request $request, FormSchemaService $formSchemaService): Response
    {
        $schema = $formSchemaService->get($schemaKey);

        if ($schema === null) {
            throw new NotFoundHttpException;
        }

        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        $formResponse = FormResponse::query()
            ->where('patient_id', $patientId)
            ->where('schema_key', $schemaKey)
            ->first();

        /** @var array<string, string> $responseStatuses */
        $responseStatuses = FormResponse::query()
            ->where('patient_id', $patientId)
            ->pluck('status', 'schema_key')
            ->all();

        $allSchemas = $formSchemaService->all();

        $forms = array_map(function (array $s) use ($responseStatuses): array {
            /** @var string $key */
            $key = $s['key'];

            /** @var list<array<string, mixed>> $sections */
            $sections = $s['sections'];

            return [
                'key' => $key,
                'title' => $s['title'],
                'sections' => array_map(fn (array $section): array => [
                    'key' => $section['key'],
                    'title' => $section['title'],
                ], $sections),
                'status' => $responseStatuses[$key] ?? 'not_started',
            ];
        }, $allSchemas);

        $completed = count(array_filter($forms, fn (array $form): bool => $form['status'] === 'completed'));

        return Inertia::render('intake/Form', [
            'schema' => $schema,
            'savedData' => $formResponse instanceof FormResponse ? $formResponse->data : [],
            'forms' => $forms,
            'progress' => [
                'completed' => $completed,
                'total' => count($forms),
            ],
        ]);
    }

    public function save(string $schemaKey, Request $request, FormSchemaService $formSchemaService): JsonResponse
    {
        $schema = $formSchemaService->get($schemaKey);

        if ($schema === null) {
            throw new NotFoundHttpException;
        }

        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        /** @var array<string, mixed> $incomingData */
        $incomingData = $request->input('data', []);

        $formResponse = FormResponse::query()
            ->where('patient_id', $patientId)
            ->where('schema_key', $schemaKey)
            ->first();

        if ($formResponse instanceof FormResponse) {
            /** @var array<string, mixed> $existingData */
            $existingData = $formResponse->data ?? [];
            $formResponse->update([
                'data' => array_merge($existingData, $incomingData),
            ]);
        } else {
            FormResponse::query()->create([
                'patient_id' => $patientId,
                'schema_key' => $schemaKey,
                'data' => $incomingData,
            ]);
        }

        return response()->json(['status' => 'saved']);
    }

    public function complete(string $schemaKey, Request $request, FormSchemaService $formSchemaService): RedirectResponse
    {
        $schema = $formSchemaService->get($schemaKey);

        if ($schema === null) {
            throw new NotFoundHttpException;
        }

        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        $rules = $formSchemaService->validationRules($schemaKey);

        /** @var array<string, list<string>> $prefixedRules */
        $prefixedRules = [];

        foreach ($rules as $fieldKey => $fieldRules) {
            $prefixedRules['data.'.$fieldKey] = $fieldRules;
        }

        $request->validate($prefixedRules);

        /** @var array<string, mixed> $validatedData */
        $validatedData = $request->input('data', []);

        FormResponse::query()->updateOrCreate(
            ['patient_id' => $patientId, 'schema_key' => $schemaKey],
            ['data' => $validatedData, 'status' => 'completed'],
        );

        $this->checkAndDispatchSync($patientId, $formSchemaService);

        return redirect()->route('intake.form.completed', $schemaKey);
    }

    private function checkAndDispatchSync(int $patientId, FormSchemaService $formSchemaService): void
    {
        $totalSchemas = count($formSchemaService->all());
        $completedCount = FormResponse::query()
            ->where('patient_id', $patientId)
            ->where('status', 'completed')
            ->count();

        if ($completedCount >= $totalSchemas && config('services.monday.api_token')) {
            /** @var Patient $patient */
            $patient = Patient::query()->findOrFail($patientId);
            SyncPatientToMonday::dispatch($patient);
        }
    }
}
