@extends('layouts.app')
@section('title', $user->exists ? 'Edit account' : 'New account')

@section('content')
<p><a href="{{ route('users.index') }}">&larr; Team</a></p>
<h1>{{ $user->exists ? 'Edit '.$user->name : 'New account' }}</h1>

<form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="card">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <div class="form-grid">
        <div><label>Name *</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div><label>Email *</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
        <div><label>Role *</label><select name="role" id="role">
            @foreach (\App\Enums\UserRole::options() as $r)<option value="{{ $r['value'] }}" @selected(old('role', $user->role?->value)===$r['value'])>{{ $r['label'] }}</option>@endforeach
        </select></div>
    </div>
    @unless ($user->exists)
        <p class="muted">A random temporary password is generated and shown once after creation.</p>
    @endunless

    <h2>Permissions</h2>
    <p class="muted">Ticked = allowed. Boxes that differ from the role's default are saved as a per-user override.</p>
    <div class="form-grid">
        @foreach ($catalog as $key => $label)
            @php($default = in_array('*', $roleDefaults[$user->role?->value] ?? [], true) || in_array($key, $roleDefaults[$user->role?->value] ?? [], true))
            @php($checked = array_key_exists($key, $overrides) ? $overrides[$key] : $default)
            <label style="display:flex; gap:0.5rem; align-items:flex-start;">
                <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked($checked)>
                <span><code>{{ $key }}</code><br><span class="muted">{{ $label }}</span></span>
            </label>
        @endforeach
    </div>

    <button type="submit" style="margin-top:1rem;">{{ $user->exists ? 'Save' : 'Create account' }}</button>
</form>

@if ($user->exists && $user->id !== auth()->id())
<form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this account?')">
    @csrf @method('DELETE')
    <button class="danger">Delete account</button>
</form>
@endif
@endsection
