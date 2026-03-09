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
        Schema::create('intake_notes', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('intake_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $blueprint->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $blueprint->text('body');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intake_notes');
    }
};
