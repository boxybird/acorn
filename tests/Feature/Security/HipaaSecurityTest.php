<?php

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

test('form response data is encrypted at rest', function (): void {
    $formResponse = FormResponse::factory()->create([
        'data' => ['ssn' => '123-45-6789'],
    ]);

    $raw = DB::table('form_responses')->where('id', $formResponse->id)->value('data');

    expect($raw)->not->toContain('123-45-6789');
});

test('magic link tokens are single use', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();

    /** @var string $token */
    $token = $patient->magic_link_token;

    $this->get(route('intake.verify', ['token' => $token]))->assertRedirect();
    $this->get(route('intake.verify', ['token' => $token]))->assertRedirect(route('intake.landing'));
});

test('patient cannot access another intakes form response', function (): void {
    $patient1 = Patient::factory()->create();
    $intake1 = Intake::factory()->create(['patient_id' => $patient1->id]);
    $patient2 = Patient::factory()->create();
    $intake2 = Intake::factory()->create(['patient_id' => $patient2->id]);
    FormResponse::factory()->create([
        'intake_id' => $intake2->id,
        'schema_key' => 'demographics',
        'data' => ['secret' => 'patient2-data'],
    ]);

    $this->withSession(['patient_id' => $patient1->id, 'intake_id' => $intake1->id])
        ->get(route('intake.form.show', 'demographics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('savedData', [])
        );
});

test('session lifetime does not exceed 120 minutes', function (): void {
    expect(config('session.lifetime'))->toBeLessThanOrEqual(120);
});

test('documents are stored on local private disk', function (): void {
    $localDisk = config('filesystems.disks.local');

    expect($localDisk['root'])->toContain('private');
});
