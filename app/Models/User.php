<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // --- RBAC -------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === UserRole::SystemAdmin;
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(PermissionOverride::class);
    }

    /** Overrides keyed by permission => granted(bool). */
    public function overrideMap(): Collection
    {
        $rows = $this->relationLoaded('overrides') ? $this->overrides : $this->overrides()->get();

        return $rows->mapWithKeys(fn (PermissionOverride $o) => [$o->permission => (bool) $o->granted]);
    }

    /**
     * Resolve a permission: system_admin gets everything; otherwise the role's
     * default set, plus/minus this user's explicit overrides.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $overrides = $this->overrideMap();

        if ($overrides->has($permission)) {
            return $overrides->get($permission);
        }

        $defaults = config('permissions.roles.'.($this->role?->value ?? ''), []);

        return in_array('*', $defaults, true) || in_array($permission, $defaults, true);
    }

    /** @return array<int, string> effective permission keys */
    public function effectivePermissions(): array
    {
        return array_values(array_filter(
            array_keys(config('permissions.catalog', [])),
            fn (string $key) => $this->hasPermission($key),
        ));
    }

    // --- Work relations --------------------------------------------------

    public function ownedTenders(): BelongsToMany
    {
        return $this->belongsToMany(Tender::class, 'tender_owners');
    }

    public function ownedServiceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'owner_id');
    }

    public function ownedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_owners');
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignees');
    }

    public function assignedSubtasks(): HasMany
    {
        return $this->hasMany(Subtask::class, 'assignee_id');
    }

    public function ownedTrackerItems(): HasMany
    {
        return $this->hasMany(TrackerItem::class, 'owner_id');
    }
}
