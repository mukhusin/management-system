@extends('layouts.app')
@section('title', $project->name)

@php($canManage = auth()->user()->can('projects.manage_work'))

@section('content')
<p class="back-link"><a href="{{ route('projects.index') }}">&larr; Projects</a></p>

<div class="card">
    <div class="page-head" style="margin-bottom:.5rem;">
        <div>
            <h1 style="margin-bottom:.3rem;">{{ $project->name }}</h1>
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
    @if($project->scope_statement && $project->allScopeItems->isEmpty())<p class="scope-text"><strong>Scope</strong>
{{ $project->scope_statement }}</p>@endif

    @if ($project->phases->isNotEmpty())
        <div class="steps">
            @foreach ($project->phases as $ph)
                <span class="step {{ $ph->isSignedOff() ? 'done' : ($currentPhase && $currentPhase->id === $ph->id ? 'current' : '') }}">{{ $ph->name }}</span>
            @endforeach
        </div>
        <span class="cover"><span class="progress" style="width:120px;"><span style="width:{{ $coverage['percent'] }}%;"></span></span>
        <span class="muted">{{ $coverage['covered'] }}/{{ $coverage['total'] }} requirements covered project-wide</span></span>
    @endif
</div>

<h2>Phases</h2>

@forelse ($project->phases as $phase)
    @include('partials._phase', [
        'phase' => $phase,
        'project' => $project,
        'canManage' => $canManage,
        'members' => $members,
        'taskStatuses' => $taskStatuses,
        'workStatuses' => $workStatuses,
        'isCurrent' => $currentPhase && $currentPhase->id === $phase->id,
    ])
@empty
    <div class="card muted">No phases yet.
        @if($project->type === \App\Enums\ProjectType::Sdlc) Expected the SDLC template — add phases below.@endif
    </div>
@endforelse

@if($canManage)
<div class="card">
    <details><summary><strong>+ Add phase</strong></summary>
        <form method="POST" action="{{ route('projects.phases.store', $project) }}" class="form-grid" style="margin-top:.75rem;">
            @csrf
            <div class="full"><label>Name *</label><input type="text" name="name" placeholder="e.g. Discovery, Rollout, Handover" required></div>
            <div><label>Status</label><select name="status">@foreach($workStatuses as $s)<option value="{{ $s['value'] }}">{{ $s['label'] }}</option>@endforeach</select></div>
            <div><label>Starts</label><input type="date" name="starts_on"></div>
            <div><label>Ends</label><input type="date" name="ends_on"></div>
            <div class="full"><label>Description</label><textarea name="description"></textarea></div>
            <div style="align-self:end;"><button class="small">Add phase</button></div>
        </form>
    </details>
</div>
@endif

@if ($project->scopeItems->isNotEmpty() || $canManage)
<div class="card">
    @include('partials._scope_table', [
        'items' => $project->scopeItems,
        'tasks' => collect($project->allTasks()),
        'canManage' => $canManage,
        'addAction' => route('scope-items.store', $project),
        'addPhaseId' => null,
        'title' => 'Project-level requirements (no phase)',
    ])
</div>
@endif

@include('partials._comments', ['subject' => $project, 'subjectType' => 'projects'])
@include('partials._attachments', ['subject' => $project, 'subjectType' => 'projects'])
@include('partials._audit', ['subject' => $project])
@endsection
