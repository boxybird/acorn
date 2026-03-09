<?php

namespace App\Enums;

enum IntakeStatus: string
{
    case Active = 'active';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Flagged = 'flagged';
    case CorrectionSubmitted = 'correction_submitted';
    case Approved = 'approved';
    case SyncedToMonday = 'synced_to_monday';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Flagged => 'Flagged',
            self::CorrectionSubmitted => 'Corrections Submitted',
            self::Approved => 'Approved',
            self::SyncedToMonday => 'Synced to Monday',
        };
    }

    /**
     * @return list<self>
     */
    public static function staffActionable(): array
    {
        return [self::Submitted, self::CorrectionSubmitted];
    }
}
