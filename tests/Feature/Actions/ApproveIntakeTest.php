<?php

use App\Actions\ApproveIntake;
use App\Enums\IntakeStatus;
use App\Jobs\SyncIntakeToMonday;
use App\Models\Intake;
use Illuminate\Support\Facades\Bus;

it('approves an intake and dispatches sync job', function (): void {
    Bus::fake();
    config()->set('services.monday.api_token', 'fake-token');

    $intake = Intake::factory()->submitted()->create();

    $approveIntake = app(ApproveIntake::class);
    $result = $approveIntake->handle($intake);

    expect($result->fresh()->status)->toBe(IntakeStatus::Approved);

    Bus::assertDispatched(SyncIntakeToMonday::class);
});

it('does not dispatch sync job when monday api token is missing', function (): void {
    Bus::fake();
    config()->set('services.monday.api_token');

    $intake = Intake::factory()->submitted()->create();

    $approveIntake = app(ApproveIntake::class);
    $approveIntake->handle($intake);

    Bus::assertNotDispatched(SyncIntakeToMonday::class);
});
