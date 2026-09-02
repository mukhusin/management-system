<?php

namespace App\Models\Concerns;

use App\Exceptions\StaleModelException;

/**
 * Optimistic concurrency control (SRS non-functional requirement).
 *
 * Edit forms echo the row's `lock_version` in a hidden field; on save the
 * controller calls $model->updateWithLock($validated, $submittedLockVersion).
 * If another write landed first the guarded UPDATE matches 0 rows and we
 * raise StaleModelException instead of silently clobbering.
 */
trait HasOptimisticLock
{
    public function initializeHasOptimisticLock(): void
    {
        if (! array_key_exists('lock_version', $this->attributes)) {
            $this->attributes['lock_version'] = 0;
        }
    }

    /**
     * Apply changes only if the row is still at $expectedVersion.
     *
     * @throws StaleModelException
     */
    public function updateWithLock(array $attributes, int $expectedVersion): bool
    {
        $this->fill($attributes);

        $dirty = $this->getDirty();
        unset($dirty['lock_version']);

        $affected = static::query()
            ->whereKey($this->getKey())
            ->where('lock_version', $expectedVersion)
            ->update([
                ...$dirty,
                'lock_version' => $expectedVersion + 1,
                $this->getUpdatedAtColumn() => $this->freshTimestamp(),
            ]);

        if ($affected === 0) {
            throw new StaleModelException(class_basename($this), $this->getKey());
        }

        $this->forceFill([
            ...$dirty,
            'lock_version' => $expectedVersion + 1,
        ])->syncOriginal();

        return true;
    }
}
