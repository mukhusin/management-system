@extends('layouts.app')
@section('title', 'Audit log')

@section('content')
<h1>Audit log</h1>

<div class="card">
    <form method="GET" class="filters">
        <div><label>Event</label><select name="event"><option value="">Any</option>
            @foreach ($events as $e)<option value="{{ $e }}" @selected(($filters['event'] ?? '')===$e)>{{ $e }}</option>@endforeach
        </select></div>
        <div><label>Type</label><select name="type"><option value="">Any</option>
            @foreach ($types as $t)<option value="{{ $t }}" @selected(($filters['type'] ?? '')===$t)>{{ class_basename($t) }}</option>@endforeach
        </select></div>
        <div><label>User</label><select name="user"><option value="">Anyone</option>
            @foreach ($users as $u)<option value="{{ $u->id }}" @selected((string)($filters['user'] ?? '')===(string)$u->id)>{{ $u->name }}</option>@endforeach
        </select></div>
        <div><button type="submit">Filter</button></div>
    </form>
</div>

<div class="card">
    <table class="grid">
        <thead><tr><th>When</th><th>User</th><th>Subject</th><th>Event</th></tr></thead>
        <tbody>
        @forelse ($logs as $log)
            <tr>
                <td class="muted">{{ $log->created_at?->format('d M Y H:i') }}</td>
                <td>{{ $log->user?->name ?? 'system' }}</td>
                <td>{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                <td>{{ $log->summary() }}</td>
            </tr>
        @empty <tr><td colspan="4" class="muted">No events.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $logs->links() }}</div>
</div>
@endsection
