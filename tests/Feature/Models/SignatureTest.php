<?php

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Signature;

test('signature belongs to an intake', function (): void {
    $signature = Signature::factory()->create();

    expect($signature->intake)->toBeInstanceOf(Intake::class);
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
