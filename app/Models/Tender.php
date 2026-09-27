<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TenderState;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasDueDate;
use App\Models\Concerns\HasOptimisticLock;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasOwners;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tender extends Model
{
    use HasAttachments;
    use HasComments;
    use HasDueDate;
    use HasFactory;
    use HasOptimisticLock;
    use HasOwners;
    use LogsAudit;

    protected $fillable = [
        'user_id', 'source', 'external_id', 'title', 'description',
        'country', 'sector', 'buyer', 'client', 'value', 'estimated_value',
        'currency', 'published_date', 'deadline_date', 'url', 'raw',
        'state', 'priority', 'scope_statement', 'service_line_id',
        'adopted_at', 'adopted_by',
    ];

    protected $casts = [
        'published_date' => 'date',
        'deadline_date' => 'date',
        'adopted_at' => 'datetime',
        'raw' => 'array',
        'value' => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'state' => TenderState::class,
        'priority' => Priority::class,
    ];

    public function dueDateColumn(): string
    {
        return 'deadline_date';
    }

    /** Baseline fields whose edits are audited (SRS financial-parameter compliance). */
    public function baselineFields(): array
    {
        return ['value', 'estimated_value', 'currency', 'deadline_date'];
    }

    // --- Relations ------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceLine(): BelongsTo
    {
        return $this->belongsTo(ServiceLine::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function adopter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adopted_by');
    }

    // --- Adoption (opportunity -> pipeline) ---------------------------

    public function isAdopted(): bool
    {
        return $this->adopted_at !== null;
    }

    /**
     * Move an ingested opportunity into the pipeline so it can be tracked
     * through its lifecycle.
     */
    public function adopt(User $actor): void
    {
        if ($this->isAdopted()) {
            return;
        }

        $this->forceFill(['adopted_at' => now(), 'adopted_by' => $actor->id])->save();

        if ($this->owners()->doesntExist()) {
            $this->owners()->attach($actor->id);
        }

        $this->audit('adopted', null, ['by' => $actor->name]);
    }

    public function scopeAdopted(Builder $query): Builder
    {
        return $query->whereNotNull('adopted_at');
    }

    public function scopeOpportunities(Builder $query): Builder
    {
        return $query->whereNull('adopted_at');
    }

    // --- Per-user shortlist / hide (opportunities feed only) ----------

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tender_saves');
    }

    public function dismissedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tender_dismissals');
    }

    public function isSavedBy(User $user): bool
    {
        return $this->relationLoaded('savedBy')
            ? $this->savedBy->contains('id', $user->id)
            : $this->savedBy()->whereKey($user->id)->exists();
    }

    public function isDismissedBy(User $user): bool
    {
        return $this->relationLoaded('dismissedBy')
            ? $this->dismissedBy->contains('id', $user->id)
            : $this->dismissedBy()->whereKey($user->id)->exists();
    }

    public function toggleSavedBy(User $user): bool
    {
        $attached = ! $this->savedBy()->whereKey($user->id)->exists();
        $this->savedBy()->toggle($user->id);

        return $attached;
    }

    public function toggleDismissedBy(User $user): bool
    {
        $dismissed = ! $this->dismissedBy()->whereKey($user->id)->exists();
        $this->dismissedBy()->toggle($user->id);

        return $dismissed;
    }

    public function scopeSavedByUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('savedBy', fn (Builder $q) => $q->whereKey($userId));
    }

    public function scopeNotDismissedBy(Builder $query, ?int $userId): Builder
    {
        return $userId
            ? $query->whereDoesntHave('dismissedBy', fn (Builder $q) => $q->whereKey($userId))
            : $query;
    }

    // --- Lightweight query parsing (Opportunities "Ask" box) ----------

    public function scopeMinValue(Builder $query, ?float $min): Builder
    {
        return $min ? $query->where('value', '>=', $min) : $query;
    }

    public function scopeClosingWithinDays(Builder $query, ?int $days): Builder
    {
        return $days === null
            ? $query
            : $query->whereNotNull('deadline_date')->whereBetween('deadline_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    // --- Scopes --------------------------------------------------------

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('buyer', 'like', "%{$term}%")
              ->orWhere('client', 'like', "%{$term}%");
        });
    }

    public function scopeFromSource(Builder $query, ?string $source): Builder
    {
        return $source ? $query->where('source', $source) : $query;
    }

    public function scopeInCountry(Builder $query, ?string $country): Builder
    {
        return $country ? $query->where('country', $country) : $query;
    }

    public function scopeState(Builder $query, TenderState|string|null $state): Builder
    {
        return $state ? $query->where('state', $state instanceof TenderState ? $state->value : $state) : $query;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('deadline_date')
              ->orWhere('deadline_date', '>=', now()->toDateString());
        });
    }
}
