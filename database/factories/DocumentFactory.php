<?php

namespace Database\Factories;

use App\Models\FormResponse;
use App\Models\Intake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intake_id' => Intake::factory(),
            'form_response_id' => FormResponse::factory(),
            'field_key' => 'insurance_card',
            'file_path' => 'documents/'.$this->faker->uuid().'.pdf',
            'original_filename' => $this->faker->word().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(1024, 5242880),
        ];
    }
}
