<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\ServiceRequestSource;
use App\Enums\ServiceRequestState;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasOptimisticLock;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    use HasAttachments;
    use HasComments;
    use HasFactory;
    use HasOptimisticLock;
    use LogsAudit;

    protected $fillable = [
        'reference', 'source', 'state', 'priority', 'service_line_id', 'owner_id',
        'client', 'contact_name', 'contact_email', 'contact_phone',
        'summary', 'details', 'estimated_value', 'currency',
    ];

    protected $casts = [
        'source' => ServiceRequestSource::class,
        'state' => ServiceRequestState::class,
        'priority' => Priority::class,
        'estimated_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $request) {
            if (blank($request->reference)) {
                $request->reference = 'SR-'.str(now()->format('ymd'))->value()
                    .'-'.str(random_int(100, 999))->value();
            }
        });
    }

    public function baselineFields(): array
    {
        return ['estimated_value', 'currency'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function serviceLine(): BelongsTo
    {
        return $this->belongsTo(ServiceLine::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function scopeState(Builder $query, ServiceRequestState|string|null $state): Builder
    {
        if (! $state) {
            return $query;
        }

        return $query->where('state', $state instanceof ServiceRequestState ? $state->value : $state);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('summary', 'like', "%{$term}%")
            ->orWhere('client', 'like', "%{$term}%")
            ->orWhere('contact_name', 'like', "%{$term}%")
            ->orWhere('reference', 'like', "%{$term}%"));
    }
}
