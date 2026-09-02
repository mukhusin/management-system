<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ScopeItem;
use Illuminate\Http\Request;

class ScopeItemController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'description' => ['required', 'string', 'max:2000'],
            'phase_id' => ['nullable', 'integer'],
        ]);

        // A phase filter is only honoured if it belongs to this project.
        $phaseId = $project->phases()->whereKey($data['phase_id'] ?? null)->value('id');

        $project->allScopeItems()->create([
            'phase_id' => $phaseId,
            'code' => $project->nextScopeCode(),
            'description' => $data['description'],
            'source' => 'manual',
            'position' => (int) $project->allScopeItems()->max('position') + 1,
        ]);

        return back()->with('status', 'Requirement added.');
    }

    public function update(Request $request, ScopeItem $scopeItem)
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'description' => ['required', 'string', 'max:2000'],
            'task_ids' => ['array'],
            'task_ids.*' => ['integer'],
        ]);

        $scopeItem->update(['description' => $data['description']]);

        // Only allow linking tasks that belong to the same project.
        $valid = $scopeItem->project->allTasks()->pluck('id');
        $scopeItem->tasks()->sync(collect($data['task_ids'] ?? [])->intersect($valid));

        return back()->with('status', 'Scope item updated.');
    }

    public function destroy(Request $request, ScopeItem $scopeItem)
    {
        $this->authorizeManage($request);
        $scopeItem->delete();

        return back()->with('status', 'Scope item removed.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->can('projects.manage_work'), 403);
    }
}
