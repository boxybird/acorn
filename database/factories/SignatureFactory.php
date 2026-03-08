<?php

namespace Database\Factories;

use App\Models\FormResponse;
use App\Models\Intake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Signature>
 */
class SignatureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intake_id' => Intake::factory(),
            'form_response_id' => FormResponse::factory(),
            'field_key' => 'consent_signature',
            'image_path' => 'signatures/'.fake()->uuid().'.png',
        ];
    }
}
