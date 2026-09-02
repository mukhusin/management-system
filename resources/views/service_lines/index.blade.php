@extends('layouts.app')
@section('title', 'Service lines')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Service lines</h1>
    <a href="{{ route('service-lines.create') }}" class="btn">+ Service line</a>
</div>

<div class="card">
    <table class="grid">
        <thead><tr><th>Name</th><th>Active</th><th>Tenders</th><th>Requests</th><th>Projects</th><th></th></tr></thead>
        <tbody>
        @foreach ($lines as $line)
            <tr>
                <td>{{ $line->name }}<div class="muted">{{ $line->description }}</div></td>
                <td>{{ $line->active ? 'Yes' : 'No' }}</td>
                <td>{{ $line->tenders_count }}</td>
                <td>{{ $line->service_requests_count }}</td>
                <td>{{ $line->projects_count }}</td>
                <td><a href="{{ route('service-lines.edit', $line) }}">Edit</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
