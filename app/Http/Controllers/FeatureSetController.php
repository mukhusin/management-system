<?php

namespace App\Http\Controllers;

use App\Enums\WorkStatus;
use App\Models\FeatureSet;
use App\Models\Milestone;
use Illuminate\Http\Request;

class FeatureSetController extends Controller
{
    public function store(Request $request, Milestone $milestone)
    {
        if (! $milestone->project->workAllowedInPhase($milestone->phase)) {
            return back()->with('error', 'This milestone belongs to a later phase — advance the project first (phase gates are enforced).');
        }

        $data = $this->rules($request);
        $data['position'] = (int) $milestone->featureSets()->max('position') + 1;
        $milestone->featureSets()->create($data);

        return back()->with('status', 'Feature set added.');
    }

    public function update(Request $request, FeatureSet $featureSet)
    {
        $featureSet->updateWithLock($this->rules($request), (int) $request->integer('lock_version'));

        return back()->with('status', 'Feature set updated.');
    }

    public function destroy(FeatureSet $featureSet)
    {
        $featureSet->delete();

        return back()->with('status', 'Feature set removed.');
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', WorkStatus::values())],
        ]);
    }
}
