{{-- $rows: iterable of ['label' => string, 'n' => int]. Bars scale to the max. --}}
@php($max = collect($rows)->max('n') ?: 1)
@foreach ($rows as $row)
    <div class="bar-row">
        <span class="lab">{{ $row['label'] }}</span>
        @if ($row['n'])<span class="bar" style="width:{{ max(6, round($row['n'] / $max * 200)) }}px;"></span>@endif
        <span class="muted">{{ $row['n'] }}</span>
    </div>
@endforeach
