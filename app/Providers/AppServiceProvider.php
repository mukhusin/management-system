<?php

namespace App\Providers;

use App\Models\FeatureSet;
use App\Models\Milestone;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use App\Observers\ProgressRollupObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // This app ships its own CSS, not Tailwind/Bootstrap, so use a
        // pagination view styled to match (resources/views/pagination/).
        Paginator::defaultView('pagination.app');
        Paginator::defaultSimpleView('pagination.app');

        $this->registerPermissionGates();

        foreach ([Subtask::class, Task::class, FeatureSet::class, Milestone::class] as $model) {
            $model::observe(ProgressRollupObserver::class);
        }
    }

    /**
     * Register one Gate ability per permission key in config/permissions.php.
     * The resolution (role defaults ± per-user overrides, system_admin = all)
     * lives on the User model.
     */
    private function registerPermissionGates(): void
    {
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        foreach (array_keys(config('permissions.catalog', [])) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
    }
}
