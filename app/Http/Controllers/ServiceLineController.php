<?php

namespace App\Http\Controllers;

use App\Models\ServiceLine;
use Illuminate\Http\Request;

class ServiceLineController extends Controller
{
    public function index()
    {
        return view('service_lines.index', [
            'lines' => ServiceLine::ordered()->withCount(['tenders', 'serviceRequests', 'projects'])->get(),
        ]);
    }

    public function create()
    {
        return view('service_lines.form', ['line' => new ServiceLine(['active' => true])]);
    }

    public function store(Request $request)
    {
        ServiceLine::create($this->validateData($request));

        return redirect()->route('service-lines.index')->with('status', 'Service line added.');
    }

    public function edit(ServiceLine $serviceLine)
    {
        return view('service_lines.form', ['line' => $serviceLine]);
    }

    public function update(Request $request, ServiceLine $serviceLine)
    {
        $serviceLine->update($this->validateData($request));

        return redirect()->route('service-lines.index')->with('status', 'Service line updated.');
    }

    public function destroy(ServiceLine $serviceLine)
    {
        $serviceLine->update(['active' => false]);

        return redirect()->route('service-lines.index')->with('status', 'Service line deactivated.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]) + ['active' => $request->boolean('active')];
    }
}
