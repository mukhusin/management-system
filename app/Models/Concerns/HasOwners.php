<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A record with one or more owners (many-to-many with users through a
 * "<model>_owners" pivot).
 */
trait HasOwners
{
    protected function ownerPivotTable(): string
    {
        return Str::snake(class_basename($this)).'_owners';
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, $this->ownerPivotTable())->orderBy('name');
    }

    public function isOwnedBy(User|int|null $user): bool
    {
        if (! $user) {
            return false;
        }

        $id = $user instanceof User ? $user->id : $user;

        return $this->relationLoaded('owners')
            ? $this->owners->contains('id', $id)
            : $this->owners()->whereKey($id)->exists();
    }

    public function syncOwners(iterable $userIds): void
    {
        $this->owners()->sync(collect($userIds)->filter()->all());
    }

    public function ownerNames(): string
    {
        return $this->owners->pluck('name')->join(', ') ?: '—';
    }

    public function scopeOwnedBy(Builder $query, $userId): Builder
    {
        return $userId
            ? $query->whereHas('owners', fn (Builder $q) => $q->whereKey($userId))
            : $query;
    }
}
