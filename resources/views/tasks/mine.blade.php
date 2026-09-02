@extends('layouts.app')
@section('title', 'My Work')

@section('content')
<h1>My Work</h1>

@forelse ($grouped as $projectName => $tasks)
    <div class="card">
        <h2 style="margin-top:0;">{{ $projectName }}</h2>
        <table class="grid">
            <thead><tr><th></th><th>Task</th><th>Status</th><th>Priority</th><th>Progress</th><th>Due</th></tr></thead>
            <tbody>
            @foreach ($tasks as $task)
                <tr>
                    <td>
                        <form method="POST" action="{{ route('tasks.toggle', $task) }}">@csrf @method('PATCH')
                            <input type="checkbox" onchange="this.form.submit()" @checked($task->status === \App\Enums\TaskStatus::Done)>
                        </form>
                    </td>
                    <td>{{ $task->title }}</td>
                    <td>@include('partials._badge', ['enum' => $task->status])</td>
                    <td>@include('partials._badge', ['enum' => $task->priority])</td>
                    <td>@include('partials._progress', ['value' => $task->progress])</td>
                    <td>@include('partials._due', ['model' => $task, 'label' => 'Due'])</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="card muted">No tasks assigned to you.</div>
@endforelse
@endsection
