{{-- expects $subject (LogsAudit) --}}
<div class="card">
    <h2 style="margin-top:0;">Audit trail</h2>
    <ul class="thread">
        @forelse ($subject->auditLogs as $log)
            <li>
                <div class="by">{{ $log->summary() }}
                    <span class="at">{{ $log->user?->name ?? 'system' }} &middot; {{ $log->created_at?->format('d M Y H:i') }}</span>
                </div>
            </li>
        @empty
            <li class="muted">No recorded events.</li>
        @endforelse
    </ul>
</div>
