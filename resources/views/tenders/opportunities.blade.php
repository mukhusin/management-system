@extends('layouts.app')
@section('title', 'Opportunities')

@section('content')
<div class="page-head">
    <div>
        <h1>Opportunities</h1>
        <p class="muted">
            Tenders ingested from external sources (World Bank, ReliefWeb, TANePS&hellip;).
            @if ($lastFetched)Last refreshed {{ \Illuminate\Support\Carbon::parse($lastFetched)->diffForHumans() }}.@endif
        </p>
    </div>
    <div style="display:flex; gap:.5rem;">
        @can('tenders.ingest')
        <form method="POST" action="{{ route('opportunities.fetch') }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Fetching…'">
            @csrf
            <button type="submit" class="btn ghost">Fetch latest</button>
        </form>
        @endcan
        <a href="{{ route('tenders.index') }}" class="btn">View pipeline</a>
    </div>
</div>

<div class="card">
    <form method="GET" class="filters">
        <div><label>Search</label><input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="title, buyer"></div>
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
        <div><label>Closing within</label>
            <select name="closing_within">
                <option value="">Any time</option>
                @foreach ([7 => '7 days', 14 => '14 days', 30 => '30 days', 60 => '60 days', 90 => '90 days'] as $days => $label)
                    <option value="{{ $days }}" @selected((string)($filters['closing_within'] ?? '') === (string)$days)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><label>Min value</label><input type="number" name="min_value" value="{{ $filters['min_value'] ?? '' }}" placeholder="e.g. 20000" style="min-width:110px;"></div>
        <div><label><input type="checkbox" name="open_only" value="1" @checked($filters['open_only'] ?? true)> Open only</label></div>
        <div><label><input type="checkbox" name="saved_only" value="1" @checked(!empty($filters['saved_only']))> Saved only</label></div>
        <div><button type="submit">Filter</button></div>
    </form>
    @if ($hiddenCount)
        <p class="muted" style="margin:.6rem 0 0;">
            {{ $hiddenCount }} hidden by you.
            <a href="{{ request()->fullUrlWithQuery(['show_hidden' => empty($filters['show_hidden']) ? 1 : null]) }}">
                {{ empty($filters['show_hidden']) ? 'Show hidden' : 'Hide them again' }}
            </a>
        </p>
    @endif
</div>

<div class="card">
    <div class="table-wrap">
    <table class="grid">
        <thead><tr><th>Title</th><th>Source</th><th>Country</th><th>Buyer</th><th>Deadline</th><th></th></tr></thead>
        <tbody>
        @forelse ($tenders as $tender)
            <tr @if($tender->isDismissedBy(auth()->user())) style="opacity:.55;" @endif>
                <td><a href="{{ route('tenders.show', $tender) }}">{{ $tender->title }}</a></td>
                <td><span class="badge badge--gray">{{ ucfirst(str_replace('_',' ',$tender->source)) }}</span></td>
                <td>{{ $tender->country ?? '—' }}</td>
                <td class="muted">{{ $tender->buyer ?? '—' }}</td>
                <td>@include('partials._due', ['model' => $tender, 'label' => 'Deadline'])</td>
                <td style="text-align:right; white-space:nowrap;">
                    <form method="POST" action="{{ route('opportunities.save', $tender) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn ghost small" title="{{ $tender->isSavedBy(auth()->user()) ? 'Remove from shortlist' : 'Save to your shortlist' }}">
                            {{ $tender->isSavedBy(auth()->user()) ? '★ Saved' : '☆ Save' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('opportunities.dismiss', $tender) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn ghost small">{{ $tender->isDismissedBy(auth()->user()) ? 'Unhide' : 'Hide' }}</button>
                    </form>
                    @can('tenders.create')
                    <form method="POST" action="{{ route('opportunities.pursue', $tender) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn small">Pursue</button>
                    </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">
                No open opportunities.
                @can('tenders.ingest')Use <strong>Fetch latest</strong> to pull from the configured sources.@endcan
            </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pagination">{{ $tenders->links() }}</div>
</div>
@endsection
