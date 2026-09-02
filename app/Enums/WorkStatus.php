<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Status for milestones and feature sets. */
enum WorkStatus: string
{
    use EnumHelpers;

    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InProgress => 'In progress',
            self::Blocked => 'Blocked',
            self::Done => 'Done',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::InProgress => 'blue',
            self::Blocked => 'red',
            self::Done => 'green',
        };
    }
}
