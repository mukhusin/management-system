<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Gives a model a polymorphic audit trail. Records a "created" event
 * automatically; callers record state/baseline/etc. events explicitly.
 */
trait LogsAudit
{
    /** Set false around bulk system operations (e.g. tender ingestion). */
    public static bool $auditCreation = true;

    public static function bootLogsAudit(): void
    {
        static::created(function ($model) {
            if (static::$auditCreation) {
                $model->audit('created');
            }
        });
    }

    /** Run a callback without recording "created" audit events. */
    public static function withoutCreationAudit(callable $callback): mixed
    {
        $previous = static::$auditCreation;
        static::$auditCreation = false;

        try {
            return $callback();
        } finally {
            static::$auditCreation = $previous;
        }
    }

    /** Models override this with the fields whose edits must be audited. */
    public function baselineFields(): array
    {
        return [];
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest('id');
    }

    public function audit(string $event, ?array $old = null, ?array $new = null): AuditLog
    {
        return AuditLog::record($this, $event, $old, $new);
    }

    /**
     * If any BASELINE_FIELDS changed, record a baseline_changed audit entry.
     * Call after a successful update.
     */
    public function auditBaselineChanges(array $before): void
    {
        $fields = $this->baselineFields();
        $old = [];
        $new = [];

        foreach ($fields as $field) {
            $wasValue = $before[$field] ?? null;
            $isValue = $this->getAttribute($field);
            $was = $wasValue instanceof \BackedEnum ? $wasValue->value : (string) $wasValue;
            $is = $isValue instanceof \BackedEnum ? $isValue->value : (string) $isValue;

            if ($was !== $is) {
                $old[$field] = $wasValue instanceof \BackedEnum ? $wasValue->value : $wasValue;
                $new[$field] = $isValue instanceof \BackedEnum ? $isValue->value : $isValue;
            }
        }

        if ($new !== []) {
            $this->audit('baseline_changed', $old, $new);
        }
    }
}
