<?php

namespace App\Models\Concerns;

/**
 * Cached progress roll-up for the Project → Milestone → Feature Set → Task →
 * Sub-task tree. Each model implements:
 *   - progressChildren(): iterable of child models exposing `progress`
 *   - progressParent(): the parent model (or null at the root)
 *   - computeLeafProgress(): ?int  — a value to use when there are no children
 *                                    (null ⇒ always average the children)
 *
 * Call recomputeProgress() after a child changes; it writes the new cached
 * value (without firing model events) and bubbles up to the parent.
 */
trait RollsUpProgress
{
    public function recomputeProgress(bool $bubble = true): void
    {
        $children = collect($this->progressChildren());

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

    public function computeLeafProgress(): ?int
    {
        return null;
    }
}
