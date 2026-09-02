<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\Request;

class SubtaskController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $data = $this->rules($request);
        $data['position'] = (int) $task->subtasks()->max('position') + 1;
        $task->subtasks()->create($data);

        return back()->with('status', 'Sub-task added.');
    }

    public function update(Request $request, Subtask $subtask)
    {
        $this->guard($request, $subtask);
        $subtask->updateWithLock($this->rules($request), (int) $request->integer('lock_version'));

        return back()->with('status', 'Sub-task updated.');
    }

    public function destroy(Subtask $subtask)
    {
        $subtask->delete();

        return back()->with('status', 'Sub-task removed.');
    }

    public function toggle(Request $request, Subtask $subtask)
    {
        $this->guard($request, $subtask);
        $subtask->status = $subtask->status === TaskStatus::Done ? TaskStatus::Todo : TaskStatus::Done;
        $subtask->save();

        return back();
    }

    private function guard(Request $request, Subtask $subtask): void
    {
        $user = $request->user();
        abort_unless(
            $user->can('projects.manage_work')
            || ($user->can('work.execute') && in_array($user->id, [$subtask->assignee_id, $subtask->task?->assignee_id], true)),
            403,
        );
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:'.implode(',', TaskStatus::values())],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
    }
}
