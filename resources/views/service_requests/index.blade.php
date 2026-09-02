@extends('layouts.app')
@section('title', 'Service Requests')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Service Requests</h1>
    @can('service_requests.create')<a href="{{ route('service-requests.create') }}" class="btn">+ Log request</a>@endcan
</div>

<div class="card">
    <form method="GET" class="filters">
        <div><label>Search</label><input type="text" name="q" value="{{ $filters['q'] ?? '' }}"></div>
        <div><label>State</label><select name="state"><option value="">Any</option>
            @foreach ($states as $s)<option value="{{ $s['value'] }}" @selected(($filters['state'] ?? '')===$s['value'])>{{ $s['label'] }}</option>@endforeach
        </select></div>
        <div><label>Service line</label><select name="service_line"><option value="">Any</option>
            @foreach ($serviceLines as $l)<option value="{{ $l->id }}" @selected((string)($filters['service_line'] ?? '')===(string)$l->id)>{{ $l->name }}</option>@endforeach
        </select></div>
        <div><label>Owner</label><select name="owner"><option value="">Anyone</option>
            @foreach ($owners as $o)<option value="{{ $o->id }}" @selected((string)($filters['owner'] ?? '')===(string)$o->id)>{{ $o->name }}</option>@endforeach
        </select></div>
        <div><button type="submit">Filter</button></div>
    </form>
</div>

<div class="card">
    <table class="grid">
        <thead><tr><th>Ref</th><th>Summary</th><th>State</th><th>Client</th><th>Service line</th><th>Owner</th><th>Value</th></tr></thead>
        <tbody>
        @forelse ($requests as $r)
            <tr>
                <td class="muted">{{ $r->reference }}</td>
                <td><a href="{{ route('service-requests.show', $r) }}">{{ $r->summary }}</a></td>
                <td>@include('partials._badge', ['enum' => $r->state])</td>
                <td>{{ $r->client ?? $r->contact_name ?? '—' }}</td>
                <td>{{ $r->serviceLine?->name ?? '—' }}</td>
                <td>{{ $r->owner?->name ?? '—' }}</td>
                <td>@if($r->estimated_value){{ number_format($r->estimated_value) }} {{ $r->currency }}@else—@endif</td>
            </tr>
        @empty <tr><td colspan="7" class="muted">No service requests logged.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $requests->links() }}</div>
</div>
@endsection
