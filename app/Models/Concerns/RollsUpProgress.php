<?php

namespace App\Models\Concerns;

/**
 * Cached progress roll-up for the Project → Milestone → Feature Set → Task →
 * Sub-task tree. Each model implements:
 *   - progressChildRelation(): ?string  — name of the hasMany relation whose
 *                                         rows expose `progress` (null ⇒ leaf)
 *   - progressParent(): the parent model (or null at the root)
 *   - computeLeafProgress(): ?int — value to use when there are no children
 *
 * Call recomputeProgress() after a child changes; it writes the new cached
 * value (without firing model events) and bubbles up to the parent.
 */
trait RollsUpProgress
{
    public function recomputeProgress(bool $bubble = true): void
    {
        $relation = $this->progressChildRelation();

        // Always read children fresh — the relation may be cached and stale.
        $children = $relation ? $this->{$relation}()->get() : collect();

        if ($children->isEmpty()) {
            $value = $this->computeLeafProgress() ?? 0;
        } else {
            $value = (int) round($children->avg('progress'));
        }

        if ((int) $this->progress !== $value) {
            $this->progress = $value;
            $this->saveQuietly();
        }

        if ($bubble) {
            $this->progressParent()?->recomputeProgress();
        }
    }

    public function progressChildRelation(): ?string
    {
        return null;
    }

    public function computeLeafProgress(): ?int
    {
        return null;
    }
}
