@extends('layouts.app')
@section('title', $tender->exists ? 'Edit tender' : 'Register tender')

@section('content')
<p><a href="{{ route('tenders.index') }}">&larr; Tenders</a></p>
<h1>{{ $tender->exists ? 'Edit tender' : 'Register a tender' }}</h1>

<form method="POST" action="{{ $tender->exists ? route('tenders.update', $tender) : route('tenders.store') }}" class="card">
    @csrf
    @if ($tender->exists) @method('PUT') <input type="hidden" name="lock_version" value="{{ $tender->lock_version }}"> @endif

    <div class="form-grid">
        <div class="full"><label>Title *</label><input type="text" name="title" value="{{ old('title', $tender->title) }}" required></div>
        <div><label>Client</label><input type="text" name="client" value="{{ old('client', $tender->client) }}"></div>
        <div><label>Buyer / Donor</label><input type="text" name="buyer" value="{{ old('buyer', $tender->buyer) }}"></div>
        <div><label>Country</label><input type="text" name="country" value="{{ old('country', $tender->country) }}"></div>
        <div><label>Sector</label><input type="text" name="sector" value="{{ old('sector', $tender->sector) }}"></div>
        <div><label>Service line</label>
            <select name="service_line_id"><option value="">—</option>
                @foreach ($serviceLines as $l)<option value="{{ $l->id }}" @selected((string)old('service_line_id',$tender->service_line_id)===(string)$l->id)>{{ $l->name }}</option>@endforeach
            </select></div>
        <div class="full">@include('partials._owner_picker', ['users' => $owners, 'name' => 'owner_ids', 'label' => 'Owners', 'selected' => old('owner_ids', $tender->exists ? $tender->owners->pluck('id')->all() : [])])</div>
        <div><label>Priority</label>
            <select name="priority">
                @foreach (\App\Enums\Priority::options() as $p)<option value="{{ $p['value'] }}" @selected(old('priority', $tender->priority?->value)===$p['value'])>{{ $p['label'] }}</option>@endforeach
            </select></div>
        <div><label>Value</label><input type="number" step="0.01" name="value" value="{{ old('value', $tender->value) }}"></div>
        <div><label>Estimated value</label><input type="number" step="0.01" name="estimated_value" value="{{ old('estimated_value', $tender->estimated_value) }}"></div>
        <div><label>Currency</label><input type="text" name="currency" value="{{ old('currency', $tender->currency) }}" placeholder="USD / TZS"></div>
        <div><label>Published date</label><input type="date" name="published_date" value="{{ old('published_date', optional($tender->published_date)->toDateString()) }}"></div>
        <div><label>Deadline</label><input type="date" name="deadline_date" value="{{ old('deadline_date', optional($tender->deadline_date)->toDateString()) }}"></div>
        <div class="full"><label>URL</label><input type="url" name="url" value="{{ old('url', $tender->url) }}"></div>
        <div class="full"><label>Description</label><textarea name="description">{{ old('description', $tender->description) }}</textarea></div>
        <div class="full"><label>Scope statement (inherited by a promoted project)</label><textarea name="scope_statement">{{ old('scope_statement', $tender->scope_statement) }}</textarea></div>
    </div>

    <button type="submit" style="margin-top:1rem;">{{ $tender->exists ? 'Save changes' : 'Register tender' }}</button>
</form>
@endsection
