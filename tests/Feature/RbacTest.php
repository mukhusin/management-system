<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PermissionOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_defaults_resolve(): void
    {
        $officer = User::factory()->role(UserRole::TenderOfficer)->create();

        $this->assertTrue($officer->hasPermission('tenders.transition'));
        $this->assertFalse($officer->hasPermission('projects.manage_work'));
        $this->assertFalse($officer->hasPermission('users.manage'));
    }

    public function test_system_admin_has_everything(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (array_keys(config('permissions.catalog')) as $key) {
            $this->assertTrue($admin->hasPermission($key), $key);
        }
    }

    public function test_per_user_override_revokes_a_default(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $this->assertTrue($pm->hasPermission('projects.manage_work'));

        PermissionOverride::create(['user_id' => $pm->id, 'permission' => 'projects.manage_work', 'granted' => false]);

        $this->assertFalse($pm->fresh()->hasPermission('projects.manage_work'));
    }

    public function test_per_user_override_grants_beyond_the_role(): void
    {
        $dev = User::factory()->role(UserRole::DevMember)->create();
        $this->assertFalse($dev->hasPermission('audit.view'));

        PermissionOverride::create(['user_id' => $dev->id, 'permission' => 'audit.view', 'granted' => true]);

        $this->assertTrue($dev->fresh()->hasPermission('audit.view'));
    }

    public function test_dev_member_is_blocked_from_admin_pages(): void
    {
        $this->actingAs(User::factory()->role(UserRole::DevMember)->create());

        $this->get('/users')->assertForbidden();
        $this->get('/audit')->assertForbidden();
        $this->get('/projects/create')->assertForbidden();
    }

    public function test_admin_reaches_admin_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/users')->assertOk();
        $this->get('/audit')->assertOk();
    }
}
