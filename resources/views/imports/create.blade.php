@extends('layouts.app')
@section('title', 'Import tracker CSV')

@section('content')
<p><a href="{{ route('tracker.index') }}">&larr; Tracker</a></p>
<h1>Import the Master Business Tracker</h1>

<div class="card">
    <p class="muted">
        Expected columns: <code>ID, Date, Category, Title, Description, Responsible Person,
        Priority, Status, Progress %, Next Action, Due Date, Remarks / Outcome</code>.
        Rows are matched by <code>ID</code> (e.g. <code>EMREC-001</code>) so re-importing updates
        rather than duplicates. Blank-title rows are skipped; <code>Unspecified</code> dates become empty.
    </p>
    <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".csv,text/csv" required>
        <button type="submit">Import</button>
    </form>
</div>
@endsection
