@extends('layouts.app')

@section('title', 'Tender & Aid Opportunities')

@section('content')
    <div class="card">
        <form method="GET" class="filters">
            <div>
                <label for="q">Search</label>
                <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="keyword, buyer...">
            </div>
            <div>
                <label for="source">Source</label>
                <select id="source" name="source">
                    <option value="">All sources</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>
                            {{ ucfirst(str_replace('_', ' ', $source)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="country">Country</label>
                <select id="country" name="country">
                    <option value="">All countries</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country }}" @selected(($filters['country'] ?? '') === $country)>
                            {{ $country }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="open_only">Status</label>
                <select id="open_only" name="open_only">
                    <option value="1" @selected(($filters['open_only'] ?? '1') == '1')>Open only</option>
                    <option value="0" @selected(($filters['open_only'] ?? '1') == '0')>Include closed</option>
                </select>
            </div>
            <div>
                <button type="submit">Filter</button>
            </div>
        </form>
    </div>

    @forelse ($tenders as $tender)
        <div class="card">
            <span class="badge">{{ ucfirst(str_replace('_', ' ', $tender->source)) }}</span>
            @if ($tender->country)
                <span class="badge">{{ $tender->country }}</span>
            @endif

            <div class="tender-title">
                <a href="{{ route('tenders.show', $tender) }}">{{ $tender->title }}</a>
            </div>

            <div class="meta">
                @if ($tender->buyer)
                    {{ $tender->buyer }} &middot;
                @endif
                @if ($tender->source === 'manual' && $tender->user)
                    Added by {{ $tender->user->name }} &middot;
                @endif
                @if ($tender->deadline_date)
                    <span class="{{ $tender->isClosingSoon() ? 'deadline-soon' : '' }}">
                        Deadline: {{ $tender->deadline_date->format('d M Y') }}
                        ({{ $tender->deadlineCountdown() }})
                    </span>
                @else
                    No deadline listed
                @endif
            </div>
        </div>
    @empty
        <div class="card">
            No opportunities found yet. Run <code>php artisan tenders:fetch</code> to pull in the latest notices.
        </div>
    @endforelse

    <div class="pagination">
        {{ $tenders->links() }}
    </div>
@endsection
