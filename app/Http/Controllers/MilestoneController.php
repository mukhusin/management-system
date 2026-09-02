<?php

namespace App\Http\Controllers;

use App\Enums\WorkStatus;
use App\Models\Milestone;
use App\Models\Phase;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function store(Request $request, Phase $phase)
    {
        $this->authorize('projects.manage_work');

        if (! $phase->project->workAllowedInPhase($phase)) {
            return back()->with('error', 'This phase comes after the current one — sign off earlier phases first (gates are enforced).');
        }

        $data = $this->rules($request);
        $data['project_id'] = $phase->project_id;
        $data['position'] = (int) $phase->milestones()->max('position') + 1;
        $phase->milestones()->create($data);

        return back()->with('status', 'Milestone added.');
    }

    public function update(Request $request, Milestone $milestone)
    {
        $this->authorize('projects.manage_work');
        $milestone->updateWithLock($this->rules($request), (int) $request->integer('lock_version'));

        return back()->with('status', 'Milestone updated.');
    }

    public function destroy(Milestone $milestone)
    {
        $this->authorize('projects.manage_work');
        $milestone->delete();

        return back()->with('status', 'Milestone removed.');
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', WorkStatus::values())],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}
