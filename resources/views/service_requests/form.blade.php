@extends('layouts.app')
@section('title', $request->exists ? 'Edit service request' : 'Log service request')

@section('content')
<p><a href="{{ route('service-requests.index') }}">&larr; Service Requests</a></p>
<h1>{{ $request->exists ? 'Edit service request' : 'Log a service request' }}</h1>

<form method="POST" action="{{ $request->exists ? route('service-requests.update', $request) : route('service-requests.store') }}" class="card">
    @csrf
    @if ($request->exists) @method('PUT') <input type="hidden" name="lock_version" value="{{ $request->lock_version }}"> @endif
    <div class="form-grid">
        <div class="full"><label>Summary *</label><input type="text" name="summary" value="{{ old('summary', $request->summary) }}" required></div>
        <div><label>Source *</label><select name="source">
            @foreach (\App\Enums\ServiceRequestSource::options() as $s)<option value="{{ $s['value'] }}" @selected(old('source', $request->source?->value)===$s['value'])>{{ $s['label'] }}</option>@endforeach
        </select></div>
        <div><label>Priority *</label><select name="priority">
            @foreach (\App\Enums\Priority::options() as $p)<option value="{{ $p['value'] }}" @selected(old('priority', $request->priority?->value)===$p['value'])>{{ $p['label'] }}</option>@endforeach
        </select></div>
        <div><label>Service line</label><select name="service_line_id"><option value="">—</option>
            @foreach ($serviceLines as $l)<option value="{{ $l->id }}" @selected((string)old('service_line_id',$request->service_line_id)===(string)$l->id)>{{ $l->name }}</option>@endforeach
        </select></div>
        <div><label>Owner</label><select name="owner_id"><option value="">—</option>
            @foreach ($owners as $o)<option value="{{ $o->id }}" @selected((string)old('owner_id',$request->owner_id)===(string)$o->id)>{{ $o->name }}</option>@endforeach
        </select></div>
        <div><label>Client</label><input type="text" name="client" value="{{ old('client', $request->client) }}"></div>
        <div><label>Contact name</label><input type="text" name="contact_name" value="{{ old('contact_name', $request->contact_name) }}"></div>
        <div><label>Contact email</label><input type="email" name="contact_email" value="{{ old('contact_email', $request->contact_email) }}"></div>
        <div><label>Contact phone</label><input type="text" name="contact_phone" value="{{ old('contact_phone', $request->contact_phone) }}"></div>
        <div><label>Estimated value</label><input type="number" step="0.01" name="estimated_value" value="{{ old('estimated_value', $request->estimated_value) }}"></div>
        <div><label>Currency</label><input type="text" name="currency" value="{{ old('currency', $request->currency) }}"></div>
        <div class="full"><label>Details</label><textarea name="details">{{ old('details', $request->details) }}</textarea></div>
    </div>
    <button type="submit" style="margin-top:1rem;">{{ $request->exists ? 'Save' : 'Log request' }}</button>
</form>
@endsection
