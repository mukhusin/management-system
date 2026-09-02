{{-- expects $subject (HasAttachments), $subjectType --}}
<div class="card">
    <h2 style="margin-top:0;">Attachments</h2>

    <form method="POST" action="{{ route('attachments.store', [$subjectType, $subject->id]) }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" required>
        <button type="submit" class="small">Upload</button>
    </form>

    <ul style="list-style:none; padding:0; margin:0.75rem 0 0;">
        @forelse ($subject->attachments as $file)
            <li style="border-top:1px solid var(--border); padding:0.5rem 0;">
                <a href="{{ route('attachments.download', $file) }}">{{ $file->original_name }}</a>
                <span class="muted">— {{ $file->humanSize() }}, {{ $file->user?->name ?? 'system' }}, {{ $file->created_at->diffForHumans() }}</span>
                @if ($file->deletableBy(auth()->user()))
                    <form method="POST" action="{{ route('attachments.destroy', $file) }}" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="ghost small" style="border:none; background:none; color:var(--c-red);">remove</button>
                    </form>
                @endif
            </li>
        @empty
            <li class="muted">No files attached.</li>
        @endforelse
    </ul>
</div>
