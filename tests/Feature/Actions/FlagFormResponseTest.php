<?php

use App\Actions\FlagFormResponse;
use App\Enums\IntakeStatus;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\IntakeFlaggedNotification;
use Illuminate\Support\Facades\Notification;

it('flags a form response and notifies the patient', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->submitted()->create(['patient_id' => $patient->id]);
    $formResponse = FormResponse::factory()->completed()->create(['intake_id' => $intake->id]);

    $flagFormResponse = app(FlagFormResponse::class);
    $intakeFlag = $flagFormResponse->handle(
        intake: $intake,
        formResponseId: $formResponse->id,
        userId: $user->id,
        reason: 'Missing date of birth',
    );

    expect($intake->fresh()->status)->toBe(IntakeStatus::Flagged);
    expect($intakeFlag->reason)->toBe('Missing date of birth');
    expect($intakeFlag->form_response_id)->toBe($formResponse->id);
    expect($intakeFlag->user_id)->toBe($user->id);

    Notification::assertSentTo($patient, IntakeFlaggedNotification::class);
});
