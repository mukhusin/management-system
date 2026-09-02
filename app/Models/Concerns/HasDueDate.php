<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * Human-friendly countdowns for a model's due/deadline date. The item is
 * considered open through the *end* of its due day.
 *
 * Override dueDateColumn() when the column isn't "due_date"
 * (Tender uses "deadline_date", Project uses "target_deadline").
 */
trait HasDueDate
{
    public function dueDateColumn(): string
    {
        return 'due_date';
    }

    public function dueDate(): ?Carbon
    {
        $value = $this->{$this->dueDateColumn()};

        return $value ? Carbon::parse($value) : null;
    }

    public function dueClosesAt(): ?Carbon
    {
        return $this->dueDate()?->copy()->endOfDay();
    }

    public function isOverdue(): bool
    {
        $closesAt = $this->dueClosesAt();

        return $closesAt !== null && $closesAt->isPast();
    }

    public function isDueSoon(int $days = 7): bool
    {
        $closesAt = $this->dueClosesAt();

        return $closesAt !== null
            && ! $closesAt->isPast()
            && $closesAt->lte(now()->addDays($days));
    }

    /**
     * Short countdown: "closed"/"overdue", "6h left", "1 day left",
     * "12 days left". Null when there is no due date.
     */
    public function dueCountdown(string $pastLabel = 'overdue'): ?string
    {
        $closesAt = $this->dueClosesAt();

        if ($closesAt === null) {
            return null;
        }

        if ($closesAt->isPast()) {
            return $pastLabel;
        }

        $hours = (int) floor(now()->diffInHours($closesAt, false));

        if ($hours < 24) {
            return max($hours, 1).'h left';
        }

        $days = intdiv($hours, 24);

        return $days.($days === 1 ? ' day left' : ' days left');
    }

    // --- Backwards-compatible aliases for Tender's original API ---

    public function deadlineClosesAt(): ?Carbon
    {
        return $this->dueClosesAt();
    }

    public function isClosed(): bool
    {
        return $this->isOverdue();
    }

    public function isClosingSoon(): bool
    {
        return $this->isDueSoon();
    }

    public function deadlineCountdown(): ?string
    {
        return $this->dueCountdown('closed');
    }
}
