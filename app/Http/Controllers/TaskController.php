<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\FeatureSet;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function mine(Request $request)
    {
        $tasks = Task::with('featureSet.milestone.project')
            ->where('assignee_id', $request->user()->id)
            ->orderByRaw("FIELD(status, 'blocked','in_progress','code_review','todo','done')")
            ->orderBy('due_date')
            ->get()
            ->groupBy(fn (Task $t) => $t->featureSet?->milestone?->project?->name ?? 'Unassigned');

        return view('tasks.mine', ['grouped' => $tasks]);
    }

    public function store(Request $request, FeatureSet $featureSet)
    {
        $milestone = $featureSet->milestone;
        if (! $milestone->project->workAllowedInPhase($milestone->phase)) {
            return back()->with('error', 'This milestone belongs to a later phase — advance the project first (phase gates are enforced).');
        }

        $data = $this->rules($request);
        $data['position'] = (int) $featureSet->tasks()->max('position') + 1;
        $featureSet->tasks()->create($data);

        return back()->with('status', 'Task added.');
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeEdit($request, $task);
        $task->updateWithLock($this->rules($request), (int) $request->integer('lock_version'));

        return back()->with('status', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return back()->with('status', 'Task removed.');
    }

    public function toggle(Request $request, Task $task)
    {
        $this->authorizeEdit($request, $task);

        $task->status = $task->status === TaskStatus::Done ? TaskStatus::Todo : TaskStatus::Done;
        $task->save();

        return back();
    }

    private function authorizeEdit(Request $request, Task $task): void
    {
        $user = $request->user();
        abort_unless(
            $user->can('projects.manage_work')
            || ($user->can('work.execute') && $task->assignee_id === $user->id),
            403,
        );
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:'.implode(',', TaskStatus::values())],
            'priority' => ['required', 'in:'.implode(',', Priority::values())],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}
