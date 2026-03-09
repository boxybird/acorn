<?php

namespace App\Actions;

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\IntakeFlag;

class ResolveIntakeFlag
{
    public function handle(Intake $intake, IntakeFlag $intakeFlag): void
    {
        $intakeFlag->update(['resolved_at' => now()]);

        $unresolvedCount = $intake->flags()->whereNull('resolved_at')->count();

        if ($unresolvedCount === 0) {
            $intake->update(['status' => IntakeStatus::UnderReview]);
        }
    }
}
