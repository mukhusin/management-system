<?php

namespace App\Http\Controllers;

use App\Enums\WorkStatus;
use App\Models\Phase;
use App\Models\Project;
use Illuminate\Http\Request;

class PhaseController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $this->authorize('projects.manage_work');

        $data = $this->rules($request);
        $data['position'] = $project->nextPhasePosition();
        $project->phases()->create($data);

        return back()->with('status', 'Phase added.');
    }

    public function update(Request $request, Phase $phase)
    {
        $this->authorize('projects.manage_work');

        $phase->updateWithLock($this->rules($request), (int) $request->integer('lock_version'));
        if ($request->has('assignee_ids')) {
            $phase->assignees()->sync($request->input('assignee_ids', []));
        }

        return back()->with('status', 'Phase updated.');
    }

    public function destroy(Phase $phase)
    {
        $this->authorize('projects.manage_work');
        $phase->delete();

        return back()->with('status', 'Phase removed.');
    }

    /**
     * Stage gate: mark the phase signed off. Blocked while its milestones are
     * open unless gates are unenforced and the actor is a system admin who
     * ticks "force".
     */
    public function signOff(Request $request, Phase $phase)
    {
        $this->authorize('projects.edit');

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'force' => ['sometimes', 'boolean'],
        ]);

        $incomplete = $phase->incompleteMilestones();
        $force = $request->boolean('force');

        if ($incomplete->isNotEmpty()) {
            $mayForce = ! Project::phaseGatesEnforced() && $force && $request->user()->isAdmin();
            if (! $mayForce) {
                return back()->with('error',
                    $incomplete->count().' milestone(s) in this phase are not Done.'
                    .(Project::phaseGatesEnforced() ? '' : ' A system administrator can override with the force option.'));
            }
        }

        $phase->forceFill([
            'status' => WorkStatus::Done->value,
            'gate_note' => $data['note'] ?? null,
            'gate_signed_by' => $request->user()->id,
            'gate_signed_at' => now(),
            'gate_forced' => $incomplete->isNotEmpty(),
        ])->save();

        // Open the next phase.
        $phase->project->phases()
            ->where('position', '>', $phase->position)
            ->where('status', WorkStatus::NotStarted->value)
            ->orderBy('position')->first()
            ?->update(['status' => WorkStatus::InProgress->value]);

        $phase->project->audit('phase_signed_off', ['phase' => $phase->name], ['forced' => $incomplete->isNotEmpty()]);

        return back()->with('status', "Signed off {$phase->name}.");
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', WorkStatus::values())],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
        ]);
    }
}
