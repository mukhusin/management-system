<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasDueDate;
use App\Models\Concerns\HasOptimisticLock;
use App\Models\Concerns\RollsUpProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasComments;
    use HasDueDate;
    use HasFactory;
    use HasOptimisticLock;
    use RollsUpProgress;

    protected $fillable = [
        'feature_set_id', 'assignee_id', 'title', 'description',
        'status', 'priority', 'progress', 'due_date', 'position',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => Priority::class,
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'progress' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            $implied = $task->status->impliedProgress();
            if ($implied !== null) {
                $task->progress = $implied;
            }
            $task->completed_at = $task->status === TaskStatus::Done ? ($task->completed_at ?? now()) : null;
        });
    }

    public function featureSet(): BelongsTo
    {
        return $this->belongsTo(FeatureSet::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class)->orderBy('position')->orderBy('id');
    }

    public function project(): ?Project
    {
        return $this->featureSet?->milestone?->project;
    }

    // --- Progress roll-up --------------------------------------------

    public function progressChildRelation(): ?string
    {
        return 'subtasks';
    }

    public function progressParent(): ?Model
    {
        return $this->featureSet()->first();
    }

    public function computeLeafProgress(): ?int
    {
        // No sub-tasks: progress is driven by the task's own status/value.
        return $this->status->impliedProgress() ?? (int) $this->progress;
    }

    // --- Scopes -----------------------------------------------------

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', TaskStatus::Done->value);
    }

    public function scopeAssignedTo(Builder $query, $userId): Builder
    {
        return $query->where('assignee_id', $userId);
    }
}
