{{-- $phase, $project, $canManage, $members, $taskStatuses, $workStatuses, $isCurrent --}}
@php($enforced = \App\Models\Project::phaseGatesEnforced())
@php($incomplete = $phase->incompleteMilestones())
@php($phaseTasks = $phase->milestones->flatMap->featureSets->flatMap->tasks)

<div class="card" @if($isCurrent) style="border-color:var(--accent); box-shadow:0 0 0 1px var(--accent);" @endif>
    <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <div>
            <h2 style="margin:0;">
                <span class="chip">{{ $phase->position + 1 }}</span> {{ $phase->name }}
                @if($isCurrent)<span class="badge">current</span>@endif
                @include('partials._badge', ['enum' => $phase->status])
            </h2>
            @if($phase->description)<p class="muted" style="margin:.35rem 0;">{{ $phase->description }}</p>@endif
            <p class="muted" style="margin:.2rem 0;">
                Assignees: {{ $phase->assignees->pluck('name')->join(', ') ?: '—' }}
                @if($phase->starts_on || $phase->ends_on)
                    · {{ optional($phase->starts_on)->format('d M Y') ?? '…' }} → {{ optional($phase->ends_on)->format('d M Y') ?? '…' }}
                @endif
            </p>
        </div>
        <div style="text-align:right;">
            @include('partials._progress', ['value' => $phase->progress, 'width' => '130px'])
            @if($canManage)
                <details style="margin-top:.4rem;"><summary class="muted">edit phase</summary>
                    <form method="POST" action="{{ route('phases.update', $phase) }}" class="form-grid" style="margin-top:.5rem; text-align:left;">
                        @csrf @method('PUT')
                        <input type="hidden" name="lock_version" value="{{ $phase->lock_version }}">
                        <div class="full"><label>Name</label><input type="text" name="name" value="{{ $phase->name }}" required></div>
                        <div><label>Status</label><select name="status">@foreach($workStatuses as $s)<option value="{{ $s['value'] }}" @selected($phase->status->value===$s['value'])>{{ $s['label'] }}</option>@endforeach</select></div>
                        <div><label>Starts</label><input type="date" name="starts_on" value="{{ optional($phase->starts_on)->toDateString() }}"></div>
                        <div><label>Ends</label><input type="date" name="ends_on" value="{{ optional($phase->ends_on)->toDateString() }}"></div>
                        <div class="full"><label>Description</label><textarea name="description">{{ $phase->description }}</textarea></div>
                        <div class="full">@include('partials._owner_picker', ['users' => $members, 'name' => 'assignee_ids', 'label' => 'Phase assignees', 'selected' => $phase->assignees->pluck('id')->all()])</div>
                        <div style="align-self:end;"><button class="small">Save</button></div>
                    </form>
                    <form method="POST" action="{{ route('phases.destroy', $phase) }}" style="margin-top:.4rem;">@csrf @method('DELETE')<button type="submit" class="link" style="color:var(--c-red);">delete phase</button></form>
                </details>
            @endif
        </div>
    </div>

    {{-- Gate --}}
    @if ($phase->isSignedOff())
        <p class="muted" style="margin:.5rem 0;">✔ Signed off by {{ $phase->gateSignedBy?->name ?? 'system' }} · {{ $phase->gate_signed_at->format('d M Y H:i') }}{{ $phase->gate_forced ? ' · forced' : '' }}
            @if($phase->gate_note) — “{{ $phase->gate_note }}”@endif</p>
    @elseif ($canManage && auth()->user()->can('projects.edit'))
        @if ($incomplete->isNotEmpty())
            <p class="scope-uncovered" style="margin:.4rem 0;">{{ $incomplete->count() }} milestone(s) still open{{ $enforced ? ' — gate enforced' : '' }}.</p>
        @endif
        <form method="POST" action="{{ route('phases.sign-off', $phase) }}" style="display:flex; gap:.4rem; flex-wrap:wrap; align-items:center; margin:.4rem 0;">
            @csrf @method('PATCH')
            <input type="text" name="note" placeholder="Sign-off note (optional)" style="min-width:240px;">
            @if ($incomplete->isNotEmpty() && auth()->user()->isAdmin() && ! $enforced)
                <label class="muted" style="font-weight:400;"><input type="checkbox" name="force" value="1"> override open milestones</label>
            @endif
            <button type="submit" class="small">Sign off {{ $phase->name }}</button>
        </form>
    @endif

    @php($hasBody = $phase->scopeItems->isNotEmpty() || $phase->milestones->isNotEmpty())
    <details style="margin-top:.75rem;" @if($isCurrent || $hasBody) open @endif>
        <summary class="muted">Requirements &amp; milestones ({{ $phase->scopeItems->count() }} req · {{ $phase->milestones->count() }} milestones)</summary>

        <div style="margin-top:.6rem;">
            @include('partials._scope_table', [
                'items' => $phase->scopeItems,
                'tasks' => $phaseTasks,
                'canManage' => $canManage,
                'addAction' => route('scope-items.store', $project),
                'addPhaseId' => $phase->id,
                'title' => 'Phase requirements',
            ])
        </div>

        <h3 style="margin-top:1rem;">Milestones</h3>
        @forelse ($phase->milestones as $milestone)
            @include('partials._milestone', ['milestone' => $milestone, 'canManage' => $canManage, 'members' => $members, 'taskStatuses' => $taskStatuses])
        @empty
            <p class="muted">No milestones in this phase.</p>
        @endforelse

        @if($canManage && $project->workAllowedInPhase($phase))
            <details style="margin-top:.6rem;"><summary><strong>+ Add milestone</strong></summary>
                <form method="POST" action="{{ route('phases.milestones.store', $phase) }}" class="form-grid" style="margin-top:.6rem;">
                    @csrf
                    <div class="full"><label>Name *</label><input type="text" name="name" required></div>
                    <div><label>Status</label><select name="status">@foreach($workStatuses as $s)<option value="{{ $s['value'] }}">{{ $s['label'] }}</option>@endforeach</select></div>
                    <div><label>Due date</label><input type="date" name="due_date"></div>
                    <div class="full"><label>Description</label><textarea name="description"></textarea></div>
                    <div style="align-self:end;"><button class="small">Add milestone</button></div>
                </form>
            </details>
        @endif
    </details>
</div>
