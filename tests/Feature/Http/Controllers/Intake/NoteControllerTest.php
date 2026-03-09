<?php

use App\Models\Intake;
use App\Models\Patient;

it('allows a parent to add a note to their intake', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();
    $intake = Intake::factory()->for($patient)->create();

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post('/intake/notes', [
            'body' => 'I have a question about the insurance form.',
        ])
        ->assertRedirect();

    $intake->refresh();

    expect($intake->notes)->toHaveCount(1);
    expect($intake->notes->first()->patient_id)->toBe($patient->id);
    expect($intake->notes->first()->user_id)->toBeNull();
    expect($intake->notes->first()->body)->toBe('I have a question about the insurance form.');
});

it('validates note body is required', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();
    $intake = Intake::factory()->for($patient)->create();

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post('/intake/notes', ['body' => ''])
        ->assertSessionHasErrors('body');
});
