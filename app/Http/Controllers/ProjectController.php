<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\ProjectPhase;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\WorkStatus;
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
            ->when($request->input('owner'), fn ($qq, $o) => $qq->where('owner_id', $o))
            ->when($request->boolean('overdue'), fn ($qq) => $qq->whereDate('target_deadline', '<', now())->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Cancelled->value]))
            ->with(['owner', 'serviceLine', 'tender', 'serviceRequest'])
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
            'owner', 'serviceLine', 'tender', 'serviceRequest',
            'milestones.featureSets.tasks.assignee',
            'milestones.featureSets.tasks.subtasks',
            'comments.user', 'comments.mentions', 'attachments.user', 'auditLogs.user',
        ]);

        return view('projects.show', [
            'project' => $project,
            'phases' => ProjectPhase::cases(),
            'workStatuses' => WorkStatus::options(),
            'taskStatuses' => \App\Enums\TaskStatus::options(),
            'members' => User::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return $this->form(new Project(['type' => ProjectType::Engagement, 'status' => ProjectStatus::NotStarted, 'priority' => Priority::Medium]));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['current_phase'] = ($data['type'] ?? null) === ProjectType::Sdlc->value ? ProjectPhase::Requirements->value : null;

        $project = Project::create($data);

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
        $project->refresh()->auditBaselineChanges($before);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        abort_unless($project->owner_id === request()->user()->id || request()->user()->isAdmin(), 403);
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    public function advancePhase(Request $request, Project $project)
    {
        if (! $project->usesPhases() || ! $project->current_phase) {
            return back()->with('error', 'This project does not use SDLC phases.');
        }

        $next = $project->current_phase->next();
        if (! $next) {
            return back()->with('error', 'The project is already in the final phase.');
        }

        $blocking = $project->milestones()
            ->where('phase', $project->current_phase->value)
            ->where('status', '!=', WorkStatus::Done->value)
            ->exists();

        if ($blocking && ! $request->user()->isAdmin()) {
            return back()->with('error', 'Complete all '.$project->current_phase->label().' milestones first (or ask an administrator to override).');
        }

        $from = $project->current_phase;
        $project->forceFill(['current_phase' => $next->value])->save();
        $project->audit('phase_advanced', ['phase' => $from->value], ['phase' => $next->value]);

        return back()->with('status', 'Advanced to '.$next->label().'.');
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
            'owner_id' => ['nullable', 'exists:users,id'],
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
