<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\TaskStatus;
use App\Enums\WorkStatus;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ServiceLine;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->ownedBy($request->input('owner'))
            ->when($request->boolean('overdue'), fn ($qq) => $qq->whereDate('target_deadline', '<', now())->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Cancelled->value]))
            ->with(['owners', 'serviceLine', 'tender', 'serviceRequest', 'phases'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('projects.index', [
            'projects' => $projects,
            'statuses' => ProjectStatus::options(),
            'owners' => User::orderBy('name')->get(),
            'filters' => $request->only(['q', 'status', 'owner', 'overdue']),
        ]);
    }

    public function show(Project $project)
    {
        $project->load([
            'owners', 'serviceLine', 'tender', 'serviceRequest',
            'phases.assignees', 'phases.gateSignedBy',
            'phases.scopeItems.tasks',
            'phases.milestones.featureSets.tasks.assignees',
            'phases.milestones.featureSets.tasks.subtasks',
            'phases.milestones.featureSets.tasks.scopeItems',
            'scopeItems.tasks',
            'comments.user', 'comments.mentions', 'attachments.user', 'auditLogs.user',
        ]);

        return view('projects.show', [
            'project' => $project,
            'currentPhase' => $project->currentPhase(),
            'workStatuses' => WorkStatus::options(),
            'taskStatuses' => TaskStatus::options(),
            'members' => User::orderBy('name')->get(),
            'coverage' => $project->scopeCoverage(),
        ]);
    }

    public function create()
    {
        return $this->form(new Project(['type' => ProjectType::Engagement, 'status' => ProjectStatus::NotStarted, 'priority' => Priority::Medium]));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $project = Project::create($data);
        $project->syncOwners($request->input('owner_ids') ?: [$request->user()->id]);

        if ($project->type === ProjectType::Sdlc) {
            Phase::seedSdlc($project);
        }

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function edit(Project $project)
    {
        return $this->form($project);
    }

    public function update(Request $request, Project $project)
    {
        $data = $this->validateData($request);
        $before = $project->getRawOriginal();

        if ($this->baselineChanged($project, $data) && $request->user()->cannot('projects.edit_baseline')) {
            return back()->withInput()->with('error', 'You are not allowed to change the budget or deadline.');
        }

        $project->updateWithLock($data, (int) $request->integer('lock_version'));
        if ($request->has('owner_ids')) {
            $project->syncOwners($request->input('owner_ids', []));
        }
        $project->refresh()->auditBaselineChanges($before);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        abort_unless($project->isOwnedBy(request()->user()) || request()->user()->isAdmin(), 403);
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    private function form(Project $project)
    {
        return view('projects.form', [
            'project' => $project,
            'serviceLines' => ServiceLine::ordered()->get(),
            'owners' => User::orderBy('name')->get(),
        ]);
    }

    private function baselineChanged(Project $project, array $data): bool
    {
        foreach ($project->baselineFields() as $field) {
            if (array_key_exists($field, $data)
                && (string) $data[$field] !== (string) $project->getRawOriginal($field)) {
                return true;
            }
        }

        return false;
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', ProjectType::values())],
            'status' => ['required', 'in:'.implode(',', ProjectStatus::values())],
            'priority' => ['required', 'in:'.implode(',', Priority::values())],
            'service_line_id' => ['nullable', 'exists:service_lines,id'],
            'owner_ids' => ['array'],
            'owner_ids.*' => ['integer', 'exists:users,id'],
            'client' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope_statement' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'start_date' => ['nullable', 'date'],
            'target_deadline' => ['nullable', 'date'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);
    }
}
