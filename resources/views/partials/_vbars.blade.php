{{-- $bars: iterable of ['label' => string, 'n' => int]. Vertical bar chart, scaled to the max. --}}
@php($max = collect($bars)->max('n') ?: 1)
<div class="vbars">
    @foreach ($bars as $b)
        <div class="vbar-col">
            <div class="vbar-fill {{ $b['n'] > 0 && ($loop->index < 2) ? 'hi' : '' }}" style="height:{{ $b['n'] ? max(6, round($b['n'] / $max * 100)) : 2 }}%;" title="{{ $b['n'] }}"></div>
            <div class="vbar-label">{{ $b['label'] }}</div>
        </div>
    @endforeach
</div>
