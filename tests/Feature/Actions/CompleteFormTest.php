<?php

use App\Actions\CompleteForm;
use App\Enums\FormResponseStatus;
use App\Enums\IntakeStatus;
use App\Jobs\SyncIntakeToMonday;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\User;
use App\Notifications\CorrectionSubmittedNotification;
use App\Services\FormSchemaService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;

it('completes a form response and extracts child name', function (): void {
    $intake = Intake::factory()->create();

    $completeForm = app(CompleteForm::class);
    $completeForm->handle(
        intakeId: $intake->id,
        schemaKey: 'child_information',
        data: ['child_first_name' => 'Jane', 'child_last_name' => 'Doe'],
    );

    $formResponse = FormResponse::query()
        ->where('intake_id', $intake->id)
        ->where('schema_key', 'child_information')
        ->first();

    expect($formResponse->status)->toBe(FormResponseStatus::Completed);
    expect($formResponse->data)->toBe(['child_first_name' => 'Jane', 'child_last_name' => 'Doe']);
    expect($intake->fresh()->child_name)->toBe('Jane Doe');
});

it('submits intake when all forms are completed', function (): void {
    Bus::fake();
    config()->set('services.monday.api_token', 'fake-token');

    $intake = Intake::factory()->create();

    $formSchemaService = app(FormSchemaService::class);
    $allSchemas = $formSchemaService->all();

    // Complete all schemas except the last one via factory
    foreach (array_slice($allSchemas, 0, -1) as $schema) {
        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => $schema['key'],
        ]);
    }

    $lastSchema = end($allSchemas);

    $completeForm = app(CompleteForm::class);
    $completeForm->handle(
        intakeId: $intake->id,
        schemaKey: $lastSchema['key'],
        data: ['some_field' => 'value'],
    );

    expect($intake->fresh()->status)->toBe(IntakeStatus::Submitted);
    Bus::assertDispatched(SyncIntakeToMonday::class);
});

it('transitions flagged intake to correction submitted and notifies staff', function (): void {
    Notification::fake();

    $staffUser = User::factory()->create();
    $intake = Intake::factory()->flagged()->create();

    $completeForm = app(CompleteForm::class);
    $completeForm->handle(
        intakeId: $intake->id,
        schemaKey: 'demographics',
        data: ['first_name' => 'Updated'],
    );

    expect($intake->fresh()->status)->toBe(IntakeStatus::CorrectionSubmitted);
    Notification::assertSentTo($staffUser, CorrectionSubmittedNotification::class);
});
