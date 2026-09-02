<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ProjectStatus: string
{
    use EnumHelpers;

    case NotStarted = 'not_started';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::Active => 'Active',
            self::OnHold => 'On hold',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::Active => 'blue',
            self::OnHold => 'amber',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function isOpen(): bool
    {
        return $this->isAny(self::NotStarted, self::Active, self::OnHold);
    }
}
