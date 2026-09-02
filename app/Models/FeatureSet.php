<?php

namespace App\Models;

use App\Enums\WorkStatus;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasOptimisticLock;
use App\Models\Concerns\RollsUpProgress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureSet extends Model
{
    use HasComments;
    use HasFactory;
    use HasOptimisticLock;
    use RollsUpProgress;

    protected $fillable = [
        'milestone_id', 'name', 'description', 'status', 'position',
    ];

    protected $casts = [
        'status' => WorkStatus::class,
    ];

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position')->orderBy('id');
    }

    public function progressChildren(): iterable
    {
        return $this->tasks;
    }

    public function progressParent(): ?Model
    {
        return $this->milestone;
    }
}
