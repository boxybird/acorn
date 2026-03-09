<?php

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
