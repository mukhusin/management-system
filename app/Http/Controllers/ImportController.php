<?php

namespace App\Http\Controllers;

use App\Services\Import\TrackerCsvImporter;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function create()
    {
        return view('imports.create');
    }

    public function store(Request $request, TrackerCsvImporter $importer)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $summary = $importer->import($request->file('file')->getRealPath())->summary();

        $message = "Imported: {$summary['created']} new, {$summary['updated']} updated, {$summary['skipped']} skipped.";
        if ($summary['unmatched_people'] !== []) {
            $message .= ' Unmatched people: '.implode('; ', $summary['unmatched_people']).'.';
        }

        return redirect()->route('tracker.index')->with('status', $message);
    }
}
