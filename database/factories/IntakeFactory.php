<?php

namespace Database\Factories;

use App\Enums\IntakeStatus;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Intake>
 */
class IntakeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'child_name' => fake()->firstName(),
            'status' => IntakeStatus::Active,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => IntakeStatus::Approved,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'status' => IntakeStatus::Submitted,
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn (): array => [
            'status' => IntakeStatus::Flagged,
        ]);
    }

    public function correctionSubmitted(): static
    {
        return $this->state(fn (): array => [
            'status' => IntakeStatus::CorrectionSubmitted,
        ]);
    }

    public function withoutChildName(): static
    {
        return $this->state(fn (): array => [
            'child_name' => null,
        ]);
    }
}
