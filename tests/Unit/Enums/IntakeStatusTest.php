<?php

use App\Enums\IntakeStatus;

it('has the expected cases', function (): void {
    expect(IntakeStatus::cases())->toHaveCount(7);
});

it('returns a human-readable label', function (): void {
    expect(IntakeStatus::UnderReview->label())->toBe('Under Review');
    expect(IntakeStatus::CorrectionSubmitted->label())->toBe('Corrections Submitted');
});

it('returns the correct label for every case', function (IntakeStatus $intakeStatus, string $expectedLabel): void {
    expect($intakeStatus->label())->toBe($expectedLabel);
})->with([
    'Active' => [IntakeStatus::Active, 'Active'],
    'Submitted' => [IntakeStatus::Submitted, 'Submitted'],
    'UnderReview' => [IntakeStatus::UnderReview, 'Under Review'],
    'Flagged' => [IntakeStatus::Flagged, 'Flagged'],
    'CorrectionSubmitted' => [IntakeStatus::CorrectionSubmitted, 'Corrections Submitted'],
    'Approved' => [IntakeStatus::Approved, 'Approved'],
    'SyncedToMonday' => [IntakeStatus::SyncedToMonday, 'Synced to Monday'],
]);

it('returns staff actionable statuses', function (): void {
    expect(IntakeStatus::staffActionable())->toBe([
        IntakeStatus::Submitted,
        IntakeStatus::CorrectionSubmitted,
    ]);
});
