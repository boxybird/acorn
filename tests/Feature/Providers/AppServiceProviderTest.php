<?php

use App\Services\MondayService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rules\Password;

test('MondayService is resolved as a singleton from the container', function (): void {
    $mondayService = app(MondayService::class);

    expect($mondayService)->toBeInstanceOf(MondayService::class);
});

test('MondayService is the same instance when resolved twice', function (): void {
    $mondayService = app(MondayService::class);
    $second = app(MondayService::class);

    expect($mondayService)->toBe($second);
});

test('CarbonImmutable is used for dates', function (): void {
    expect(Date::now())->toBeInstanceOf(CarbonImmutable::class);
});

test('production password defaults enforce strong rules', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    /** @var Password $rules */
    $rules = Password::defaults();

    expect($rules)->toBeInstanceOf(Password::class);
});
