@extends('layouts.app')
@section('title', 'Team')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Team</h1>
    <div>
        <a href="{{ route('users.create') }}" class="btn">+ Account</a>
        @can('services.manage')<a href="{{ route('service-lines.index') }}" class="btn ghost">Service lines</a>@endcan
    </div>
</div>

<div class="card">
    <table class="grid">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Overrides</th><th></th></tr></thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>@include('partials._badge', ['enum' => $user->role])</td>
                <td class="muted">{{ $user->overrides()->count() ?: '—' }}</td>
                <td><a href="{{ route('users.edit', $user) }}">Edit</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="pagination">{{ $users->links() }}</div>
</div>
@endsection
