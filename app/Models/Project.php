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
use App\Models\Concerns\HasOwners;
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
    use HasOwners;
    use LogsAudit;
    use RollsUpProgress;

    protected $fillable = [
        'tender_id', 'service_request_id', 'service_line_id',
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

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('position')->orderBy('id');
    }

    public function scopeItems(): HasMany
    {
        return $this->hasMany(ScopeItem::class)->orderBy('position')->orderBy('id');
    }

    public function phaseSignoffs(): HasMany
    {
        return $this->hasMany(PhaseSignoff::class)->latest('signed_at');
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

    // --- Requirements traceability ------------------------------------

    /** @return array{total:int, covered:int, percent:int} */
    public function scopeCoverage(): array
    {
        $items = $this->relationLoaded('scopeItems') ? $this->scopeItems : $this->scopeItems()->withCount('tasks')->get();
        $total = $items->count();
        $covered = $items->filter->isCovered()->count();

        return [
            'total' => $total,
            'covered' => $covered,
            'percent' => $total ? (int) round($covered / $total * 100) : 100,
        ];
    }

    public function nextScopeCode(): string
    {
        $max = (int) $this->scopeItems()
            ->selectRaw('MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)) as n')
            ->value('n');

        return 'S'.($max + 1);
    }

    // --- Progress roll-up ----------------------------------------------

    public function progressChildRelation(): ?string
    {
        return 'milestones';
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

    public static function phaseGatesEnforced(): bool
    {
        return (bool) config('projects.enforce_phase_gates', false);
    }

    /** Milestones tagged to $phase that are not yet Done. */
    public function incompleteMilestonesForPhase(ProjectPhase $phase)
    {
        $milestones = $this->relationLoaded('milestones') ? $this->milestones : $this->milestones()->get();

        return $milestones
            ->where('phase', $phase)
            ->reject(fn (Milestone $m) => $m->status === \App\Enums\WorkStatus::Done)
            ->values();
    }

    public function currentPhaseComplete(): bool
    {
        return ! $this->current_phase
            || $this->incompleteMilestonesForPhase($this->current_phase)->isEmpty();
    }

    /**
     * Whether new work (feature sets / tasks) may be added under a milestone
     * that belongs to $phase. Only restricted when gates are enforced.
     */
    public function workAllowedInPhase(?ProjectPhase $phase): bool
    {
        if (! $this->usesPhases() || ! self::phaseGatesEnforced() || ! $phase || ! $this->current_phase) {
            return true;
        }

        return $phase->order() <= $this->current_phase->order();
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
