@extends('layouts.app')
@section('title', 'Tender Pipeline')

@section('content')
<div class="page-head">
    <div>
        <h1>Tender Pipeline</h1>
        <p class="muted">Tenders you're pursuing, tracked through their lifecycle.</p>
    </div>
    <div style="display:flex; gap:.5rem;">
        <a href="{{ route('opportunities.index') }}" class="btn ghost">Browse opportunities</a>
        @can('tenders.create')<a href="{{ route('tenders.create') }}" class="btn">+ Register tender</a>@endcan
    </div>
</div>

<div class="card">
    <form method="GET" class="filters">
        <div><label>Search</label><input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="title, buyer, client"></div>
        <div><label>State</label>
            <select name="state"><option value="">Any state</option>
                @foreach ($states as $s)<option value="{{ $s['value'] }}" @selected(($filters['state'] ?? '') === $s['value'])>{{ $s['label'] }}</option>@endforeach
            </select>
        </div>
        <div><label>Owner</label>
            <select name="owner"><option value="">Anyone</option>
                @foreach ($owners as $o)<option value="{{ $o->id }}" @selected((string)($filters['owner'] ?? '') === (string)$o->id)>{{ $o->name }}</option>@endforeach
            </select>
        </div>
        <div><label><input type="checkbox" name="open_only" value="1" @checked(!empty($filters['open_only']))> Open only</label></div>
        <div><button type="submit">Filter</button></div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="grid">
        <thead><tr><th>Title</th><th>State</th><th>Owner(s)</th><th>Service line</th><th>Deadline</th><th>Value</th><th>Project</th></tr></thead>
        <tbody>
        @forelse ($tenders as $tender)
            <tr>
                <td><a href="{{ route('tenders.show', $tender) }}">{{ $tender->title }}</a>
                    <div class="muted">{{ $tender->client ?? $tender->buyer }} @if($tender->country)· {{ $tender->country }}@endif</div></td>
                <td>@include('partials._badge', ['enum' => $tender->state])</td>
                <td>{{ $tender->owners->pluck('name')->join(', ') ?: '—' }}</td>
                <td>{{ $tender->serviceLine?->name ?? '—' }}</td>
                <td>@include('partials._due', ['model' => $tender, 'label' => 'Deadline'])</td>
                <td>@if($tender->value){{ number_format($tender->value) }} {{ $tender->currency }}@else—@endif</td>
                <td>@if($tender->project)<a href="{{ route('projects.show', $tender->project) }}">open</a>@else—@endif</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">
                Nothing in the pipeline yet — <a href="{{ route('opportunities.index') }}">browse opportunities</a>
                and pursue the ones worth bidding on, or <a href="{{ route('tenders.create') }}">register one</a>.
            </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pagination">{{ $tenders->links() }}</div>
</div>
@endsection
