<?php

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\CorrectionSubmittedNotification;
use Illuminate\Support\Facades\Notification;

it('notifies staff and transitions status when parent completes a form on a flagged intake', function (): void {
    Notification::fake();

    $staffUser = User::factory()->create();
    $patient = Patient::factory()->withMagicLink()->create();
    $intake = Intake::factory()->for($patient)->flagged()->create();

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post('/intake/form/demographics/complete', [
            'data' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'phone' => '5551234567',
                'email' => 'jane@example.com',
                'address' => '123 Main St, Albuquerque, NM 87101',
                'preferred_language' => 'en',
                'referral_source' => 'pediatrician',
            ],
        ]);

    expect($intake->fresh()->status)->toBe(IntakeStatus::CorrectionSubmitted);
    Notification::assertSentTo($staffUser, CorrectionSubmittedNotification::class);
});

it('does not transition or notify when completing a form on an active intake', function (): void {
    Notification::fake();

    User::factory()->create();
    $patient = Patient::factory()->withMagicLink()->create();
    $intake = Intake::factory()->for($patient)->create();

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post('/intake/form/demographics/complete', [
            'data' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'phone' => '5551234567',
                'email' => 'jane@example.com',
                'address' => '123 Main St, Albuquerque, NM 87101',
                'preferred_language' => 'en',
                'referral_source' => 'pediatrician',
            ],
        ]);

    expect($intake->fresh()->status)->toBe(IntakeStatus::Active);
    Notification::assertNothingSent();
});
