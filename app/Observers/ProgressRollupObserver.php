<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * After any work item in the Project → Milestone → Feature Set → Task →
 * Sub-task tree is saved or removed, recompute its parent's cached progress
 * (which bubbles the rest of the way to the project).
 */
class ProgressRollupObserver
{
    public function saved(Model $model): void
    {
        $model->progressParent()?->recomputeProgress();
    }

    public function deleted(Model $model): void
    {
        $model->progressParent()?->recomputeProgress();
    }
}
