{{-- $items, $tasks (for linking), $canManage, $addAction, $addPhaseId (nullable), $title --}}
@php($cov = ['total' => $items->count(), 'covered' => $items->filter->isCovered()->count()])
@php($pct = $cov['total'] ? round($cov['covered'] / $cov['total'] * 100) : 100)

<div class="cover" style="margin-bottom:.5rem;">
    <strong>{{ $title ?? 'Requirements' }}</strong>
    @if ($cov['total'])
        <span class="progress" style="width:110px;"><span style="width:{{ $pct }}%;"></span></span>
        <span class="muted">{{ $cov['covered'] }}/{{ $cov['total'] }} covered by a task</span>
    @else
        <span class="muted">none yet</span>
    @endif
</div>

@if ($items->isNotEmpty())
<table class="grid">
    <thead><tr><th>#</th><th>Requirement</th><th>Covering tasks</th><th></th></tr></thead>
    <tbody>
    @foreach ($items as $item)
        <tr>
            <td>{{ $item->code }}</td>
            <td class="{{ $item->isCovered() ? '' : 'scope-uncovered' }}">
                {{ $item->description }}
                @unless($item->isCovered())<span class="chip">uncovered</span>@endunless
                <span class="chip">{{ $item->source }}</span>
            </td>
            <td>@forelse ($item->tasks as $t)<span class="chip">{{ $t->title }}</span>@empty<span class="muted">—</span>@endforelse</td>
            <td>
                @if($canManage)
                <details><summary class="muted">edit</summary>
                    <form method="POST" action="{{ route('scope-items.update', $item) }}" style="margin-top:.4rem;">
                        @csrf @method('PUT')
                        <textarea name="description" rows="2">{{ $item->description }}</textarea>
                        <div class="muted" style="margin:.3rem 0;">Link tasks that satisfy this requirement:</div>
                        @foreach ($tasks as $t)
                            <label style="display:block; font-weight:400; font-size:.85rem;">
                                <input type="checkbox" name="task_ids[]" value="{{ $t->id }}" @checked($item->tasks->contains($t->id))> {{ $t->title }}
                            </label>
                        @endforeach
                        <button class="small" style="margin-top:.4rem;">Save</button>
                    </form>
                    <form method="POST" action="{{ route('scope-items.destroy', $item) }}" style="display:inline;">@csrf @method('DELETE')<button type="submit" class="link" style="color:var(--c-red);">delete</button></form>
                </details>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
    <p class="muted">No requirements yet.</p>
@endif

@if($canManage)
    <form method="POST" action="{{ $addAction }}" style="margin-top:.5rem; display:flex; gap:.4rem; flex-wrap:wrap;">
        @csrf
        @if(!empty($addPhaseId))<input type="hidden" name="phase_id" value="{{ $addPhaseId }}">@endif
        <input type="text" name="description" placeholder="New requirement" style="min-width:300px;" required>
        <button class="small">Add requirement</button>
    </form>
@endif
