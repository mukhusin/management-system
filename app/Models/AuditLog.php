<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

/**
 * Append-only audit record (SRS: immutable audit logging for status shifts,
 * project initiation, and financial/value edits). Rows can be created and
 * read but never updated or deleted.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'auditable_type', 'auditable_id', 'user_id', 'event', 'old_values', 'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Audit logs are immutable.'));
        static::deleting(fn () => throw new RuntimeException('Audit logs are immutable.'));
    }

    public static function record(Model $model, string $event, ?array $old = null, ?array $new = null, ?int $userId = null): self
    {
        return static::create([
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
        ]);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function summary(): string
    {
        return match ($this->event) {
            'state_changed' => sprintf(
                'State %s → %s',
                $this->old_values['state'] ?? '?',
                $this->new_values['state'] ?? '?',
            ),
            'baseline_changed' => 'Baseline edited: '.implode(', ', array_keys($this->new_values ?? [])),
            'project_initiated' => 'Project initiated',
            'created' => 'Created',
            'comment_added' => 'Comment added',
            'attachment_added' => 'Attachment added: '.($this->new_values['name'] ?? ''),
            'phase_advanced' => sprintf(
                'Phase %s → %s',
                $this->old_values['phase'] ?? '?',
                $this->new_values['phase'] ?? '?',
            ),
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }
}
