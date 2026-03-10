<?php

use App\Jobs\SyncIntakeToMonday;
use App\Models\Document;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Services\MondayService;

test('sync job updates intake status on success', function (): void {
    $intake = Intake::factory()->create(['sync_status' => 'pending']);

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->andReturn('12345');
    $mock->shouldReceive('uploadFiles')->never();
    app()->instance(MondayService::class, $mock);

    SyncIntakeToMonday::dispatchSync($intake);

    $intake->refresh();

    expect($intake->sync_status)->toBe('synced')
        ->and($intake->synced_at)->not->toBeNull();
});

test('sync job uploads files when intake has documents', function (): void {
    $intake = Intake::factory()->create(['sync_status' => 'pending']);
    Document::factory()->count(2)->create(['intake_id' => $intake->id]);

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->andReturn('12345');
    $mock->shouldReceive('uploadFiles')->once()->with('12345', Mockery::type('array'));
    app()->instance(MondayService::class, $mock);

    SyncIntakeToMonday::dispatchSync($intake);

    $intake->refresh();

    expect($intake->sync_status)->toBe('synced')
        ->and($intake->synced_at)->not->toBeNull();
});

test('buildColumnValues maps form response data to monday_field column values', function (): void {
    $intake = Intake::factory()->create(['sync_status' => 'pending']);
    FormResponse::factory()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'insurance',
        'data' => [
            'insurance_provider' => 'Blue Cross',
            'policy_number' => 'POL123',
            'group_number' => 'GRP456',
            'policyholder_name' => 'Jane Doe',
            'policyholder_relationship' => 'parent',
            'policyholder_dob' => '1985-03-15',
        ],
    ]);

    $capturedColumnValues = [];

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->withArgs(function ($patient, $columnValues) use (&$capturedColumnValues): bool {
        $capturedColumnValues = $columnValues;

        return true;
    })->andReturn('12345');
    $mock->shouldReceive('uploadFiles')->never();
    app()->instance(MondayService::class, $mock);

    SyncIntakeToMonday::dispatchSync($intake);

    expect($capturedColumnValues)
        ->toHaveKey('insurance_provider', 'Blue Cross')
        ->toHaveKey('policy_number', 'POL123')
        ->toHaveKey('group_number', 'GRP456')
        ->toHaveKey('policyholder_name', 'Jane Doe');
});

test('buildColumnValues skips form responses with unknown schema keys', function (): void {
    $intake = Intake::factory()->create(['sync_status' => 'pending']);
    FormResponse::factory()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'nonexistent_form',
        'data' => ['some_field' => 'some_value'],
    ]);

    $capturedColumnValues = [];

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->withArgs(function ($patient, $columnValues) use (&$capturedColumnValues): bool {
        $capturedColumnValues = $columnValues;

        return true;
    })->andReturn('12345');
    $mock->shouldReceive('uploadFiles')->never();
    app()->instance(MondayService::class, $mock);

    SyncIntakeToMonday::dispatchSync($intake);

    expect($capturedColumnValues)->toBe([]);
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
