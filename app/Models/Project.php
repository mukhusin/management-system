<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\ProjectPhase;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasDueDate;
use App\Models\Concerns\HasOptimisticLock;
use App\Models\Concerns\LogsAudit;
use App\Models\Concerns\RollsUpProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Project extends Model
{
    use HasAttachments;
    use HasComments;
    use HasDueDate;
    use HasFactory;
    use HasOptimisticLock;
    use LogsAudit;
    use RollsUpProgress;

    protected $fillable = [
        'tender_id', 'service_request_id', 'service_line_id', 'owner_id',
        'name', 'type', 'status', 'priority', 'current_phase',
        'description', 'scope_statement', 'client', 'budget', 'currency',
        'start_date', 'target_deadline', 'completed_at', 'next_action', 'remarks',
    ];

    protected $casts = [
        'type' => ProjectType::class,
        'status' => ProjectStatus::class,
        'priority' => Priority::class,
        'current_phase' => ProjectPhase::class,
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'target_deadline' => 'date',
        'completed_at' => 'datetime',
    ];

    public function dueDateColumn(): string
    {
        return 'target_deadline';
    }

    public function baselineFields(): array
    {
        return ['budget', 'currency', 'target_deadline'];
    }

    // --- Relations ------------------------------------------------------

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function serviceLine(): BelongsTo
    {
        return $this->belongsTo(ServiceLine::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('position')->orderBy('id');
    }

    /** All tasks under this project, flattened. */
    public function allTasks(): Collection
    {
        return $this->milestones()
            ->with('featureSets.tasks')
            ->get()
            ->flatMap->featureSets
            ->flatMap->tasks;
    }

    // --- Progress roll-up ----------------------------------------------

    public function progressChildren(): iterable
    {
        return $this->milestones;
    }

    public function progressParent(): ?Model
    {
        return null;
    }

    // --- Phase gate ---------------------------------------------------

    public function usesPhases(): bool
    {
        return $this->type === ProjectType::Sdlc;
    }

    public function scopeStatus(Builder $query, ProjectStatus|string|null $status): Builder
    {
        if (! $status) {
            return $query;
        }

        return $query->where('status', $status instanceof ProjectStatus ? $status->value : $status);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('client', 'like', "%{$term}%"));
    }
}
