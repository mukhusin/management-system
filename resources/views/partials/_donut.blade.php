{{--
    $counts: assoc array label => count (already sorted/limited by the caller).
    Pure-CSS donut via conic-gradient — no JS, no chart library.
--}}
@php
    $palette = ['#4f46e5', '#7c3aed', '#2563eb', '#15803d', '#b45309', '#dc2626', '#0ea5e9', '#6b7280'];
    $total = array_sum($counts) ?: 1;
    $acc = 0;
    $stops = [];
    $i = 0;
    foreach ($counts as $label => $count) {
        $start = $acc / $total * 360;
        $acc += $count;
        $end = $acc / $total * 360;
        $stops[] = ($palette[$i % count($palette)]) . " {$start}deg {$end}deg";
        $i++;
    }
    $gradient = 'conic-gradient(' . implode(',', $stops) . ')';
    $size = $size ?? 108;
@endphp
<div class="donut-wrap">
    <div class="donut" style="width:{{ $size }}px; height:{{ $size }}px; background:{{ $gradient }};">
        <div class="donut-center"><b>{{ $total }}</b><span>{{ $unit ?? 'items' }}</span></div>
    </div>
    <div class="legend">
        @php($i = 0)
        @forelse ($counts as $label => $count)
            <div class="legend-row">
                <span class="legend-dot" style="background:{{ $palette[$i % count($palette)] }};"></span>
                {{ $label }}
                <b>{{ $count }}</b>
            </div>
            @php($i++)
        @empty
            <div class="muted">No data yet.</div>
        @endforelse
    </div>
</div>
