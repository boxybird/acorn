<?php

use App\Jobs\SyncPatientToMonday;
use App\Models\Patient;
use App\Services\MondayService;

test('sync job updates patient status on success', function (): void {
    $patient = Patient::factory()->create(['sync_status' => 'pending']);

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->andReturn('12345');
    app()->instance(MondayService::class, $mock);

    SyncPatientToMonday::dispatchSync($patient);

    $patient->refresh();

    expect($patient->sync_status)->toBe('synced')
        ->and($patient->synced_at)->not->toBeNull();
});

test('sync job marks patient as failed on exception', function (): void {
    $patient = Patient::factory()->create(['sync_status' => 'pending']);

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->andThrow(new Exception('API error'));
    app()->instance(MondayService::class, $mock);

    $job = new SyncPatientToMonday($patient);

    try {
        $job->handle(app(MondayService::class), app(\App\Services\FormSchemaService::class));
    } catch (Exception) {
        $job->failed(new Exception('API error'));
    }

    $patient->refresh();

    expect($patient->sync_status)->toBe('failed');
});
