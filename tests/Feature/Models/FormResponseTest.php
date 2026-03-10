<?php

use App\Models\Document;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\IntakeFlag;
use App\Models\Signature;
use Illuminate\Support\Facades\DB;

test('form response belongs to an intake', function (): void {
    $formResponse = FormResponse::factory()->create();

    expect($formResponse->intake)->toBeInstanceOf(Intake::class);
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

test('intake can only have one response per schema key', function (): void {
    $intake = Intake::factory()->create();
    FormResponse::factory()->create(['intake_id' => $intake->id, 'schema_key' => 'demographics']);

    expect(fn () => FormResponse::factory()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'demographics',
    ]))->toThrow(Exception::class);
});

test('form response can be marked completed', function (): void {
    $formResponse = FormResponse::factory()->completed()->create();

    expect($formResponse->isCompleted())->toBeTrue();
});

test('form response has many documents', function (): void {
    $formResponse = FormResponse::factory()->create();
    Document::factory()->count(2)->create([
        'intake_id' => $formResponse->intake_id,
        'form_response_id' => $formResponse->id,
    ]);

    expect($formResponse->documents)->toHaveCount(2)
        ->each->toBeInstanceOf(Document::class);
});

test('form response has many signatures', function (): void {
    $formResponse = FormResponse::factory()->create();
    Signature::factory()->count(2)->create([
        'intake_id' => $formResponse->intake_id,
        'form_response_id' => $formResponse->id,
    ]);

    expect($formResponse->signatures)->toHaveCount(2)
        ->each->toBeInstanceOf(Signature::class);
});

test('form response has many flags', function (): void {
    $formResponse = FormResponse::factory()->create();
    IntakeFlag::factory()->count(2)->create([
        'intake_id' => $formResponse->intake_id,
        'form_response_id' => $formResponse->id,
    ]);

    expect($formResponse->flags)->toHaveCount(2)
        ->each->toBeInstanceOf(IntakeFlag::class);
});
