@extends('layouts.app')

@section('title', 'Add an Opportunity')

@section('content')
    <p><a href="{{ route('tenders.index') }}">&larr; Back to all opportunities</a></p>

    <div class="card">
        <h1>Add an opportunity manually</h1>
        <p class="meta">
            Use this for anything you find outside the automated sources —
            a LinkedIn post, a WhatsApp forward, a newsletter, etc.
        </p>

        @if ($errors->any())
            <div class="card" style="border-color:#b91c1c;">
                <ul style="margin:0; padding-left:1.2rem; color:#b91c1c;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tenders.store') }}">
            @csrf

            <div style="margin-bottom:1rem;">
                <label>Title *</label><br>
                <input type="text" name="title" value="{{ old('title') }}" required style="width:100%; padding:0.5rem;">
            </div>

            <div style="margin-bottom:1rem;">
                <label>Link (e.g. the LinkedIn post URL)</label><br>
                <input type="url" name="url" value="{{ old('url') }}" placeholder="https://www.linkedin.com/posts/..." style="width:100%; padding:0.5rem;">
            </div>

            <div style="margin-bottom:1rem;">
                <label>Description / notes</label><br>
                <textarea name="description" rows="4" style="width:100%; padding:0.5rem;">{{ old('description') }}</textarea>
            </div>

            <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
                <div>
                    <label>Buyer / Donor / Organization</label><br>
                    <input type="text" name="buyer" value="{{ old('buyer') }}">
                </div>
                <div>
                    <label>Country</label><br>
                    <input type="text" name="country" value="{{ old('country') }}">
                </div>
                <div>
                    <label>Sector</label><br>
                    <input type="text" name="sector" value="{{ old('sector') }}">
                </div>
            </div>

            <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
                <div>
                    <label>Published date</label><br>
                    <input type="date" name="published_date" value="{{ old('published_date') }}">
                </div>
                <div>
                    <label>Deadline</label><br>
                    <input type="date" name="deadline_date" value="{{ old('deadline_date') }}">
                </div>
                <div>
                    <label>Value</label><br>
                    <input type="number" step="0.01" name="value" value="{{ old('value') }}">
                </div>
                <div>
                    <label>Currency</label><br>
                    <input type="text" name="currency" value="{{ old('currency') }}" placeholder="USD / TZS">
                </div>
            </div>

            <button type="submit">Save opportunity</button>
        </form>
    </div>
@endsection
