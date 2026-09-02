<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TrackerCategory;
use App\Enums\TrackerStatus;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasDueDate;
use App\Models\Concerns\HasOptimisticLock;
use App\Models\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerItem extends Model
{
    use HasAttachments;
    use HasComments;
    use HasDueDate;
    use HasFactory;
    use HasOptimisticLock;
    use LogsAudit;

    protected $fillable = [
        'reference', 'category', 'title', 'description', 'owner_id', 'service_line_id',
        'status', 'priority', 'progress', 'next_action', 'entry_date', 'due_date', 'remarks',
    ];

    protected $casts = [
        'category' => TrackerCategory::class,
        'status' => TrackerStatus::class,
        'priority' => Priority::class,
        'progress' => 'integer',
        'entry_date' => 'date',
        'due_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (TrackerItem $item) {
            if (blank($item->reference)) {
                $item->reference = static::nextReference();
            }
        });
    }

    public static function nextReference(): string
    {
        $prefix = config('tracker.reference_prefix', 'EMREC');

        $last = static::query()
            ->where('reference', 'like', $prefix.'-%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(reference, "-", -1) AS UNSIGNED) DESC')
            ->value('reference');

        $n = $last ? ((int) str($last)->afterLast('-')->value()) + 1 : 1;

        return sprintf('%s-%03d', $prefix, $n);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function serviceLine(): BelongsTo
    {
        return $this->belongsTo(ServiceLine::class);
    }

    public function scopeCategory(Builder $query, TrackerCategory|string|null $category): Builder
    {
        if (! $category) {
            return $query;
        }

        return $query->where('category', $category instanceof TrackerCategory ? $category->value : $category);
    }

    public function scopeStatus(Builder $query, TrackerStatus|string|null $status): Builder
    {
        if (! $status) {
            return $query;
        }

        return $query->where('status', $status instanceof TrackerStatus ? $status->value : $status);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('title', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->orWhere('reference', 'like', "%{$term}%"));
    }
}
