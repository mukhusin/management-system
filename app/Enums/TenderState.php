<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Tender lifecycle state machine (SRS TR-4):
 * Draft → Under Review → Submitted → Won → Lost / Cancelled.
 */
enum TenderState: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Submitted = 'submitted';
    case Won = 'won';
    case Lost = 'lost';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderReview => 'Under Review',
            self::Submitted => 'Submitted',
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::UnderReview => 'amber',
            self::Submitted => 'blue',
            self::Won => 'green',
            self::Lost => 'red',
            self::Cancelled => 'gray',
        };
    }

    /** States this state may transition to. */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::UnderReview, self::Cancelled],
            self::UnderReview => [self::Submitted, self::Draft, self::Cancelled],
            self::Submitted => [self::Won, self::Lost, self::Cancelled],
            self::Won, self::Lost, self::Cancelled => [],
        };
    }

    public function isTerminal(): bool
    {
        return $this->allowedNext() === [];
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), true);
    }
}
