@php($enum = $enum ?? null)
@if ($enum)
    <span class="badge badge--{{ method_exists($enum, 'color') ? $enum->color() : 'gray' }}">{{ $enum->label() }}</span>
@endif
