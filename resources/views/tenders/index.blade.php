@extends('layouts.app')
@section('title', 'Tenders')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Tenders</h1>
    @can('tenders.create')<a href="{{ route('tenders.create') }}" class="btn">+ Register tender</a>@endcan
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
        <div><label>Source</label>
            <select name="source"><option value="">All sources</option>
                @foreach ($sources as $s)<option value="{{ $s }}" @selected(($filters['source'] ?? '') === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
            </select>
        </div>
        <div><label>Country</label>
            <select name="country"><option value="">All countries</option>
                @foreach ($countries as $c)<option value="{{ $c }}" @selected(($filters['country'] ?? '') === $c)>{{ $c }}</option>@endforeach
            </select>
        </div>
        <div><label><input type="checkbox" name="open_only" value="1" @checked(!empty($filters['open_only']))> Open only</label></div>
        <div><button type="submit">Filter</button></div>
    </form>
</div>

<div class="card">
    <table class="grid">
        <thead><tr><th>Title</th><th>State</th><th>Owner</th><th>Service line</th><th>Deadline</th><th>Value</th></tr></thead>
        <tbody>
        @forelse ($tenders as $tender)
            <tr>
                <td><a href="{{ route('tenders.show', $tender) }}">{{ $tender->title }}</a>
                    <div class="muted">{{ $tender->client ?? $tender->buyer }} @if($tender->country)· {{ $tender->country }}@endif</div></td>
                <td>@include('partials._badge', ['enum' => $tender->state])</td>
                <td>{{ $tender->owner?->name ?? '—' }}</td>
                <td>{{ $tender->serviceLine?->name ?? '—' }}</td>
                <td>@include('partials._due', ['model' => $tender, 'label' => 'Deadline'])</td>
                <td>@if($tender->value){{ number_format($tender->value) }} {{ $tender->currency }}@else—@endif</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No tenders. Run <code>php artisan tenders:fetch</code> or register one.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $tenders->links() }}</div>
</div>
@endsection
