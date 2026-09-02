<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Models\Milestone;
use App\Models\Phase;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_sdlc_project_is_seeded_with_the_five_phases(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();

        $this->actingAs($pm)->post('/projects', [
            'name' => 'Platform', 'type' => 'sdlc', 'status' => 'not_started', 'priority' => 'medium',
        ]);

        $this->assertSame(5, Project::firstOrFail()->phases()->count());
        $this->assertSame('Requirements', Project::first()->phases()->orderBy('position')->first()->name);
    }

    public function test_sign_off_marks_the_phase_done_and_audits(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $project = Project::factory()->create();
        $phase = Phase::factory()->for($project)->create(['status' => WorkStatus::InProgress, 'position' => 0]);
        Phase::factory()->for($project)->create(['position' => 1]);

        $this->actingAs($pm)
            ->patch("/phases/{$phase->id}/sign-off", ['note' => 'Approved'])
            ->assertSessionHas('status');

        $this->assertTrue($phase->fresh()->isSignedOff());
        $this->assertSame(WorkStatus::InProgress, $project->phases()->orderBy('position')->skip(1)->first()->status);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $project->id, 'event' => 'phase_signed_off']);
    }

    public function test_enforced_gate_blocks_sign_off_with_open_milestones(): void
    {
        config(['projects.enforce_phase_gates' => true]);
        $project = Project::factory()->create();
        $phase = Phase::factory()->for($project)->create(['status' => WorkStatus::InProgress]);
        Milestone::factory()->for($phase)->create(['status' => WorkStatus::InProgress]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/phases/{$phase->id}/sign-off", ['force' => 1])
            ->assertSessionHas('error');

        $this->assertFalse($phase->fresh()->isSignedOff());
    }

    public function test_admin_can_force_sign_off_when_gates_are_not_enforced(): void
    {
        config(['projects.enforce_phase_gates' => false]);
        $project = Project::factory()->create();
        $phase = Phase::factory()->for($project)->create(['status' => WorkStatus::InProgress]);
        Milestone::factory()->for($phase)->create(['status' => WorkStatus::InProgress]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/phases/{$phase->id}/sign-off", ['force' => 1]);

        $this->assertTrue($phase->fresh()->isSignedOff());
        $this->assertTrue($phase->fresh()->gate_forced);
    }

    public function test_enforced_gate_blocks_milestones_in_a_future_phase(): void
    {
        config(['projects.enforce_phase_gates' => true]);
        $project = Project::factory()->create();
        Phase::factory()->for($project)->create(['status' => WorkStatus::InProgress, 'position' => 0]);
        $future = Phase::factory()->for($project)->create(['position' => 1]);

        $this->actingAs(User::factory()->role(UserRole::ProjectManager)->create())
            ->post("/phases/{$future->id}/milestones", ['name' => 'Too early', 'status' => 'not_started'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('milestones', 0);
    }

    public function test_milestones_in_the_current_phase_are_allowed_when_enforced(): void
    {
        config(['projects.enforce_phase_gates' => true]);
        $project = Project::factory()->create();
        $current = Phase::factory()->for($project)->create(['status' => WorkStatus::InProgress, 'position' => 0]);

        $this->actingAs(User::factory()->role(UserRole::ProjectManager)->create())
            ->post("/phases/{$current->id}/milestones", ['name' => 'Discovery', 'status' => 'not_started']);

        $this->assertDatabaseCount('milestones', 1);
    }
}
