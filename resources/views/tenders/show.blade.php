@extends('layouts.app')
@section('title', $tender->title)

@section('content')
<p><a href="{{ route('tenders.index') }}">&larr; Tenders</a></p>

<div class="card">
    <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <div>
            <h1 style="margin-bottom:0.3rem;">{{ $tender->title }}</h1>
            <div>
                @include('partials._badge', ['enum' => $tender->state])
                @if($tender->priority)@include('partials._badge', ['enum' => $tender->priority])@endif
                @if($tender->serviceLine)<span class="badge badge--gray">{{ $tender->serviceLine->name }}</span>@endif
            </div>
        </div>
        <div style="text-align:right;">
            @can('tenders.edit')<a href="{{ route('tenders.edit', $tender) }}" class="btn ghost small">Edit</a>@endcan
        </div>
    </div>

    <p class="meta">
        {{ $tender->client ?? $tender->buyer }} @if($tender->country) · {{ $tender->country }} @endif<br>
        Owners: {{ $tender->ownerNames() }}<br>
        @include('partials._due', ['model' => $tender, 'label' => 'Deadline'])<br>
        @if($tender->value || $tender->estimated_value)
            Value: {{ number_format($tender->value ?? $tender->estimated_value, 2) }} {{ $tender->currency }}<br>
        @endif
        @if($tender->url)<a href="{{ $tender->url }}" target="_blank" rel="noopener">Original notice &rarr;</a>@endif
    </p>

    @if($tender->scope_statement)<p><strong>Scope:</strong> {{ $tender->scope_statement }}</p>@endif
    @if($tender->description)<p>{{ $tender->description }}</p>@endif
</div>

@can('tenders.transition')
<div class="card">
    <h2 style="margin-top:0;">Lifecycle</h2>
    <div class="steps">
        @foreach (\App\Enums\TenderState::cases() as $s)
            <span class="step {{ $tender->state === $s ? 'current' : '' }}">{{ $s->label() }}</span>
        @endforeach
    </div>
    @forelse ($nextStates as $next)
        <form method="POST" action="{{ route('tenders.transition', $tender) }}" style="display:inline-block; margin-right:0.5rem;">
            @csrf @method('PATCH')
            <input type="hidden" name="state" value="{{ $next->value }}">
            <button type="submit" class="small">Move to {{ $next->label() }}</button>
        </form>
    @empty
        <span class="muted">No further transitions.</span>
    @endforelse

    @if ($tender->state === \App\Enums\TenderState::Won)
        @if ($tender->project)
            <p style="margin-top:0.75rem;">Project: <a href="{{ route('projects.show', $tender->project) }}">{{ $tender->project->name }}</a></p>
        @else
            @can('projects.initiate')
            <form method="POST" action="{{ route('tenders.promote', $tender) }}" style="margin-top:0.75rem;">
                @csrf @method('PATCH')
                <button type="submit">Initiate project from this tender</button>
            </form>
            @endcan
        @endif
    @endif
</div>
@endcan

@include('partials._comments', ['subject' => $tender, 'subjectType' => 'tenders'])
@include('partials._attachments', ['subject' => $tender, 'subjectType' => 'tenders'])
@include('partials._audit', ['subject' => $tender])
@endsection
