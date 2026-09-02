<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\FeatureSet;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tender_can_be_registered_with_several_owners(): void
    {
        $officer = User::factory()->role(UserRole::TenderOfficer)->create();
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($officer)->post('/tenders', [
            'title' => 'Joint bid',
            'priority' => 'high',
            'owner_ids' => [$a->id, $b->id],
        ]);

        $tender = Tender::firstOrFail();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $tender->owners->pluck('id')->all());
    }

    public function test_updating_owners_replaces_the_set(): void
    {
        $tender = Tender::factory()->create();
        [$a, $b, $c] = User::factory()->count(3)->create();
        $tender->owners()->sync([$a->id, $b->id]);

        $this->actingAs(User::factory()->admin()->create())->put("/tenders/{$tender->id}", [
            'title' => $tender->title,
            'priority' => 'medium',
            'lock_version' => $tender->lock_version,
            'owner_ids' => [$b->id, $c->id],
        ]);

        $this->assertEqualsCanonicalizing([$b->id, $c->id], $tender->fresh()->owners->pluck('id')->all());
    }

    public function test_a_task_can_have_multiple_assignees_and_they_may_edit_it(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $dev = User::factory()->role(UserRole::DevMember)->create();
        $featureSet = FeatureSet::factory()->for(Milestone::factory()->for(Project::factory()))->create();

        $this->actingAs($pm)->post("/feature-sets/{$featureSet->id}/tasks", [
            'title' => 'Shared task',
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_ids' => [$pm->id, $dev->id],
        ]);

        $task = Task::firstOrFail();
        $this->assertEqualsCanonicalizing([$pm->id, $dev->id], $task->assignees->pluck('id')->all());

        // A dev_member who is one of the assignees can toggle it.
        $this->actingAs($dev)->patch("/tasks/{$task->id}/toggle")->assertRedirect();
        $this->assertSame('done', $task->fresh()->status->value);
    }

    public function test_my_work_and_dashboard_use_the_pivot(): void
    {
        $dev = User::factory()->role(UserRole::DevMember)->create();
        $task = Task::factory()->create();
        $task->assignees()->attach($dev);

        $this->actingAs($dev)->get('/my-work')->assertOk()->assertSee($task->title);
        $this->actingAs($dev)->get('/')->assertOk();
        $this->assertSame(1, \App\Models\Task::open()->assignedTo($dev->id)->count());
    }
}
