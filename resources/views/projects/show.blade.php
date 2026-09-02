@extends('layouts.app')
@section('title', $project->name)

@php($canManage = auth()->user()->can('projects.manage_work'))

@section('content')
<p><a href="{{ route('projects.index') }}">&larr; Projects</a></p>

<div class="card">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 style="margin-bottom:0.3rem;">{{ $project->name }}</h1>
            <div>
                @include('partials._badge', ['enum' => $project->type])
                @include('partials._badge', ['enum' => $project->status])
                @include('partials._badge', ['enum' => $project->priority])
                @if($project->serviceLine)<span class="badge badge--gray">{{ $project->serviceLine->name }}</span>@endif
            </div>
        </div>
        <div style="text-align:right;">
            @include('partials._progress', ['value' => $project->progress, 'width' => '140px'])
            <div>@can('projects.edit')<a href="{{ route('projects.edit', $project) }}" class="btn ghost small">Edit</a>@endcan</div>
        </div>
    </div>
    <p class="meta">
        Client: {{ $project->client ?? '—' }} · Owners: {{ $project->ownerNames() }}<br>
        @include('partials._due', ['model' => $project, 'label' => 'Deadline'])
        @if($project->budget) · Budget: {{ number_format($project->budget, 2) }} {{ $project->currency }}@endif<br>
        @if($project->tender)Origin: <a href="{{ route('tenders.show', $project->tender) }}">tender</a>@endif
        @if($project->serviceRequest)Origin: <a href="{{ route('service-requests.show', $project->serviceRequest) }}">service request</a>@endif
    </p>
    @if($project->scope_statement && $project->scopeItems->isEmpty())<p class="scope-text"><strong>Scope</strong>
{{ $project->scope_statement }}</p>@endif

    @if ($project->usesPhases() && $project->current_phase)
        <div class="steps">
            @foreach ($phases as $ph)
                <span class="step {{ $project->current_phase === $ph ? 'current' : ($project->current_phase->order() > $ph->order() ? 'done' : '') }}">{{ $ph->label() }}</span>
            @endforeach
        </div>
        @php($incomplete = $project->incompleteMilestonesForPhase($project->current_phase))
        @if ($incomplete->isNotEmpty())
            <p class="scope-uncovered">{{ $incomplete->count() }} {{ $project->current_phase->label() }} milestone(s) still open{{ \App\Models\Project::phaseGatesEnforced() ? ' — gate enforced' : '' }}.</p>
        @endif
        @can('projects.edit')
            @if ($project->current_phase->next())
                <form method="POST" action="{{ route('projects.advance-phase', $project) }}" style="margin-top:0.5rem;">
                    @csrf @method('PATCH')
                    <input type="text" name="note" placeholder="Sign-off note (optional)" style="min-width:260px;">
                    @if ($incomplete->isNotEmpty() && auth()->user()->isAdmin() && ! \App\Models\Project::phaseGatesEnforced())
                        <label class="muted"><input type="checkbox" name="force" value="1"> override open milestones</label>
                    @endif
                    <button type="submit" class="small">Sign off {{ $project->current_phase->label() }} &rarr; {{ $project->current_phase->next()->label() }}</button>
                </form>
            @endif
        @endcan
        @if ($project->phaseSignoffs->isNotEmpty())
            <ul class="thread" style="margin-top:0.5rem;">
                @foreach ($project->phaseSignoffs as $s)
                    <li><span class="by">{{ $s->phase->label() }} signed off</span>
                        <span class="at">{{ $s->signer?->name ?? 'system' }} · {{ $s->signed_at->format('d M Y H:i') }}{{ $s->forced ? ' · forced' : '' }}</span>
                        @if($s->note)<div>{{ $s->note }}</div>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</div>

