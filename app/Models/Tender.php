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
    ];

    protected $casts = [
        'published_date' => 'date',
        'deadline_date' => 'date',
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
