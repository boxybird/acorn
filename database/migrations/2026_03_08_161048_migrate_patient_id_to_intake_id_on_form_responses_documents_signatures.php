<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add nullable intake_id columns
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->foreignId('intake_id')->nullable()->after('id');
        });

        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->foreignId('intake_id')->nullable()->after('id');
        });

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->foreignId('intake_id')->nullable()->after('id');
        });

        // Step 2: Create an Intake for each patient that has data, then backfill intake_id
        $patientIds = DB::table('form_responses')->distinct()->pluck('patient_id')
            ->merge(DB::table('documents')->distinct()->pluck('patient_id'))
            ->merge(DB::table('signatures')->distinct()->pluck('patient_id'))
            ->unique();

        foreach ($patientIds as $patientId) {
            $intakeId = DB::table('intakes')->insertGetId([
                'patient_id' => $patientId,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('form_responses')->where('patient_id', $patientId)->update(['intake_id' => $intakeId]);
            DB::table('documents')->where('patient_id', $patientId)->update(['intake_id' => $intakeId]);
            DB::table('signatures')->where('patient_id', $patientId)->update(['intake_id' => $intakeId]);
        }

        // Step 3: Drop old unique constraint and patient_id FK
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->dropUnique(['patient_id', 'schema_key']);
        });

        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->dropConstrainedForeignId('patient_id');
        });

        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->dropConstrainedForeignId('patient_id');
        });

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->dropConstrainedForeignId('patient_id');
        });

        // Step 4: Make intake_id non-nullable with FK constraint
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('intake_id')->nullable(false)->change();
            $blueprint->foreign('intake_id')->references('id')->on('intakes')->cascadeOnDelete();
            $blueprint->unique(['intake_id', 'schema_key']);
        });

        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('intake_id')->nullable(false)->change();
            $blueprint->foreign('intake_id')->references('id')->on('intakes')->cascadeOnDelete();
        });

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('intake_id')->nullable(false)->change();
            $blueprint->foreign('intake_id')->references('id')->on('intakes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop unique constraint
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->dropUnique(['intake_id', 'schema_key']);
        });

        // Step 2: Add nullable patient_id columns
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->foreignId('patient_id')->nullable()->after('id');
        });

        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->foreignId('patient_id')->nullable()->after('id');
        });

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->foreignId('patient_id')->nullable()->after('id');
        });

        // Step 3: Backfill patient_id from intakes
        $intakes = DB::table('intakes')->get(['id', 'patient_id']);

        foreach ($intakes as $intake) {
            DB::table('form_responses')->where('intake_id', $intake->id)->update(['patient_id' => $intake->patient_id]);
            DB::table('documents')->where('intake_id', $intake->id)->update(['patient_id' => $intake->patient_id]);
            DB::table('signatures')->where('intake_id', $intake->id)->update(['patient_id' => $intake->patient_id]);
        }

        // Step 4: Drop intake_id FK and column
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['intake_id']);
            $blueprint->dropColumn('intake_id');
        });

        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['intake_id']);
            $blueprint->dropColumn('intake_id');
        });

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['intake_id']);
            $blueprint->dropColumn('intake_id');
        });

        // Step 5: Make patient_id non-nullable, add FK and unique constraint
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('patient_id')->nullable(false)->change();
            $blueprint->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $blueprint->unique(['patient_id', 'schema_key']);
        });

        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('patient_id')->nullable(false)->change();
            $blueprint->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('patient_id')->nullable(false)->change();
            $blueprint->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
    }
};
