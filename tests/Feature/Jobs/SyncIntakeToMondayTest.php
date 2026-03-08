<?php

use App\Jobs\SyncIntakeToMonday;
use App\Models\Intake;
use App\Services\MondayService;

test('sync job updates intake status on success', function (): void {
    $intake = Intake::factory()->create(['sync_status' => 'pending']);

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->andReturn('12345');
    app()->instance(MondayService::class, $mock);

    SyncIntakeToMonday::dispatchSync($intake);

    $intake->refresh();

    expect($intake->sync_status)->toBe('synced')
        ->and($intake->synced_at)->not->toBeNull();
});

test('sync job marks intake as failed on exception', function (): void {
    $intake = Intake::factory()->create(['sync_status' => 'pending']);

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->andThrow(new Exception('API error'));
    app()->instance(MondayService::class, $mock);

    $job = new SyncIntakeToMonday($intake);

    try {
        $job->handle(app(MondayService::class), app(\App\Services\FormSchemaService::class));
    } catch (Exception) {
        $job->failed(new Exception('API error'));
    }

    $intake->refresh();

    expect($intake->sync_status)->toBe('failed');
});
