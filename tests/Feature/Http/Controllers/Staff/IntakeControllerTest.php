<?php

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\IntakeFlag;
use App\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('displays the intake list page', function (): void {
    Intake::factory()->submitted()->count(3)->create();

    $this->get('/staff/intakes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/IntakeList')
            ->has('intakes.data', 3)
            ->has('statusCounts')
        );
});

it('filters intakes by status', function (): void {
    Intake::factory()->submitted()->count(2)->create();
    Intake::factory()->flagged()->count(1)->create();

    $this->get('/staff/intakes?status=submitted')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 2)
        );
});

it('returns status counts for all statuses', function (): void {
    Intake::factory()->submitted()->count(3)->create();
    Intake::factory()->flagged()->count(1)->create();

    $this->get('/staff/intakes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('statusCounts.submitted', 3)
            ->where('statusCounts.flagged', 1)
        );
});

it('includes active intakes in the list', function (): void {
    Intake::factory()->count(2)->create();
    Intake::factory()->submitted()->count(1)->create();

    $this->get('/staff/intakes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 3)
        );
});

it('includes active status in status counts', function (): void {
    Intake::factory()->count(2)->create();
    Intake::factory()->submitted()->count(1)->create();

    $this->get('/staff/intakes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('statusCounts.active', 2)
            ->where('statusCounts.submitted', 1)
        );
});

it('cannot approve an active intake', function (): void {
    $intake = Intake::factory()->create();

    $this->post(sprintf('/staff/intakes/%s/approve', $intake->id))
        ->assertStatus(422);

    expect($intake->fresh()->status)->toBe(IntakeStatus::Active);
});

it('cannot flag an active intake', function (): void {
    $intake = Intake::factory()->create();
    $formResponse = $intake->formResponses()->create([
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
        'status' => 'in_progress',
    ]);

    $this->post(sprintf('/staff/intakes/%s/flag', $intake->id), [
        'form_response_id' => $formResponse->id,
        'reason' => 'Missing info',
    ])->assertStatus(422);

    expect($intake->flags)->toHaveCount(0);
});

it('filters intakes by active status', function (): void {
    Intake::factory()->count(2)->create();
    Intake::factory()->submitted()->count(3)->create();

    $this->get('/staff/intakes?status=active')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 2)
        );
});

it('filters intakes by patient email search', function (): void {
    $patient = \App\Models\Patient::factory()->create(['email' => 'searchme@example.com']);
    Intake::factory()->submitted()->create(['patient_id' => $patient->id]);
    Intake::factory()->submitted()->create();

    $this->get('/staff/intakes?search=searchme@example.com')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 1)
        );
});

it('requires authentication', function (): void {
    auth()->logout();
    $this->get('/staff/intakes')->assertRedirect('/login');
});

it('displays the intake detail page', function (): void {
    $intake = Intake::factory()->submitted()->create();
    $intake->formResponses()->create([
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
        'status' => 'completed',
    ]);

    $this->get('/staff/intakes/'.$intake->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/IntakeDetail')
            ->has('intake')
            ->has('formResponses')
            ->has('flags')
            ->has('schemas')
        );
});

it('passes form response ids needed for collapsible state initialization', function (): void {
    $intake = Intake::factory()->submitted()->create();
    $intake->formResponses()->createMany([
        ['schema_key' => 'demographics', 'data' => ['first_name' => 'Jane'], 'status' => 'completed'],
        ['schema_key' => 'insurance', 'data' => ['provider' => 'BCBS'], 'status' => 'completed'],
        ['schema_key' => 'child_information', 'data' => ['child_first_name' => 'Max'], 'status' => 'in_progress'],
    ]);

    $this->get('/staff/intakes/'.$intake->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('formResponses', 3)
            ->has('formResponses.0.id')
            ->has('formResponses.1.id')
            ->has('formResponses.2.id')
        );
});

it('displays an active intake detail page without transitioning status', function (): void {
    $intake = Intake::factory()->create();

    $this->get('/staff/intakes/'.$intake->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/IntakeDetail')
            ->has('intake')
        );

    expect($intake->fresh()->status)->toBe(IntakeStatus::Active);
});

it('auto-transitions intake to under review when staff views it', function (): void {
    $intake = Intake::factory()->submitted()->create();

    $this->get('/staff/intakes/'.$intake->id);

    expect($intake->fresh()->status)->toBe(IntakeStatus::UnderReview);
});

it('does not transition non-submitted intakes to under review', function (): void {
    $intake = Intake::factory()->flagged()->create();

    $this->get('/staff/intakes/'.$intake->id);

    expect($intake->fresh()->status)->toBe(IntakeStatus::Flagged);
});

it('allows staff to approve an intake', function (): void {
    $intake = Intake::factory()->underReview()->create();

    $this->post(sprintf('/staff/intakes/%s/approve', $intake->id))
        ->assertRedirect();

    expect($intake->fresh()->status)->toBe(IntakeStatus::Approved);
});

it('allows staff to flag a form on an intake', function (): void {
    $intake = Intake::factory()->underReview()->create();
    $formResponse = $intake->formResponses()->create([
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
        'status' => 'completed',
    ]);

    $this->post(sprintf('/staff/intakes/%s/flag', $intake->id), [
        'form_response_id' => $formResponse->id,
        'reason' => 'Missing last name',
    ])->assertRedirect();

    expect($intake->fresh()->status)->toBe(IntakeStatus::Flagged);
    expect($intake->flags)->toHaveCount(1);
    expect($intake->flags->first()->reason)->toBe('Missing last name');
});

it('allows staff to resolve a flag', function (): void {
    $intake = Intake::factory()->flagged()->create();
    $flag = IntakeFlag::factory()->for($intake)->create();

    $this->post(sprintf('/staff/intakes/%s/flags/%s/resolve', $intake->id, $flag->id))
        ->assertRedirect();

    expect($flag->fresh()->resolved_at)->not->toBeNull();
});

it('transitions back to under review when all flags resolved', function (): void {
    $intake = Intake::factory()->flagged()->create();
    $flag = IntakeFlag::factory()->for($intake)->create();

    $this->post(sprintf('/staff/intakes/%s/flags/%s/resolve', $intake->id, $flag->id));

    expect($intake->fresh()->status)->toBe(IntakeStatus::UnderReview);
});

it('stays flagged when some flags remain unresolved', function (): void {
    $intake = Intake::factory()->flagged()->create();
    $flag1 = IntakeFlag::factory()->for($intake)->create();
    $flag2 = IntakeFlag::factory()->for($intake)->create();

    $this->post(sprintf('/staff/intakes/%s/flags/%s/resolve', $intake->id, $flag1->id));

    expect($intake->fresh()->status)->toBe(IntakeStatus::Flagged);
});
