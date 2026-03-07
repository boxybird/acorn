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
        Schema::create('patients', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->string('email')->unique();
            $blueprint->string('name')->nullable();
            $blueprint->string('preferred_locale', 5)->default('en');
            $blueprint->string('magic_link_token', 64)->nullable()->unique();
            $blueprint->timestamp('magic_link_expires_at')->nullable();
            $blueprint->string('sync_status')->default('pending');
            $blueprint->timestamp('synced_at')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
