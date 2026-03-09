<?php

use App\Models\Intake;
use App\Models\IntakeNote;
use App\Models\Patient;
use App\Models\User;

it('belongs to an intake', function (): void {
    $note = IntakeNote::factory()->create();
    expect($note->intake)->toBeInstanceOf(Intake::class);
});

it('can be from staff', function (): void {
    $note = IntakeNote::factory()->create();
    expect($note->isFromStaff())->toBeTrue();
    expect($note->user)->toBeInstanceOf(User::class);
});

it('can be from a patient', function (): void {
    $note = IntakeNote::factory()->fromPatient()->create();
    expect($note->isFromStaff())->toBeFalse();
    expect($note->patient)->toBeInstanceOf(Patient::class);
});

it('is accessible via intake relationship', function (): void {
    $intake = Intake::factory()->create();
    IntakeNote::factory()->for($intake)->count(3)->create();
    expect($intake->notes)->toHaveCount(3);
});
