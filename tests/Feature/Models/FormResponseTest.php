<?php

use App\Models\FormResponse;
use App\Models\Intake;
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
