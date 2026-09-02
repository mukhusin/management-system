<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Phase;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_phase_can_be_added_with_its_own_description_and_assignees(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $project = Project::factory()->create();
        $dev = User::factory()->create();

        $this->actingAs($pm)->post("/projects/{$project->id}/phases", [
            'name' => 'Discovery',
            'description' => 'Understand the current process',
            'status' => 'in_progress',
        ]);

        $phase = Phase::firstOrFail();
        $this->assertSame('Discovery', $phase->name);
        $this->assertSame('Understand the current process', $phase->description);
        $this->assertSame(0, $phase->position);

        $this->actingAs($pm)->put("/phases/{$phase->id}", [
            'name' => $phase->name,
            'status' => 'in_progress',
            'lock_version' => $phase->lock_version,
            'assignee_ids' => [$dev->id],
        ]);

        $this->assertTrue($phase->fresh()->assignees->contains($dev));
    }

    public function test_requirements_can_be_attached_to_a_phase(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $project = Project::factory()->create();
        $phase = Phase::factory()->for($project)->create();

        $this->actingAs($pm)->post("/projects/{$project->id}/scope-items", [
            'description' => 'The system shall email a receipt',
            'phase_id' => $phase->id,
        ]);

        $this->assertSame(1, $phase->scopeItems()->count());
        $this->assertSame(0, $project->scopeItems()->count()); // project-level = phase-null only
        $this->assertSame(1, $project->allScopeItems()->count());
    }

    public function test_dev_member_cannot_add_a_phase(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->role(UserRole::DevMember)->create())
            ->post("/projects/{$project->id}/phases", ['name' => 'X', 'status' => 'not_started'])
            ->assertForbidden();
    }
}
