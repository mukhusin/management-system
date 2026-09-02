<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\ServiceLineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ServiceLineSeeder::class);
    }

    public function test_core_pages_load_for_a_project_manager(): void
    {
        $this->actingAs(User::factory()->role(UserRole::ProjectManager)->create());

        foreach (['/', '/tenders', '/service-requests', '/projects', '/my-work', '/tracker', '/reports', '/notifications', '/audit'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_create_forms_load_for_the_right_role(): void
    {
        $this->actingAs(User::factory()->role(UserRole::ProjectManager)->create());
        foreach (['/service-requests/create', '/projects/create', '/tracker/create'] as $path) {
            $this->get($path)->assertOk();
        }

        $this->actingAs(User::factory()->role(UserRole::TenderOfficer)->create());
        $this->get('/tenders/create')->assertOk();
    }

    public function test_admin_only_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/users')->assertOk();
        $this->get('/service-lines')->assertOk();
        $this->get('/import')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/tenders')->assertRedirect('/login');
    }
}
