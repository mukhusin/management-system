@extends('layouts.app')
@section('title', 'Projects')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Projects</h1>
    @can('projects.initiate')<a href="{{ route('projects.create') }}" class="btn">+ New project</a>@endcan
</div>

<div class="card">
    <form method="GET" class="filters">
        <div><label>Search</label><input type="text" name="q" value="{{ $filters['q'] ?? '' }}"></div>
        <div><label>Status</label><select name="status"><option value="">Any</option>
            @foreach ($statuses as $s)<option value="{{ $s['value'] }}" @selected(($filters['status'] ?? '')===$s['value'])>{{ $s['label'] }}</option>@endforeach
        </select></div>
        <div><label>Owner</label><select name="owner"><option value="">Anyone</option>
            @foreach ($owners as $o)<option value="{{ $o->id }}" @selected((string)($filters['owner'] ?? '')===(string)$o->id)>{{ $o->name }}</option>@endforeach
        </select></div>
        <div><label><input type="checkbox" name="overdue" value="1" @checked(!empty($filters['overdue']))> Overdue only</label></div>
        <div><button type="submit">Filter</button></div>
    </form>
</div>

<div class="card">
    <table class="grid">
        <thead><tr><th>Project</th><th>Type</th><th>Status</th><th>Phase</th><th>Progress</th><th>Owner</th><th>Deadline</th><th>Origin</th></tr></thead>
        <tbody>
        @forelse ($projects as $p)
            <tr>
                <td><a href="{{ route('projects.show', $p) }}">{{ $p->name }}</a><div class="muted">{{ $p->client }}</div></td>
                <td>@include('partials._badge', ['enum' => $p->type])</td>
                <td>@include('partials._badge', ['enum' => $p->status])</td>
                <td>{{ $p->current_phase?->label() ?? '—' }}</td>
                <td>@include('partials._progress', ['value' => $p->progress])</td>
                <td>{{ $p->owners->pluck("name")->join(", ") ?: "—" }}</td>
                <td>@include('partials._due', ['model' => $p, 'label' => 'Deadline'])</td>
                <td>
                    @if($p->tender)<a href="{{ route('tenders.show', $p->tender) }}">Tender</a>
                    @elseif($p->serviceRequest)<a href="{{ route('service-requests.show', $p->serviceRequest) }}">Request</a>
                    @else <span class="muted">Standalone</span>@endif
                </td>
            </tr>
        @empty <tr><td colspan="8" class="muted">No projects yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $projects->links() }}</div>
</div>
@endsection
