<?php

use App\Models\Patient;
use App\Models\User;

test('staff can view patient list', function (): void {
    $user = User::factory()->create();
    Patient::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('staff.patients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/PatientList')
            ->has('patients.data', 3)
        );
});

test('staff can view patient detail', function (): void {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->get(route('staff.patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/PatientDetail')
            ->has('patient')
            ->has('intakes')
        );
});

test('unauthenticated users cannot access staff dashboard', function (): void {
    $this->get(route('staff.patients.index'))
        ->assertRedirect(route('login'));
});
