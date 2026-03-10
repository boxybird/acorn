<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->name(),
            'preferred_locale' => null,
            'magic_link_token' => null,
            'magic_link_expires_at' => null,
        ];
    }

    public function withMagicLink(): static
    {
        return $this->state(fn (): array => [
            'magic_link_token' => Str::random(64),
            'magic_link_expires_at' => now()->addMinutes(30),
        ]);
    }

    public function withExpiredMagicLink(): static
    {
        return $this->state(fn (): array => [
            'magic_link_token' => Str::random(64),
            'magic_link_expires_at' => now()->subMinute(),
        ]);
    }

    public function spanishSpeaking(): static
    {
        return $this->state(fn (): array => [
            'preferred_locale' => 'es',
        ]);
    }
}
