<?php

namespace App\Models;

use App\Enums\WorkStatus;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasOptimisticLock;
use App\Models\Concerns\RollsUpProgress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phase extends Model
{
    use HasComments;
    use HasFactory;
    use HasOptimisticLock;
    use RollsUpProgress;

    protected $fillable = [
        'project_id', 'name', 'description', 'status', 'position',
        'starts_on', 'ends_on',
    ];

    protected $casts = [
        'status' => WorkStatus::class,
        'starts_on' => 'date',
        'ends_on' => 'date',
        'gate_signed_at' => 'datetime',
        'gate_forced' => 'boolean',
        'progress' => 'integer',
    ];

    /** Seed a project with the standard 5 SDLC phases (SRS SDLC-1). */
    public static function seedSdlc(Project $project): void
    {
        foreach (\App\Enums\ProjectPhase::cases() as $i => $case) {
            $project->phases()->create([
                'name' => $case->label(),
                'status' => $i === 0 ? WorkStatus::InProgress : WorkStatus::NotStarted,
                'position' => $i,
            ]);
        }
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('position')->orderBy('id');
    }

    public function scopeItems(): HasMany
    {
        return $this->hasMany(ScopeItem::class)->orderBy('position')->orderBy('id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'phase_assignees')->orderBy('name');
    }

    public function gateSignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gate_signed_by');
    }

    // --- Gate ---------------------------------------------------------

    public function isSignedOff(): bool
    {
        return $this->status === WorkStatus::Done && $this->gate_signed_at !== null;
    }

    public function incompleteMilestones()
    {
        $milestones = $this->relationLoaded('milestones') ? $this->milestones : $this->milestones()->get();

        return $milestones->reject(fn (Milestone $m) => $m->status === WorkStatus::Done)->values();
    }

    // --- Progress roll-up --------------------------------------------

    public function progressChildRelation(): ?string
    {
        return 'milestones';
    }

    public function progressParent(): ?Model
    {
        return $this->project()->first();
    }

    // --- Traceability ------------------------------------------------

    /** @return array{total:int, covered:int, percent:int} */
    public function scopeCoverage(): array
    {
        $items = $this->relationLoaded('scopeItems') ? $this->scopeItems : $this->scopeItems()->with('tasks')->get();
        $total = $items->count();
        $covered = $items->filter->isCovered()->count();

        return ['total' => $total, 'covered' => $covered, 'percent' => $total ? (int) round($covered / $total * 100) : 100];
    }
}
