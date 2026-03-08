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
        Schema::create('intakes', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $blueprint->string('child_name')->nullable();
            $blueprint->string('status')->default('active');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intakes');
    }
};
