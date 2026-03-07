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
        Schema::create('form_responses', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $blueprint->string('schema_key');
            $blueprint->text('data');
            $blueprint->string('status')->default('in_progress');
            $blueprint->timestamps();

            $blueprint->unique(['patient_id', 'schema_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_responses');
    }
};
