<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScopeItem extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'phase_id', 'code', 'description', 'source', 'position'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'scope_item_task');
    }

    public function isCovered(): bool
    {
        if ($this->relationLoaded('tasks')) {
            return $this->tasks->isNotEmpty();
        }

        return isset($this->tasks_count) ? $this->tasks_count > 0 : $this->tasks()->exists();
    }

    /** Aggregate progress of the tasks covering this scope item (0 when uncovered). */
    public function coverageProgress(): int
    {
        $tasks = $this->relationLoaded('tasks') ? $this->tasks : $this->tasks()->get();

        return $tasks->isEmpty() ? 0 : (int) round($tasks->avg('progress'));
    }
}
