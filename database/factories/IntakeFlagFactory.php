<?php

namespace Database\Factories;

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntakeFlag>
 */
class IntakeFlagFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intake_id' => Intake::factory(),
            'form_response_id' => FormResponse::factory(),
            'user_id' => User::factory(),
            'reason' => fake()->sentence(),
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'resolved_at' => now(),
        ]);
    }
}
