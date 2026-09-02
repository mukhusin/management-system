@extends('layouts.app')

@section('title', $tender->title)

@section('content')
    <p><a href="{{ route('tenders.index') }}">&larr; Back to all opportunities</a></p>

    <div class="card">
        <span class="badge">{{ ucfirst(str_replace('_', ' ', $tender->source)) }}</span>
        @if ($tender->country)
            <span class="badge">{{ $tender->country }}</span>
        @endif
        @if ($tender->sector)
            <span class="badge">{{ $tender->sector }}</span>
        @endif

        <h1>{{ $tender->title }}</h1>

        <p class="meta">
            @if ($tender->buyer) Buyer / Donor: {{ $tender->buyer }}<br> @endif
            @if ($tender->published_date) Published: {{ $tender->published_date->format('d M Y') }}<br> @endif
            @if ($tender->deadline_date)
                Deadline: {{ $tender->deadline_date->format('d M Y') }}
                <span class="{{ $tender->isClosingSoon() ? 'deadline-soon' : '' }}">({{ $tender->deadlineCountdown() }})</span><br>
            @endif
            @if ($tender->value) Value: {{ number_format($tender->value, 2) }} {{ $tender->currency }}<br> @endif
        </p>

        @if ($tender->description)
            <p>{{ $tender->description }}</p>
        @endif

        @if ($tender->url)
            <p><a href="{{ $tender->url }}" target="_blank" rel="noopener">View original notice &rarr;</a></p>
        @endif
    </div>
@endsection
