@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<h1>Dashboard</h1>

<div class="kpi-grid">
    @foreach ($kpis as $label => $n)
        <div class="kpi"><div class="n">{{ $n }}</div><div class="l">{{ $label }}</div></div>
    @endforeach
</div>

<div class="row" style="margin-top:1.5rem;">
    <div class="card" style="flex:1; min-width:280px;">
        <h2 style="margin-top:0;">Tender pipeline</h2>
        @include('partials._bars', ['rows' => collect(\App\Enums\TenderState::cases())->map(fn ($s) => ['label' => $s->label(), 'n' => (int) ($tenderFunnel[$s->value] ?? 0)])])
    </div>
    <div class="card" style="flex:1; min-width:280px;">
        <h2 style="margin-top:0;">Service request pipeline</h2>
        @include('partials._bars', ['rows' => collect(\App\Enums\ServiceRequestState::cases())->map(fn ($s) => ['label' => $s->label(), 'n' => (int) ($requestFunnel[$s->value] ?? 0)])])
    </div>
</div>

<div class="row">
    <div class="card" style="flex:1; min-width:280px;">
        <h2 style="margin-top:0;">My open tasks</h2>
        <ul style="list-style:none; padding:0; margin:0;">
            @forelse ($myTasks as $t)
                <li style="border-top:1px solid var(--border); padding:0.5rem 0;">
                    {{ $t->title }}
                    <span class="muted">— {{ $t->featureSet?->milestone?->project?->name }}</span>
                    @include('partials._badge', ['enum' => $t->status])
                </li>
            @empty <li class="muted">Nothing assigned to you.</li>
            @endforelse
        </ul>
    </div>
    <div class="card" style="flex:1; min-width:280px;">
        <h2 style="margin-top:0;">My projects &amp; tenders</h2>
        <ul style="list-style:none; padding:0; margin:0;">
            @foreach ($myProjects as $p)
                <li style="border-top:1px solid var(--border); padding:0.5rem 0;"><a href="{{ route('projects.show', $p) }}">{{ $p->name }}</a> @include('partials._badge', ['enum' => $p->status])</li>
            @endforeach
            @foreach ($myTenders as $t)
                <li style="border-top:1px solid var(--border); padding:0.5rem 0;"><a href="{{ route('tenders.show', $t) }}">{{ $t->title }}</a> @include('partials._badge', ['enum' => $t->state])</li>
            @endforeach
            @if ($myProjects->isEmpty() && $myTenders->isEmpty())<li class="muted">Nothing owned by you.</li>@endif
        </ul>
    </div>
</div>

<div class="row">
    <div class="card" style="flex:1; min-width:280px;">
        <h2 style="margin-top:0;">Due in the next 14 days</h2>
        <ul style="list-style:none; padding:0; margin:0;">
            @forelse ($upcoming as $item)
                <li style="border-top:1px solid var(--border); padding:0.5rem 0;">
                    <span class="badge badge--gray">{{ $item['type'] }}</span>
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    <span class="muted">— {{ \Illuminate\Support\Carbon::parse($item['date'])->format('d M') }}</span>
                </li>
            @empty <li class="muted">Nothing due soon.</li>
            @endforelse
        </ul>
    </div>
    <div class="card" style="flex:1; min-width:280px;">
        <h2 style="margin-top:0;">Recent activity</h2>
        <ul class="thread">
            @forelse ($recentAudit as $log)
                <li><div class="by">{{ $log->summary() }}
                    <span class="at">{{ $log->user?->name ?? 'system' }} · {{ $log->created_at?->diffForHumans() }}</span></div></li>
            @empty <li class="muted">No activity yet.</li>
            @endforelse
        </ul>
    </div>
</div>

<div class="card">
    <h2 style="margin-top:0;">By service line</h2>
    <table class="grid">
        <thead><tr><th>Service line</th><th>Tenders</th><th>Service requests</th><th>Projects</th></tr></thead>
        <tbody>
        @foreach ($serviceLineBreakdown as $line)
            <tr><td>{{ $line->name }}</td><td>{{ $line->tenders_count }}</td><td>{{ $line->service_requests_count }}</td><td>{{ $line->projects_count }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
