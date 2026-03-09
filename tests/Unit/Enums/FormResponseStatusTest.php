<?php

use App\Enums\FormResponseStatus;

it('has the expected cases', function (): void {
    expect(FormResponseStatus::cases())->toHaveCount(3);
});

it('returns a human-readable label', function (): void {
    expect(FormResponseStatus::NotStarted->label())->toBe('Not Started');
    expect(FormResponseStatus::InProgress->label())->toBe('In Progress');
    expect(FormResponseStatus::Completed->label())->toBe('Completed');
});
