<?php

namespace Database\Factories;

use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntakeNote>
 */
class IntakeNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intake_id' => Intake::factory(),
            'user_id' => User::factory(),
            'patient_id' => null,
            'body' => fake()->sentence(),
        ];
    }

    public function fromPatient(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'patient_id' => Patient::factory(),
        ]);
    }
}
