@extends('layouts.app')
@section('title', 'Tracker')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Business Tracker</h1>
    <div>
        @can('tracker.manage')<a href="{{ route('tracker.create') }}" class="btn">+ Item</a>@endcan
        @can('users.manage')<a href="{{ route('import.create') }}" class="btn ghost">Import CSV</a>@endcan
    </div>
</div>

<div class="card">
    <form method="GET" class="filters">
        <div><label>Search</label><input type="text" name="q" value="{{ $filters['q'] ?? '' }}"></div>
        <div><label>Category</label><select name="category"><option value="">Any</option>
            @foreach ($categories as $c)<option value="{{ $c['value'] }}" @selected(($filters['category'] ?? '')===$c['value'])>{{ $c['label'] }}</option>@endforeach
        </select></div>
        <div><label>Status</label><select name="status"><option value="">Any</option>
            @foreach ($statuses as $s)<option value="{{ $s['value'] }}" @selected(($filters['status'] ?? '')===$s['value'])>{{ $s['label'] }}</option>@endforeach
        </select></div>
        <div><label>Owner</label><select name="owner"><option value="">Anyone</option>
            @foreach ($owners as $o)<option value="{{ $o->id }}" @selected((string)($filters['owner'] ?? '')===(string)$o->id)>{{ $o->name }}</option>@endforeach
        </select></div>
        <div><button type="submit">Filter</button></div>
    </form>
</div>

<div class="card">
    <table class="grid">
        <thead><tr><th>Ref</th><th>Title</th><th>Category</th><th>Status</th><th>Owner</th><th>Priority</th><th>Progress</th><th>Due</th></tr></thead>
        <tbody>
        @forelse ($items as $item)
            <tr>
                <td class="muted">{{ $item->reference }}</td>
                <td><a href="{{ route('tracker.show', $item) }}">{{ $item->title }}</a><div class="muted">{{ $item->next_action }}</div></td>
                <td>@include('partials._badge', ['enum' => $item->category])</td>
                <td>@include('partials._badge', ['enum' => $item->status])</td>
                <td>{{ $item->owner?->name ?? '—' }}</td>
                <td>@include('partials._badge', ['enum' => $item->priority])</td>
                <td>@if(!is_null($item->progress))@include('partials._progress', ['value' => $item->progress])@else—@endif</td>
                <td>@include('partials._due', ['model' => $item, 'label' => 'Due'])</td>
            </tr>
        @empty <tr><td colspan="8" class="muted">No tracker items.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $items->links() }}</div>
</div>
@endsection
