<?php

use App\Models\FormResponse;
use App\Models\Patient;
use App\Models\Signature;

test('signature belongs to a patient', function (): void {
    $signature = Signature::factory()->create();

    expect($signature->patient)->toBeInstanceOf(Patient::class);
});

test('signature belongs to a form response', function (): void {
    $signature = Signature::factory()->create();

    expect($signature->formResponse)->toBeInstanceOf(FormResponse::class);
});

test('signature can be created with factory', function (): void {
    $signature = Signature::factory()->create();

    expect($signature)->toBeInstanceOf(Signature::class)
        ->and($signature->field_key)->toBeString()
        ->and($signature->image_path)->toBeString();
});
