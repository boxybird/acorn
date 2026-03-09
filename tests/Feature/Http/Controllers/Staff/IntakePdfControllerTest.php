<?php

use App\Models\Intake;
use App\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('generates a PDF for an intake', function (): void {
    $intake = Intake::factory()->submitted()->create();
    $intake->formResponses()->create([
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
        'status' => 'completed',
    ]);

    $response = $this->get(sprintf('/staff/intakes/%s/pdf', $intake->id));

    $response->assertOk();
    $response->assertHeader('content-disposition');
});

it('uses the child name in the PDF filename', function (): void {
    $intake = Intake::factory()->submitted()->create(['child_name' => 'John Smith']);

    $response = $this->get(sprintf('/staff/intakes/%s/pdf', $intake->id));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename=john-smith-intake-summary.pdf');
});

it('uses a default filename when child name is null', function (): void {
    $intake = Intake::factory()->submitted()->withoutChildName()->create();

    $response = $this->get(sprintf('/staff/intakes/%s/pdf', $intake->id));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename=intake-intake-summary.pdf');
});
