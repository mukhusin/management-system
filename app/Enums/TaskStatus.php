<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Task / sub-task status (SRS SDLC-4). */
enum TaskStatus: string
{
    use EnumHelpers;

    case Todo = 'todo';
    case InProgress = 'in_progress';
    case CodeReview = 'code_review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'To-Do',
            self::InProgress => 'In Progress',
            self::CodeReview => 'Code Review',
            self::Done => 'Done',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Todo => 'gray',
            self::InProgress => 'blue',
            self::CodeReview => 'purple',
            self::Done => 'green',
        };
    }

    /** Progress implied purely by status, or null when it depends on the user's input. */
    public function impliedProgress(): ?int
    {
        return match ($this) {
            self::Todo => 0,
            self::Done => 100,
            default => null,
        };
    }
}
