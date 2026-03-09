<?php

use App\Enums\IntakeStatus;

it('has the expected cases', function (): void {
    expect(IntakeStatus::cases())->toHaveCount(7);
});

it('returns a human-readable label', function (): void {
    expect(IntakeStatus::UnderReview->label())->toBe('Under Review');
    expect(IntakeStatus::CorrectionSubmitted->label())->toBe('Corrections Submitted');
});

it('returns staff actionable statuses', function (): void {
    expect(IntakeStatus::staffActionable())->toBe([
        IntakeStatus::Submitted,
        IntakeStatus::CorrectionSubmitted,
    ]);
});
