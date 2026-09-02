{{-- $milestone, $canManage, $members, $taskStatuses --}}
<div style="border-top:1px solid var(--border); padding-top:.7rem; margin-top:.7rem;">
    <div style="display:flex; justify-content:space-between; gap:1rem;">
        <strong>{{ $milestone->name }}</strong>
        <span>
            @include('partials._badge', ['enum' => $milestone->status])
            @include('partials._progress', ['value' => $milestone->progress])
            @if($canManage)
            <form method="POST" action="{{ route('milestones.destroy', $milestone) }}" style="display:inline;">@csrf @method('DELETE')<button type="submit" class="link" style="color:var(--c-red);">×</button></form>
            @endif
        </span>
    </div>
    @if($milestone->description)<p class="muted" style="margin:.2rem 0;">{{ $milestone->description }}</p>@endif

    <div class="tree">
        @foreach ($milestone->featureSets as $fs)
            <ul><li>
                <strong>{{ $fs->name }}</strong>
                @include('partials._badge', ['enum' => $fs->status])
                @include('partials._progress', ['value' => $fs->progress])
                @if($canManage)
                <form method="POST" action="{{ route('feature-sets.destroy', $fs) }}" style="display:inline;">@csrf @method('DELETE')<button type="submit" class="link" style="color:var(--c-red);">×</button></form>
                @endif
                <ul>
                    @foreach ($fs->tasks as $task)
                        <li>
                            <form method="POST" action="{{ route('tasks.toggle', $task) }}" style="display:inline;">@csrf @method('PATCH')
                                <input type="checkbox" onchange="this.form.submit()" @checked($task->status === \App\Enums\TaskStatus::Done)>
                            </form>
                            {{ $task->title }}
                            @include('partials._badge', ['enum' => $task->status])
                            <span class="muted">{{ $task->assigneeNames() }} · {{ $task->progress }}%</span>
                            @foreach ($task->scopeItems as $si)<span class="chip" title="{{ $si->description }}">{{ $si->code }}</span>@endforeach
                            @if($task->subtasks->count())
                                <ul>
                                    @foreach ($task->subtasks as $st)
                                        <li>
                                            <form method="POST" action="{{ route('subtasks.toggle', $st) }}" style="display:inline;">@csrf @method('PATCH')
                                                <input type="checkbox" onchange="this.form.submit()" @checked($st->status === \App\Enums\TaskStatus::Done)>
                                            </form>
                                            {{ $st->title }} <span class="muted">{{ $st->progress }}%</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if($canManage)
                                <details><summary class="muted">+ sub-task</summary>
                                    <form method="POST" action="{{ route('tasks.subtasks.store', $task) }}">
                                        @csrf
                                        <input type="text" name="title" placeholder="Sub-task title" required>
                                        <input type="hidden" name="status" value="todo">
                                        <button class="small">Add</button>
                                    </form>
                                </details>
                            @endif
                        </li>
                    @endforeach
                    @if($canManage)
                        <li><details><summary class="muted">+ task</summary>
                            <form method="POST" action="{{ route('feature-sets.tasks.store', $fs) }}" class="form-grid" style="margin-top:.5rem;">
                                @csrf
                                <div class="full"><input type="text" name="title" placeholder="Task title" required></div>
                                <div><label>Assignees</label><select name="assignee_ids[]" multiple size="3">@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                                <div><label>Status</label><select name="status">@foreach($taskStatuses as $s)<option value="{{ $s['value'] }}">{{ $s['label'] }}</option>@endforeach</select></div>
                                <div><label>Priority</label><select name="priority">@foreach(\App\Enums\Priority::options() as $p)<option value="{{ $p['value'] }}" @selected($p['value']==='medium')>{{ $p['label'] }}</option>@endforeach</select></div>
                                <div><label>Due date</label><input type="date" name="due_date"></div>
                                <div style="align-self:end;"><button class="small">Add task</button></div>
                            </form>
                        </details></li>
                    @endif
                </ul>
            </li></ul>
        @endforeach
        @if($canManage)
            <details><summary class="muted">+ feature set</summary>
                <form method="POST" action="{{ route('milestones.feature-sets.store', $milestone) }}">
                    @csrf
                    <input type="text" name="name" placeholder="Feature set name" required>
                    <input type="hidden" name="status" value="not_started">
                    <button class="small">Add</button>
                </form>
            </details>
        @endif
    </div>
</div>
