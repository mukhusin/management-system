<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\FeatureSet;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressRollupTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_bubbles_from_subtask_to_project(): void
    {
        $project = Project::factory()->create();
        $milestone = Milestone::factory()->for($project)->create();
        $featureSet = FeatureSet::factory()->for($milestone)->create();
        $task = Task::factory()->for($featureSet)->create();
        [$a, $b] = [
            Subtask::factory()->for($task)->create(),
            Subtask::factory()->for($task)->create(),
        ];

        $this->assertSame(0, $project->fresh()->progress);

        $a->update(['status' => TaskStatus::Done]);

        // one of two sub-tasks done => task 50 => feature set 50 => milestone 50 => project 50
        $this->assertSame(50, $task->fresh()->progress);
        $this->assertSame(50, $featureSet->fresh()->progress);
        $this->assertSame(50, $milestone->fresh()->progress);
        $this->assertSame(50, $project->fresh()->progress);

        $b->update(['status' => TaskStatus::Done]);
        $this->assertSame(100, $project->fresh()->progress);
    }

    public function test_task_done_sets_completed_at(): void
    {
        $task = Task::factory()->create();
        $this->assertNull($task->completed_at);

        $task->update(['status' => TaskStatus::Done]);
        $this->assertNotNull($task->fresh()->completed_at);

        $task->update(['status' => TaskStatus::Todo]);
        $this->assertNull($task->fresh()->completed_at);
        $this->assertSame(0, $task->fresh()->progress);
    }

    public function test_project_with_no_milestones_stays_zero(): void
    {
        $project = Project::factory()->create();
        $project->recomputeProgress();

        $this->assertSame(0, $project->fresh()->progress);
    }
}
