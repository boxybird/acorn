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
        Schema::table('intakes', function (Blueprint $blueprint): void {
            $blueprint->string('sync_status')->default('pending')->after('status');
            $blueprint->timestamp('synced_at')->nullable()->after('sync_status');
        });
    }

    public function down(): void
    {
        Schema::table('intakes', function (Blueprint $blueprint): void {
            $blueprint->dropColumn(['sync_status', 'synced_at']);
        });
    }
};
