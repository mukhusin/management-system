@extends('layouts.app')
@section('title', $item->title)

@section('content')
<p><a href="{{ route('tracker.index') }}">&larr; Tracker</a></p>

<div class="card">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 style="margin-bottom:0.3rem;">{{ $item->title }}</h1>
            <div>
                @include('partials._badge', ['enum' => $item->category])
                @include('partials._badge', ['enum' => $item->status])
                @include('partials._badge', ['enum' => $item->priority])
            </div>
        </div>
        <div>@can('tracker.manage')<a href="{{ route('tracker.edit', $item) }}" class="btn ghost small">Edit</a>@endcan</div>
    </div>
    <p class="meta">
        {{ $item->reference }} · Owner: {{ $item->owner?->name ?? 'unassigned' }}
        @if($item->serviceLine) · {{ $item->serviceLine->name }}@endif<br>
        @if(!is_null($item->progress))@include('partials._progress', ['value' => $item->progress])<br>@endif
        @include('partials._due', ['model' => $item, 'label' => 'Due'])<br>
        @if($item->next_action)Next action: {{ $item->next_action }}@endif
    </p>
    @if($item->description)<p>{{ $item->description }}</p>@endif
    @if($item->remarks)<p><strong>Remarks:</strong> {{ $item->remarks }}</p>@endif
</div>

@include('partials._comments', ['subject' => $item, 'subjectType' => 'tracker'])
@include('partials._attachments', ['subject' => $item, 'subjectType' => 'tracker'])
@include('partials._audit', ['subject' => $item])
@endsection
