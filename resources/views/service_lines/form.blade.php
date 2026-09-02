@extends('layouts.app')
@section('title', $line->exists ? 'Edit service line' : 'New service line')

@section('content')
<p><a href="{{ route('service-lines.index') }}">&larr; Service lines</a></p>
<h1>{{ $line->exists ? 'Edit service line' : 'New service line' }}</h1>

<form method="POST" action="{{ $line->exists ? route('service-lines.update', $line) : route('service-lines.store') }}" class="card">
    @csrf
    @if ($line->exists) @method('PUT') @endif
    <div class="form-grid">
        <div><label>Name *</label><input type="text" name="name" value="{{ old('name', $line->name) }}" required></div>
        <div><label>Position</label><input type="number" name="position" value="{{ old('position', $line->position) }}"></div>
        <div><label><input type="checkbox" name="active" value="1" @checked(old('active', $line->active))> Active</label></div>
        <div class="full"><label>Description</label><textarea name="description">{{ old('description', $line->description) }}</textarea></div>
    </div>
    <button type="submit" style="margin-top:1rem;">Save</button>
</form>
@endsection
