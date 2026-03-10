<?php

use App\Services\FormSchemaService;

test('form schema service loads all schemas', function (): void {
    $formSchemaService = app(FormSchemaService::class);
    $schemas = $formSchemaService->all();
    $keys = array_column($schemas, 'key');

    expect($schemas)->toBeArray()
        ->and($schemas)->toHaveCount(6)
        ->and($keys)->toContain('demographics', 'insurance', 'child_information', 'medical_history', 'developmental_concerns', 'consent');
});

test('form schema service loads a schema by key', function (): void {
    $formSchemaService = app(FormSchemaService::class);
    $schema = $formSchemaService->get('demographics');

    expect($schema)->not->toBeNull()
        ->and($schema['key'])->toBe('demographics')
        ->and($schema['title'])->toBeString()
        ->and($schema['title'])->toStartWith('forms/')
        ->and($schema['sections'])->toBeArray();
});

test('form schema service returns null for unknown key', function (): void {
    $formSchemaService = app(FormSchemaService::class);

    expect($formSchemaService->get('nonexistent'))->toBeNull();
});

test('form schema service extracts validation rules', function (): void {
    $formSchemaService = app(FormSchemaService::class);
    $rules = $formSchemaService->validationRules('demographics');

    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('first_name');
});

test('form schema service extracts conditional validation rules', function (): void {
    $formSchemaService = app(FormSchemaService::class);
    $rules = $formSchemaService->validationRules('demographics');

    expect($rules)->toHaveKey('secondary_guardian_name');
});

test('validationRules returns empty array for unknown schema key', function (): void {
    $formSchemaService = app(FormSchemaService::class);

    expect($formSchemaService->validationRules('nonexistent'))->toBe([]);
});

test('getResolved returns resolved translations', function (): void {
    $formSchemaService = app(FormSchemaService::class);
    $resolved = $formSchemaService->getResolved('demographics');

    expect($resolved)->not->toBeNull()
        ->and($resolved['key'])->toBe('demographics')
        ->and($resolved['title'])->toBeString()
        ->and($resolved['title'])->not->toStartWith('forms/');
});

test('getResolved returns null for unknown key', function (): void {
    $formSchemaService = app(FormSchemaService::class);

    expect($formSchemaService->getResolved('nonexistent'))->toBeNull();
});

test('loadSchemas returns empty when forms directory does not exist', function (): void {
    app()->useConfigPath('/nonexistent/path');

    $formSchemaService = new FormSchemaService;

    expect($formSchemaService->all())->toBe([]);
});

test('schemas are returned ordered', function (): void {
    $formSchemaService = app(FormSchemaService::class);
    $schemas = $formSchemaService->all();

    $orders = array_column($schemas, 'order');
    $sorted = $orders;
    sort($sorted);

    expect($orders)->toBe($sorted);
});
