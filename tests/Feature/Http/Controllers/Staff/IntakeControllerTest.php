<?php

use App\Enums\IntakeStatus;
use App\Models\Intake;
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

it('excludes active intakes from the list', function (): void {
    Intake::factory()->count(2)->create();
    Intake::factory()->submitted()->count(1)->create();

    $this->get('/staff/intakes')
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
            ->has('notes')
            ->has('flags')
            ->has('schemas')
        );
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
