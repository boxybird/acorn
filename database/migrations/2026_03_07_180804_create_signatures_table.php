<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('form_response_id')->constrained()->cascadeOnDelete();
            $blueprint->string('field_key');
            $blueprint->string('image_path');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
