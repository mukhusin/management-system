<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\HasOptimisticLock;
use App\Models\Concerns\RollsUpProgress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subtask extends Model
{
    use HasFactory;
    use HasOptimisticLock;
    use RollsUpProgress;

    protected $fillable = ['task_id', 'assignee_id', 'title', 'status', 'progress', 'position'];

    protected $casts = [
        'status' => TaskStatus::class,
        'progress' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Subtask $subtask) {
            $implied = $subtask->status->impliedProgress();
            if ($implied !== null) {
                $subtask->progress = $implied;
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    // --- Progress roll-up (leaf) ------------------------------------

    public function progressChildren(): iterable
    {
        return [];
    }

    public function progressParent(): ?Model
    {
        return $this->task;
    }

    public function computeLeafProgress(): ?int
    {
        return $this->status->impliedProgress() ?? (int) $this->progress;
    }
}
