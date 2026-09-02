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
        Client: {{ $project->client ?? '—' }} · Owner: {{ $project->owner?->name ?? 'unassigned' }}<br>
        @include('partials._due', ['model' => $project, 'label' => 'Deadline'])
        @if($project->budget) · Budget: {{ number_format($project->budget, 2) }} {{ $project->currency }}@endif<br>
        @if($project->tender)Origin: <a href="{{ route('tenders.show', $project->tender) }}">tender</a>@endif
        @if($project->serviceRequest)Origin: <a href="{{ route('service-requests.show', $project->serviceRequest) }}">service request</a>@endif
    </p>
    @if($project->scope_statement)<p><strong>Scope:</strong> {{ $project->scope_statement }}</p>@endif

    @if ($project->usesPhases() && $project->current_phase)
        <div class="steps">
            @foreach ($phases as $ph)
                <span class="step {{ $project->current_phase === $ph ? 'current' : ($project->current_phase->order() > $ph->order() ? 'done' : '') }}">{{ $ph->label() }}</span>
            @endforeach
        </div>
        @can('projects.edit')
            @if ($project->current_phase->next())
                <form method="POST" action="{{ route('projects.advance-phase', $project) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="small">Advance to {{ $project->current_phase->next()->label() }}</button>
                </form>
            @endif
        @endcan
    @endif
</div>

<h2>Work breakdown</h2>

@forelse ($project->milestones as $milestone)
    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:1rem;">
            <strong>{{ $milestone->name }}</strong>
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
                                    <span class="muted">{{ $task->assignee?->name ?? 'unassigned' }} · {{ $task->progress }}%</span>
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
                                        <div><select name="assignee_id"><option value="">Unassigned</option>@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                                        <div><select name="status">@foreach($taskStatuses as $s)<option value="{{ $s['value'] }}">{{ $s['label'] }}</option>@endforeach</select></div>
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
