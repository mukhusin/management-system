@extends('layouts.app')
@section('title', $item->exists ? 'Edit tracker item' : 'New tracker item')

@section('content')
<p><a href="{{ route('tracker.index') }}">&larr; Tracker</a></p>
<h1>{{ $item->exists ? 'Edit '.$item->reference : 'New tracker item' }}</h1>

<form method="POST" action="{{ $item->exists ? route('tracker.update', $item) : route('tracker.store') }}" class="card">
    @csrf
    @if ($item->exists) @method('PUT') <input type="hidden" name="lock_version" value="{{ $item->lock_version }}"> @endif
    <div class="form-grid">
        <div class="full"><label>Title *</label><input type="text" name="title" value="{{ old('title', $item->title) }}" required></div>
        <div><label>Category *</label><select name="category">
            @foreach ($categories as $c)<option value="{{ $c['value'] }}" @selected(old('category', $item->category?->value)===$c['value'])>{{ $c['label'] }}</option>@endforeach
        </select></div>
        <div><label>Status *</label><select name="status">
            @foreach ($statuses as $s)<option value="{{ $s['value'] }}" @selected(old('status', $item->status?->value)===$s['value'])>{{ $s['label'] }}</option>@endforeach
        </select></div>
        <div><label>Priority *</label><select name="priority">
            @foreach (\App\Enums\Priority::options() as $p)<option value="{{ $p['value'] }}" @selected(old('priority', $item->priority?->value)===$p['value'])>{{ $p['label'] }}</option>@endforeach
        </select></div>
        <div><label>Owner</label><select name="owner_id"><option value="">—</option>
            @foreach ($owners as $o)<option value="{{ $o->id }}" @selected((string)old('owner_id',$item->owner_id)===(string)$o->id)>{{ $o->name }}</option>@endforeach
        </select></div>
        <div><label>Service line</label><select name="service_line_id"><option value="">—</option>
            @foreach ($serviceLines as $l)<option value="{{ $l->id }}" @selected((string)old('service_line_id',$item->service_line_id)===(string)$l->id)>{{ $l->name }}</option>@endforeach
        </select></div>
        <div><label>Progress %</label><input type="number" min="0" max="100" name="progress" value="{{ old('progress', $item->progress) }}"></div>
        <div><label>Entry date</label><input type="date" name="entry_date" value="{{ old('entry_date', optional($item->entry_date)->toDateString()) }}"></div>
        <div><label>Due date</label><input type="date" name="due_date" value="{{ old('due_date', optional($item->due_date)->toDateString()) }}"></div>
        <div class="full"><label>Next action</label><input type="text" name="next_action" value="{{ old('next_action', $item->next_action) }}"></div>
        <div class="full"><label>Description</label><textarea name="description">{{ old('description', $item->description) }}</textarea></div>
        <div class="full"><label>Remarks / outcome</label><textarea name="remarks">{{ old('remarks', $item->remarks) }}</textarea></div>
    </div>
    <button type="submit" style="margin-top:1rem;">{{ $item->exists ? 'Save' : 'Create' }}</button>
</form>
@endsection
