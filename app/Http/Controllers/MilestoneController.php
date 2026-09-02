<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPhase;
use App\Enums\WorkStatus;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $data = $this->rules($request);
        $data['position'] = (int) $project->milestones()->max('position') + 1;
        $project->milestones()->create($data);

        return back()->with('status', 'Milestone added.');
    }

    public function update(Request $request, Milestone $milestone)
    {
        $milestone->updateWithLock($this->rules($request), (int) $request->integer('lock_version'));

        return back()->with('status', 'Milestone updated.');
    }

    public function destroy(Milestone $milestone)
    {
        $milestone->delete();

        return back()->with('status', 'Milestone removed.');
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phase' => ['nullable', 'in:'.implode(',', ProjectPhase::values())],
            'status' => ['required', 'in:'.implode(',', WorkStatus::values())],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}
