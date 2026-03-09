<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $blueprint): void {
            $blueprint->string('email_hash', 64)->nullable()->after('email');
        });

        $this->backfillPatients();

        Schema::table('patients', function (Blueprint $blueprint): void {
            $blueprint->dropUnique(['email']);
            $blueprint->unique('email_hash');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $blueprint): void {
            $blueprint->dropUnique(['email_hash']);
            $blueprint->unique('email');
            $blueprint->dropColumn('email_hash');
        });
    }

    private function backfillPatients(): void
    {
        /** @var string $appKey */
        $appKey = config('app.key');

        $patients = DB::table('patients')->get();

        foreach ($patients as $patient) {
            $emailHash = hash_hmac('sha256', mb_strtolower($patient->email), $appKey);
            $encryptedEmail = encrypt($patient->email);
            $encryptedName = $patient->name !== null ? encrypt($patient->name) : null;

            DB::table('patients')
                ->where('id', $patient->id)
                ->update([
                    'email' => $encryptedEmail,
                    'name' => $encryptedName,
                    'email_hash' => $emailHash,
                ]);
        }

        $intakes = DB::table('intakes')->whereNotNull('child_name')->get();

        foreach ($intakes as $intake) {
            DB::table('intakes')
                ->where('id', $intake->id)
                ->update([
                    'child_name' => encrypt($intake->child_name),
                ]);
        }
    }
};