@if ($project->scopeItems->isNotEmpty() || $canManage)
<div class="card">
    <div class="cover">
        <h2 style="margin:0;">Requirements &amp; traceability</h2>
        <span class="progress" style="width:120px;"><span style="width:{{ $coverage['percent'] }}%;"></span></span>
        <span class="muted">{{ $coverage['covered'] }}/{{ $coverage['total'] }} scope items covered by a task</span>
    </div>
    <table class="grid" style="margin-top:0.5rem;">
        <thead><tr><th>#</th><th>Requirement</th><th>Covering tasks</th><th></th></tr></thead>
        <tbody>
        @forelse ($project->scopeItems as $item)
            <tr>
                <td>{{ $item->code }}</td>
                <td class="{{ $item->isCovered() ? '' : 'scope-uncovered' }}">
                    {{ $item->description }}
                    @unless($item->isCovered())<span class="chip">uncovered</span>@endunless
                    <span class="chip">{{ $item->source }}</span>
                </td>
                <td>
                    @forelse ($item->tasks as $t)<span class="chip">{{ $t->title }}</span> @empty <span class="muted">—</span> @endforelse
                </td>
                <td>
                    @if($canManage)
                    <details><summary class="muted">edit</summary>
                        <form method="POST" action="{{ route('scope-items.update', $item) }}" style="margin-top:0.4rem;">
                            @csrf @method('PUT')
                            <textarea name="description" rows="2" style="width:100%;">{{ $item->description }}</textarea>
                            <div class="muted" style="margin:0.3rem 0;">Link tasks that satisfy this requirement:</div>
                            @foreach ($project->allTasks() as $t)
                                <label style="display:block; font-size:0.85rem;">
                                    <input type="checkbox" name="task_ids[]" value="{{ $t->id }}" @checked($item->tasks->contains($t->id))>
                                    {{ $t->title }}
                                </label>
                            @endforeach
                            <button class="small" style="margin-top:0.4rem;">Save</button>
                        </form>
                        <form method="POST" action="{{ route('scope-items.destroy', $item) }}" style="display:inline;">@csrf @method('DELETE')<button class="ghost small" style="color:var(--c-red);">delete</button></form>
                    </details>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">No scope items. Add the tender's requirements here so tasks can be traced back to them.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($canManage)
        <form method="POST" action="{{ route('scope-items.store', $project) }}" style="margin-top:0.5rem;">
            @csrf
            <input type="text" name="description" placeholder="New requirement / scope item" style="min-width:320px;" required>
            <button class="small">Add scope item</button>
        </form>
    @endif
</div>
@endif

<h2>Work breakdown</h2>

