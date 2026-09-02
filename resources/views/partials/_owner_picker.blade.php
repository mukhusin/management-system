{{-- $users (collection), $selected (array<int>), $name (e.g. "owner_ids"), $label --}}
@php($selected = collect($selected ?? [])->map(fn ($v) => (int) $v))
<fieldset style="border:1px solid var(--border-strong); border-radius:var(--radius-sm); padding:.5rem .7rem; min-width:0;">
    <legend style="font-size:.78rem; font-weight:600; color:var(--ink-soft); padding:0 .3rem;">{{ $label ?? 'Owners' }}</legend>
    <div style="display:flex; flex-wrap:wrap; gap:.4rem .9rem;">
        @foreach ($users as $u)
            <label style="font-weight:400; display:flex; align-items:center; gap:.35rem; margin:0;">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $u->id }}" @checked($selected->contains($u->id))>
                {{ $u->name }}
            </label>
        @endforeach
    </div>
</fieldset>
