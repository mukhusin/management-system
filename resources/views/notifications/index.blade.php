@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Notifications</h1>
    <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="ghost small">Mark all read</button></form>
</div>

<div class="card">
    <ul class="thread">
        @forelse ($notifications as $n)
            <li style="{{ $n->read_at ? 'opacity:0.6;' : '' }}">
                <div class="by">
                    {{ $n->data['by'] ?? 'Someone' }} mentioned you
                    <span class="at">{{ $n->created_at->diffForHumans() }}</span>
                </div>
                <div>
                    on {{ $n->data['subject_type'] ?? '' }}
                    @if(!empty($n->data['url']))<a href="{{ $n->data['url'] }}">{{ $n->data['subject_label'] ?? 'view' }}</a>@else {{ $n->data['subject_label'] ?? '' }} @endif
                    — <em>{{ $n->data['excerpt'] ?? '' }}</em>
                </div>
                @unless ($n->read_at)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}">@csrf @method('PATCH')<button class="ghost small">Mark read</button></form>
                @endunless
            </li>
        @empty <li class="muted">Nothing here.</li>
        @endforelse
    </ul>
    <div class="pagination">{{ $notifications->links() }}</div>
</div>
@endsection
