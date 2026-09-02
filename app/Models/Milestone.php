<?php

namespace App\Models;

use App\Enums\ProjectPhase;
use App\Enums\WorkStatus;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasDueDate;
use App\Models\Concerns\HasOptimisticLock;
use App\Models\Concerns\RollsUpProgress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    use HasComments;
    use HasDueDate;
    use HasFactory;
    use HasOptimisticLock;
    use RollsUpProgress;

    protected $fillable = [
        'project_id', 'name', 'description', 'phase', 'status', 'due_date', 'position',
    ];

    protected $casts = [
        'status' => WorkStatus::class,
        'phase' => ProjectPhase::class,
        'due_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function featureSets(): HasMany
    {
        return $this->hasMany(FeatureSet::class)->orderBy('position')->orderBy('id');
    }

    public function progressChildRelation(): ?string
    {
        return 'featureSets';
    }

    public function progressParent(): ?Model
    {
        return $this->project;
    }
}
