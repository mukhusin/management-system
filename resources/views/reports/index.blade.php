@extends('layouts.app')
@section('title', 'Reports')

@section('content')
<h1>Reports</h1>

<div class="row">
    <div class="card" style="flex:1; min-width:280px;">
        <h2 style="margin-top:0;">Tender funnel</h2>
        @include('partials._bars', ['rows' => $tenderFunnel])
        <p class="muted">Win rate: {{ $tenderWinRate === null ? 'n/a' : $tenderWinRate.'%' }}</p>
    </div>
    <div class="card" style="flex:1; min-width:280px;">
        <h2 style="margin-top:0;">Service request funnel</h2>
        @include('partials._bars', ['rows' => $requestFunnel])
        <p class="muted">Win rate: {{ $requestWinRate === null ? 'n/a' : $requestWinRate.'%' }}</p>
    </div>
</div>

<div class="card">
    <h2 style="margin-top:0;">Projects by status</h2>
    <table class="grid">
        <thead><tr><th>Status</th><th>Count</th><th>Avg progress</th></tr></thead>
        <tbody>
        @foreach ($projectStatuses as $s)
            <tr>
                <td>{{ $s->label() }}</td>
                <td>{{ $projectsByStatus[$s->value]->n ?? 0 }}</td>
                <td>{{ $projectsByStatus[$s->value]->avg_progress ?? 0 }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="card">
    <h2 style="margin-top:0;">Workload — open tasks per person</h2>
    <table class="grid">
        <thead><tr><th>Person</th><th>Open tasks</th></tr></thead>
        <tbody>@foreach ($workload as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->open_tasks }}</td></tr>@endforeach</tbody>
    </table>
</div>

<div class="card">
    <h2 style="margin-top:0;">Pipeline &amp; delivery by service line</h2>
    <table class="grid">
        <thead><tr><th>Service line</th><th>Tenders</th><th>Requests</th><th>Projects</th><th>Project budget</th></tr></thead>
        <tbody>
        @foreach ($serviceLines as $l)
            <tr><td>{{ $l->name }}</td><td>{{ $l->tenders_count }}</td><td>{{ $l->service_requests_count }}</td>
                <td>{{ $l->projects_count }}</td><td>{{ $l->pipeline_budget ? number_format($l->pipeline_budget) : '—' }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="card">
    <h2 style="margin-top:0;">Overdue tasks</h2>
    <table class="grid">
        <thead><tr><th>Task</th><th>Project</th><th>Assignee</th><th>Due</th></tr></thead>
        <tbody>
        @forelse ($overdue as $t)
            <tr><td>{{ $t->title }}</td><td>{{ $t->featureSet?->milestone?->project?->name }}</td>
                <td>{{ $t->assignee?->name ?? '—' }}</td><td class="due-soon">{{ optional($t->due_date)->format('d M Y') }}</td></tr>
        @empty <tr><td colspan="4" class="muted">Nothing overdue.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
