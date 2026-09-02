@php($value = (int) ($value ?? 0))
<span style="display:inline-flex; align-items:center; gap:0.4rem;">
    <span class="progress" style="width:{{ $width ?? '90px' }};"><span style="width:{{ max(0, min(100, $value)) }}%;"></span></span>
    <span class="muted">{{ $value }}%</span>
</span>
