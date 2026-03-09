<?php

namespace App\Actions;

use App\Enums\IntakeStatus;
use App\Jobs\SyncIntakeToMonday;
use App\Models\Intake;

class ApproveIntake
{
    public function handle(Intake $intake): Intake
    {
        $intake->update(['status' => IntakeStatus::Approved]);

        if (config('services.monday.api_token')) {
            SyncIntakeToMonday::dispatch($intake);
        }

        return $intake;
    }
}
