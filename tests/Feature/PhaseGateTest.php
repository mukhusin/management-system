<?php

namespace Tests\Feature;

use App\Enums\ProjectPhase;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Models\FeatureSet;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseGateTest extends TestCase
{
    use RefreshDatabase;

    private function sdlcProject(): Project
    {
        return Project::factory()->sdlc()->create();
    }

    public function test_advance_records_a_signoff_and_an_audit_entry(): void
    {
        $project = $this->sdlcProject();
        $pm = User::factory()->role(UserRole::ProjectManager)->create();

        $this->actingAs($pm)
            ->patch("/projects/{$project->id}/advance-phase", ['note' => 'Reqs approved'])
            ->assertSessionHas('status');

        $this->assertSame(ProjectPhase::SystemDesign, $project->fresh()->current_phase);
        $this->assertDatabaseHas('phase_signoffs', [
            'project_id' => $project->id,
            'phase' => 'requirements',
            'signed_by' => $pm->id,
            'note' => 'Reqs approved',
        ]);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $project->id, 'event' => 'phase_advanced']);
    }

    public function test_enforced_gate_blocks_advance_with_open_milestones(): void
    {
        config(['projects.enforce_phase_gates' => true]);
        $project = $this->sdlcProject();
        Milestone::factory()->for($project)->create(['phase' => ProjectPhase::Requirements, 'status' => WorkStatus::InProgress]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/projects/{$project->id}/advance-phase", ['force' => 1])
            ->assertSessionHas('error');

        $this->assertSame(ProjectPhase::Requirements, $project->fresh()->current_phase);
    }

    public function test_admin_can_force_when_gates_are_not_enforced(): void
    {
        config(['projects.enforce_phase_gates' => false]);
        $project = $this->sdlcProject();
        Milestone::factory()->for($project)->create(['phase' => ProjectPhase::Requirements, 'status' => WorkStatus::InProgress]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/projects/{$project->id}/advance-phase", ['force' => 1]);

        $this->assertSame(ProjectPhase::SystemDesign, $project->fresh()->current_phase);
        $this->assertDatabaseHas('phase_signoffs', ['project_id' => $project->id, 'forced' => true]);
    }

    public function test_enforced_gate_blocks_work_in_a_future_phase(): void
    {
        config(['projects.enforce_phase_gates' => true]);
        $project = $this->sdlcProject(); // current_phase = requirements
        $future = Milestone::factory()->for($project)->create(['phase' => ProjectPhase::Deployment]);

        $this->actingAs(User::factory()->role(UserRole::ProjectManager)->create())
            ->post("/milestones/{$future->id}/feature-sets", ['name' => 'Too early', 'status' => 'not_started'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('feature_sets', 0);
    }

    public function test_work_in_current_phase_is_allowed_when_enforced(): void
    {
        config(['projects.enforce_phase_gates' => true]);
        $project = $this->sdlcProject();
        $now = Milestone::factory()->for($project)->create(['phase' => ProjectPhase::Requirements]);

        $this->actingAs(User::factory()->role(UserRole::ProjectManager)->create())
            ->post("/milestones/{$now->id}/feature-sets", ['name' => 'Discovery', 'status' => 'not_started']);

        $this->assertDatabaseCount('feature_sets', 1);
    }
}
