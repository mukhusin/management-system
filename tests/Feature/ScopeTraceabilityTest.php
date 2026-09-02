<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\FeatureSet;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ScopeItem;
use App\Models\Task;
use App\Models\Tender;
use App\Models\User;
use App\Services\ProjectInitiator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopeTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_splits_the_tender_scope_into_line_items(): void
    {
        $tender = Tender::factory()->won()->create([
            'scope_statement' => "- Build the intake form\n- Wire up notifications\n- Deploy to staging",
        ]);

        $project = app(ProjectInitiator::class)->fromTender($tender, User::factory()->role(UserRole::ProjectManager)->create());

        $this->assertCount(3, $project->scopeItems);
        $this->assertSame('S1', $project->scopeItems->first()->code);
        $this->assertSame('Build the intake form', $project->scopeItems->first()->description);
        $this->assertSame('tender', $project->scopeItems->first()->source);
    }

    public function test_coverage_reflects_linked_tasks(): void
    {
        $project = Project::factory()->create();
        $a = ScopeItem::factory()->for($project)->create(['code' => 'S1']);
        ScopeItem::factory()->for($project)->create(['code' => 'S2']);

        $this->assertSame(0, $project->scopeCoverage()['percent']);

        $task = Task::factory()->for(
            FeatureSet::factory()->for(Milestone::factory()->for($project))
        )->create();
        $a->tasks()->attach($task);

        $this->assertSame(50, $project->fresh()->load('scopeItems.tasks')->scopeCoverage()['percent']);
    }

    public function test_update_endpoint_links_only_same_project_tasks(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $project = Project::factory()->create();
        $item = ScopeItem::factory()->for($project)->create();

        $mine = Task::factory()->for(FeatureSet::factory()->for(Milestone::factory()->for($project)))->create();
        $foreign = Task::factory()->create();

        $this->actingAs($pm)->put("/scope-items/{$item->id}", [
            'description' => $item->description,
            'task_ids' => [$mine->id, $foreign->id],
        ]);

        $this->assertEqualsCanonicalizing([$mine->id], $item->fresh()->tasks->pluck('id')->all());
    }

    public function test_dev_member_cannot_manage_scope_items(): void
    {
        $item = ScopeItem::factory()->create();

        $this->actingAs(User::factory()->role(UserRole::DevMember)->create())
            ->put("/scope-items/{$item->id}", ['description' => 'hacked'])
            ->assertForbidden();
    }
}
