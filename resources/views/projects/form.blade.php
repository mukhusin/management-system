@extends('layouts.app')
@section('title', $project->exists ? 'Edit project' : 'New project')

@section('content')
<p><a href="{{ route('projects.index') }}">&larr; Projects</a></p>
<h1>{{ $project->exists ? 'Edit project' : 'New project' }}</h1>

<form method="POST" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}" class="card">
    @csrf
    @if ($project->exists) @method('PUT') <input type="hidden" name="lock_version" value="{{ $project->lock_version }}"> @endif
    <div class="form-grid">
        <div class="full"><label>Name *</label><input type="text" name="name" value="{{ old('name', $project->name) }}" required></div>
        <div><label>Type *</label><select name="type">
            @foreach (\App\Enums\ProjectType::options() as $t)<option value="{{ $t['value'] }}" @selected(old('type', $project->type?->value)===$t['value'])>{{ $t['label'] }}</option>@endforeach
        </select></div>
        <div><label>Status *</label><select name="status">
            @foreach (\App\Enums\ProjectStatus::options() as $s)<option value="{{ $s['value'] }}" @selected(old('status', $project->status?->value)===$s['value'])>{{ $s['label'] }}</option>@endforeach
        </select></div>
        <div><label>Priority *</label><select name="priority">
            @foreach (\App\Enums\Priority::options() as $p)<option value="{{ $p['value'] }}" @selected(old('priority', $project->priority?->value)===$p['value'])>{{ $p['label'] }}</option>@endforeach
        </select></div>
        <div><label>Service line</label><select name="service_line_id"><option value="">—</option>
            @foreach ($serviceLines as $l)<option value="{{ $l->id }}" @selected((string)old('service_line_id',$project->service_line_id)===(string)$l->id)>{{ $l->name }}</option>@endforeach
        </select></div>
        <div><label>Owner</label><select name="owner_id"><option value="">—</option>
            @foreach ($owners as $o)<option value="{{ $o->id }}" @selected((string)old('owner_id',$project->owner_id)===(string)$o->id)>{{ $o->name }}</option>@endforeach
        </select></div>
        <div><label>Client</label><input type="text" name="client" value="{{ old('client', $project->client) }}"></div>
        <div><label>Budget</label><input type="number" step="0.01" name="budget" value="{{ old('budget', $project->budget) }}"></div>
        <div><label>Currency</label><input type="text" name="currency" value="{{ old('currency', $project->currency) }}"></div>
        <div><label>Start date</label><input type="date" name="start_date" value="{{ old('start_date', optional($project->start_date)->toDateString()) }}"></div>
        <div><label>Target deadline</label><input type="date" name="target_deadline" value="{{ old('target_deadline', optional($project->target_deadline)->toDateString()) }}"></div>
        <div class="full"><label>Next action</label><input type="text" name="next_action" value="{{ old('next_action', $project->next_action) }}"></div>
        <div class="full"><label>Description</label><textarea name="description">{{ old('description', $project->description) }}</textarea></div>
        <div class="full"><label>Scope statement</label><textarea name="scope_statement">{{ old('scope_statement', $project->scope_statement) }}</textarea></div>
        <div class="full"><label>Remarks</label><textarea name="remarks">{{ old('remarks', $project->remarks) }}</textarea></div>
    </div>
    <button type="submit" style="margin-top:1rem;">{{ $project->exists ? 'Save' : 'Create project' }}</button>
</form>
@endsection
