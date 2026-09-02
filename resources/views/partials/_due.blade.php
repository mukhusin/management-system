{{-- expects $model (uses HasDueDate) and optional $label --}}
@php($cd = $model->dueCountdown())
@if ($model->{$model->dueDateColumn()})
    <span class="{{ $model->isDueSoon() ? 'due-soon' : '' }}">
        {{ $label ?? 'Due' }}: {{ \Illuminate\Support\Carbon::parse($model->{$model->dueDateColumn()})->format('d M Y') }}
        @if ($cd) <span class="muted">({{ $cd }})</span> @endif
    </span>
@else
    <span class="muted">No {{ strtolower($label ?? 'due date') }}</span>
@endif
