<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Status for generic tracker items (the EMREC Master Business Tracker). */
enum TrackerStatus: string
{
    use EnumHelpers;

    case NotStarted = 'not_started';
    case Ongoing = 'ongoing';
    case Blocked = 'blocked';
    case Done = 'done';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::Ongoing => 'Ongoing',
            self::Blocked => 'Blocked',
            self::Done => 'Done',
            self::Dropped => 'Dropped',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::Ongoing => 'blue',
            self::Blocked => 'red',
            self::Done => 'green',
            self::Dropped => 'gray',
        };
    }
}
