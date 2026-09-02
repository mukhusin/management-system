@extends('layouts.app')
@section('title', $request->summary)

@section('content')
<p><a href="{{ route('service-requests.index') }}">&larr; Service Requests</a></p>

<div class="card">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 style="margin-bottom:0.3rem;">{{ $request->summary }}</h1>
            <div>
                @include('partials._badge', ['enum' => $request->state])
                @include('partials._badge', ['enum' => $request->priority])
                @include('partials._badge', ['enum' => $request->source])
                @if($request->serviceLine)<span class="badge badge--gray">{{ $request->serviceLine->name }}</span>@endif
            </div>
        </div>
        <div>
            @can('service_requests.edit')<a href="{{ route('service-requests.edit', $request) }}" class="btn ghost small">Edit</a>@endcan
        </div>
    </div>
    <p class="meta">
        {{ $request->reference }}<br>
        Client: {{ $request->client ?? '—' }} · Contact: {{ $request->contact_name ?? '—' }}
        {{ $request->contact_email ? '('.$request->contact_email.')' : '' }}<br>
        Owner: {{ $request->owner?->name ?? 'unassigned' }}<br>
        @if($request->estimated_value)Estimated value: {{ number_format($request->estimated_value, 2) }} {{ $request->currency }}@endif
    </p>
    @if($request->details)<p>{{ $request->details }}</p>@endif
</div>

@can('service_requests.transition')
<div class="card">
    <h2 style="margin-top:0;">Lifecycle</h2>
    <div class="steps">
        @foreach (\App\Enums\ServiceRequestState::cases() as $s)
            <span class="step {{ $request->state === $s ? 'current' : '' }}">{{ $s->label() }}</span>
        @endforeach
    </div>
    @forelse ($nextStates as $next)
        <form method="POST" action="{{ route('service-requests.transition', $request) }}" style="display:inline-block; margin-right:0.5rem;">
            @csrf @method('PATCH')
            <input type="hidden" name="state" value="{{ $next->value }}">
            <button type="submit" class="small">Move to {{ $next->label() }}</button>
        </form>
    @empty <span class="muted">No further transitions.</span>
    @endforelse

    @if ($request->state === \App\Enums\ServiceRequestState::Won && ! $request->project)
        @can('projects.initiate')
        <form method="POST" action="{{ route('service-requests.promote', $request) }}" style="margin-top:0.75rem;">
            @csrf @method('PATCH')
            <button type="submit">Initiate project from this request</button>
        </form>
        @endcan
    @elseif ($request->project)
        <p style="margin-top:0.75rem;">Project: <a href="{{ route('projects.show', $request->project) }}">{{ $request->project->name }}</a></p>
    @endif
</div>
@endcan

@include('partials._comments', ['subject' => $request, 'subjectType' => 'service-requests'])
@include('partials._attachments', ['subject' => $request, 'subjectType' => 'service-requests'])
@include('partials._audit', ['subject' => $request])
@endsection
