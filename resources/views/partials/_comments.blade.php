{{-- expects $subject (HasComments), $subjectType (route segment) --}}
<div class="card">
    <h2 style="margin-top:0;">Discussion</h2>

    @can('tenders.comment')
        <form method="POST" action="{{ route('comments.store', [$subjectType, $subject->id]) }}">
            @csrf
            <textarea name="body" rows="3" style="width:100%;" placeholder="Write a comment. Markdown supported. Mention colleagues with @name@emrec.co.tz" required></textarea>
            <button type="submit" class="small" style="margin-top:0.5rem;">Post comment</button>
        </form>
    @endcan

    <ul class="thread">
        @forelse ($subject->comments as $comment)
            <li>
                <div class="by">{{ $comment->user->name }}
                    <span class="at">{{ $comment->created_at->diffForHumans() }}</span>
                    @if ($comment->deletableBy(auth()->user()))
                        <form method="POST" action="{{ route('comments.destroy', $comment) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="ghost small danger" style="border:none; background:none; color:var(--c-red); padding:0;">delete</button>
                        </form>
                    @endif
                </div>
                <div>{!! $comment->renderedBody() !!}</div>
            </li>
        @empty
            <li class="muted">No comments yet.</li>
        @endforelse
    </ul>
</div>
