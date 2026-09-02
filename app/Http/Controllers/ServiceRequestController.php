<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\ServiceRequestSource;
use App\Enums\ServiceRequestState;
use App\Models\ServiceLine;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\ProjectInitiator;
use App\Services\ServiceRequestStateMachine;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = ServiceRequest::query()
            ->search($request->input('q'))
            ->state($request->input('state'))
            ->when($request->input('service_line'), fn ($q, $s) => $q->where('service_line_id', $s))
            ->when($request->input('owner'), fn ($q, $o) => $q->where('owner_id', $o))
            ->with(['owner', 'serviceLine'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('service_requests.index', [
            'requests' => $requests,
            'states' => ServiceRequestState::options(),
            'serviceLines' => ServiceLine::ordered()->get(),
            'owners' => User::orderBy('name')->get(),
            'filters' => $request->only(['q', 'state', 'service_line', 'owner']),
        ]);
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['owner', 'serviceLine', 'project', 'comments.user', 'comments.mentions', 'attachments.user', 'auditLogs.user']);

        return view('service_requests.show', [
            'request' => $serviceRequest,
            'nextStates' => $serviceRequest->state?->allowedNext() ?? [],
        ]);
    }

    public function create()
    {
        $this->authorize('service_requests.create');

        return view('service_requests.form', [
            'request' => new ServiceRequest(['state' => ServiceRequestState::New, 'priority' => Priority::Medium, 'source' => ServiceRequestSource::Website]),
            'serviceLines' => ServiceLine::ordered()->get(),
            'owners' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('service_requests.create');

        $serviceRequest = ServiceRequest::create($this->validateData($request));

        return redirect()->route('service-requests.show', $serviceRequest)->with('status', 'Service request logged.');
    }

    public function edit(ServiceRequest $serviceRequest)
    {
        $this->authorize('service_requests.edit');

        return view('service_requests.form', [
            'request' => $serviceRequest,
            'serviceLines' => ServiceLine::ordered()->get(),
            'owners' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $this->authorize('service_requests.edit');

        $serviceRequest->updateWithLock($this->validateData($request), (int) $request->integer('lock_version'));

        return redirect()->route('service-requests.show', $serviceRequest)->with('status', 'Service request updated.');
    }

    public function destroy(ServiceRequest $serviceRequest)
    {
        $this->authorize('service_requests.edit');
        $serviceRequest->delete();

        return redirect()->route('service-requests.index')->with('status', 'Service request deleted.');
    }

    public function transition(Request $request, ServiceRequest $serviceRequest, ServiceRequestStateMachine $machine)
    {
        $data = $request->validate([
            'state' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $machine->apply($serviceRequest, ServiceRequestState::from($data['state']), $request->user(), $data['note'] ?? null);

        return back()->with('status', 'Service request moved to '.ServiceRequestState::from($data['state'])->label().'.');
    }

    public function promote(ServiceRequest $serviceRequest, ProjectInitiator $initiator)
    {
        $project = $initiator->fromServiceRequest($serviceRequest, request()->user());

        return redirect()->route('projects.show', $project)->with('status', 'Project initiated from service request.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'source' => ['required', 'in:'.implode(',', ServiceRequestSource::values())],
            'priority' => ['required', 'in:'.implode(',', Priority::values())],
            'service_line_id' => ['nullable', 'exists:service_lines,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'client' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'summary' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
        ]);
    }
}
