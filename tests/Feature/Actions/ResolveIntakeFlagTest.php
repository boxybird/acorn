<?php

use App\Actions\ResolveIntakeFlag;
use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\IntakeFlag;

it('resolves a flag and transitions intake to under review when no unresolved flags remain', function (): void {
    $intake = Intake::factory()->flagged()->create();
    $flag = IntakeFlag::factory()->create(['intake_id' => $intake->id]);

    $resolveIntakeFlag = app(ResolveIntakeFlag::class);
    $resolveIntakeFlag->handle($intake, $flag);

    expect($flag->fresh()->resolved_at)->not->toBeNull();
    expect($intake->fresh()->status)->toBe(IntakeStatus::UnderReview);
});

it('keeps intake flagged when unresolved flags remain', function (): void {
    $intake = Intake::factory()->flagged()->create();
    $flag1 = IntakeFlag::factory()->create(['intake_id' => $intake->id]);
    $flag2 = IntakeFlag::factory()->create(['intake_id' => $intake->id]);

    $resolveIntakeFlag = app(ResolveIntakeFlag::class);
    $resolveIntakeFlag->handle($intake, $flag1);

    expect($flag1->fresh()->resolved_at)->not->toBeNull();
    expect($intake->fresh()->status)->toBe(IntakeStatus::Flagged);
});