@forelse ($project->milestones as $milestone)
    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:1rem;">
            <strong>{{ $milestone->name }} @if($milestone->phase)<span class="chip">{{ $milestone->phase->label() }}</span>@endif</strong>
            <span>@include('partials._badge', ['enum' => $milestone->status]) @include('partials._progress', ['value' => $milestone->progress])
                @if($canManage)
                <form method="POST" action="{{ route('milestones.destroy', $milestone) }}" style="display:inline;">@csrf @method('DELETE')<button class="ghost small" style="color:var(--c-red); border:none; background:none;">×</button></form>
                @endif
            </span>
        </div>
        @if($milestone->description)<p class="muted">{{ $milestone->description }}</p>@endif

        <div class="tree">
            @foreach ($milestone->featureSets as $fs)
                <ul>
                    <li>
                        <strong>{{ $fs->name }}</strong> @include('partials._badge', ['enum' => $fs->status]) @include('partials._progress', ['value' => $fs->progress])
                        @if($canManage)
                        <form method="POST" action="{{ route('feature-sets.destroy', $fs) }}" style="display:inline;">@csrf @method('DELETE')<button class="ghost small" style="color:var(--c-red); border:none; background:none;">×</button></form>
                        @endif
                        <ul>
                            @foreach ($fs->tasks as $task)
                                <li>
                                    <form method="POST" action="{{ route('tasks.toggle', $task) }}" style="display:inline;">@csrf @method('PATCH')
                                        <input type="checkbox" onchange="this.form.submit()" @checked($task->status === \App\Enums\TaskStatus::Done)>
                                    </form>
                                    {{ $task->title }}
                                    @include('partials._badge', ['enum' => $task->status])
                                    <span class="muted">{{ $task->assigneeNames() }} · {{ $task->progress }}%</span>
                                    @foreach ($task->scopeItems as $si)<span class="chip" title="{{ $si->description }}">{{ $si->code }}</span>@endforeach
                                    @if($task->subtasks->count())
                                        <ul>
                                            @foreach ($task->subtasks as $st)
                                                <li>
                                                    <form method="POST" action="{{ route('subtasks.toggle', $st) }}" style="display:inline;">@csrf @method('PATCH')
                                                        <input type="checkbox" onchange="this.form.submit()" @checked($st->status === \App\Enums\TaskStatus::Done)>
                                                    </form>
                                                    {{ $st->title }} <span class="muted">{{ $st->progress }}%</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if($canManage)
                                        <details><summary class="muted">+ sub-task</summary>
                                            <form method="POST" action="{{ route('tasks.subtasks.store', $task) }}">
                                                @csrf
                                                <input type="text" name="title" placeholder="Sub-task title" required>
                                                <input type="hidden" name="status" value="todo">
                                                <button class="small">Add</button>
                                            </form>
                                        </details>
                                    @endif
                                </li>
                            @endforeach
                            @if($canManage)
                                <li><details><summary class="muted">+ task</summary>
                                    <form method="POST" action="{{ route('feature-sets.tasks.store', $fs) }}" class="form-grid" style="margin-top:0.5rem;">
                                        @csrf
                                        <div class="full"><input type="text" name="title" placeholder="Task title" required></div>
                                        <div><label>Assignees</label><select name="assignee_ids[]" multiple size="3">@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                                        <div><label>Status</label><select name="status">@foreach($taskStatuses as $s)<option value="{{ $s['value'] }}">{{ $s['label'] }}</option>@endforeach</select></div>
                                        <div><select name="priority">@foreach(\App\Enums\Priority::options() as $p)<option value="{{ $p['value'] }}" @selected($p['value']==='medium')>{{ $p['label'] }}</option>@endforeach</select></div>
                                        <div><input type="date" name="due_date"></div>
                                        <div><button class="small">Add task</button></div>
                                    </form>
                                </details></li>
                            @endif
                        </ul>
                    </li>
                </ul>
            @endforeach

            @if($canManage)
                <details><summary class="muted">+ feature set</summary>
                    <form method="POST" action="{{ route('milestones.feature-sets.store', $milestone) }}">
                        @csrf
                        <input type="text" name="name" placeholder="Feature set name" required>
                        <input type="hidden" name="status" value="not_started">
                        <button class="small">Add</button>
                    </form>
                </details>
            @endif
        </div>
    </div>
@empty
    <div class="card muted">No milestones yet.</div>
@endforelse

@if($canManage)
<div class="card">
    <details><summary><strong>+ Add milestone</strong></summary>
        <form method="POST" action="{{ route('projects.milestones.store', $project) }}" class="form-grid" style="margin-top:0.75rem;">
            @csrf
            <div class="full"><label>Name *</label><input type="text" name="name" required></div>
            <div><label>Status</label><select name="status">@foreach($workStatuses as $s)<option value="{{ $s['value'] }}">{{ $s['label'] }}</option>@endforeach</select></div>
            @if($project->usesPhases())
            <div><label>Phase gate</label><select name="phase"><option value="">—</option>@foreach($phases as $ph)<option value="{{ $ph->value }}">{{ $ph->label() }}</option>@endforeach</select></div>
            @endif
            <div><label>Due date</label><input type="date" name="due_date"></div>
            <div class="full"><label>Description</label><textarea name="description"></textarea></div>
            <div><button class="small">Add milestone</button></div>
        </form>
    </details>
</div>
@endif

@include('partials._comments', ['subject' => $project, 'subjectType' => 'projects'])
@include('partials._attachments', ['subject' => $project, 'subjectType' => 'projects'])
@include('partials._audit', ['subject' => $project])
@endsection
