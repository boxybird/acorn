<?php

namespace Database\Factories;

use App\Enums\FormResponseStatus;
use App\Models\Intake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormResponse>
 */
class FormResponseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intake_id' => Intake::factory(),
            'schema_key' => 'demographics',
            'data' => ['first_name' => $this->faker->firstName()],
            'status' => FormResponseStatus::InProgress,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => ['status' => FormResponseStatus::Completed]);
    }
}
