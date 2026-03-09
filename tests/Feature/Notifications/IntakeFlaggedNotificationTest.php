<?php

use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\IntakeFlaggedNotification;
use Illuminate\Support\Facades\Notification;

it('sends a notification to the parent when a form is flagged', function (): void {
    Notification::fake();

    $this->actingAs(User::factory()->create());
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->for($patient)->underReview()->create();
    $formResponse = $intake->formResponses()->create([
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
        'status' => 'completed',
    ]);

    $this->post(sprintf('/staff/intakes/%s/flag', $intake->id), [
        'form_response_id' => $formResponse->id,
        'reason' => 'Missing information',
    ]);

    Notification::assertSentTo($patient, IntakeFlaggedNotification::class);
});

it('does not send a notification when flag validation fails', function (): void {
    Notification::fake();

    $this->actingAs(User::factory()->create());
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->for($patient)->underReview()->create();

    $this->post(sprintf('/staff/intakes/%s/flag', $intake->id), [
        'form_response_id' => '',
        'reason' => '',
    ]);

    Notification::assertNothingSent();
});
