<?php

namespace App\Actions;

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\IntakeFlag;
use App\Notifications\IntakeFlaggedNotification;

class FlagFormResponse
{
    public function handle(Intake $intake, int $formResponseId, int $userId, string $reason): IntakeFlag
    {
        $intakeFlag = IntakeFlag::query()->create([
            'intake_id' => $intake->id,
            'form_response_id' => $formResponseId,
            'user_id' => $userId,
            'reason' => $reason,
        ]);

        $intake->update(['status' => IntakeStatus::Flagged]);

        $intake->load('patient');
        $intake->patient?->notify(new IntakeFlaggedNotification($intake, $intakeFlag));

        return $intakeFlag;
    }
}
