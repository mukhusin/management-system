{{-- expects $subject (HasComments), $subjectType (route segment) --}}
<div class="card">
    <h2 style="margin-top:0;">Discussion</h2>

    @can('tenders.comment')
        <form method="POST" action="{{ route('comments.store', [$subjectType, $subject->id]) }}">
            @csrf
            <textarea name="body" data-editor rows="3" placeholder="Write a comment. Markdown supported. Type @ to mention a colleague." required></textarea>
            <button type="submit" class="small" style="margin-top:0.5rem;">Post comment</button>
        </form>
        @include('partials._editor')
    @endcan

    <ul class="thread">
        @forelse ($subject->comments as $comment)
            <li>
                <div class="by">{{ $comment->user->name }}
                    <span class="at">{{ $comment->created_at->diffForHumans() }}</span>
                    @if ($comment->deletableBy(auth()->user()))
                        <form method="POST" action="{{ route('comments.destroy', $comment) }}" style="display:inline; margin-left:.4rem;">
                            @csrf @method('DELETE')
                            <button type="submit" class="link" style="color:var(--c-red); font-size:.75rem;">delete</button>
                        </form>
                    @endif
                </div>
                <div class="md-body">{!! $comment->renderedBody() !!}</div>
            </li>
        @empty
            <li class="muted">No comments yet.</li>
        @endforelse
    </ul>
</div>
