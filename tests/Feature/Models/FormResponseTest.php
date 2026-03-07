<?php

use App\Models\FormResponse;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

test('form response belongs to a patient', function (): void {
    $formResponse = FormResponse::factory()->create();

    expect($formResponse->patient)->toBeInstanceOf(Patient::class);
});

test('form response data is encrypted', function (): void {
    $formResponse = FormResponse::factory()->create([
        'data' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
    ]);

    $formResponse->refresh();

    expect($formResponse->data)->toBe(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $raw = DB::table('form_responses')->where('id', $formResponse->id)->value('data');
    expect($raw)->not->toContain('Jane');
});

test('patient can only have one response per schema key', function (): void {
    $patient = Patient::factory()->create();
    FormResponse::factory()->create(['patient_id' => $patient->id, 'schema_key' => 'demographics']);

    expect(fn () => FormResponse::factory()->create([
        'patient_id' => $patient->id,
        'schema_key' => 'demographics',
    ]))->toThrow(Exception::class);
});

test('form response can be marked completed', function (): void {
    $formResponse = FormResponse::factory()->completed()->create();

    expect($formResponse->isCompleted())->toBeTrue();
});
