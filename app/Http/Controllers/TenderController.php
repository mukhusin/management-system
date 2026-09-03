<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\TenderState;
use App\Models\ServiceLine;
use App\Models\Tender;
use App\Models\User;
use App\Services\ProjectInitiator;
use App\Services\TenderStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class TenderController extends Controller
{
    /**
     * The pipeline — tenders someone chose to pursue, tracked through their
     * lifecycle.
     */
    public function index(Request $request)
    {
        $tenders = Tender::query()
            ->adopted()
            ->search($request->input('q'))
            ->state($request->input('state'))
            ->ownedBy($request->input('owner'))
            ->when($request->boolean('open_only', false), fn ($q) => $q->open())
            ->with(['owners', 'serviceLine', 'project'])
            ->orderByRaw('deadline_date IS NULL, deadline_date asc')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('tenders.index', [
            'tenders' => $tenders,
            'owners' => User::orderBy('name')->get(),
            'states' => TenderState::options(),
            'filters' => $request->only(['q', 'state', 'owner', 'open_only']),
        ]);
    }

    /**
     * Opportunities — everything ingested from external sources that nobody
     * has picked up yet.
     */
    public function opportunities(Request $request)
    {
        // First visit with an empty feed: pull once so the page isn't blank.
        if (Tender::opportunities()->doesntExist() && Tender::query()->doesntExist()) {
            $this->runFetch();
        }

        $tenders = Tender::query()
            ->opportunities()
            ->search($request->input('q'))
            ->fromSource($request->input('source'))
            ->inCountry($request->input('country'))
            ->when($request->boolean('open_only', true), fn ($q) => $q->open())
            ->with('serviceLine')
            ->orderByRaw('deadline_date IS NULL, deadline_date asc')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('tenders.opportunities', [
            'tenders' => $tenders,
            'sources' => Tender::opportunities()->distinct()->orderBy('source')->pluck('source'),
            'countries' => Tender::opportunities()->whereNotNull('country')->distinct()->orderBy('country')->pluck('country')->take(80),
            'lastFetched' => Tender::opportunities()->max('updated_at'),
            'filters' => $request->only(['q', 'source', 'country', 'open_only']),
        ]);
    }

    public function fetch(Request $request)
    {
        $summary = $this->runFetch();

        return redirect()->route('opportunities.index')->with('status', $summary);
    }

    public function pursue(Request $request, Tender $tender)
    {
        abort_if($tender->isAdopted(), 409);

        $tender->adopt($request->user());

        return redirect()->route('tenders.show', $tender)
            ->with('status', 'Added to the pipeline — you can now track it through its lifecycle.');
    }

    public function show(Tender $tender)
    {
        $tender->load(['owners', 'adopter', 'serviceLine', 'project', 'comments.user', 'comments.mentions', 'attachments.user', 'auditLogs.user']);

        return view('tenders.show', [
            'tender' => $tender,
            'nextStates' => $tender->isAdopted() ? ($tender->state?->allowedNext() ?? []) : [],
        ]);
    }

    public function create()
    {
        return view('tenders.form', [
            'tender' => new Tender(['state' => TenderState::Draft, 'priority' => Priority::Medium]),
            'serviceLines' => ServiceLine::ordered()->get(),
            'owners' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['source'] = 'manual';
        $data['user_id'] = $request->user()->id;
        $data['external_id'] = ! empty($data['url']) ? sha1($data['url']) : (string) Str::uuid();
        $data['adopted_at'] = now();
        $data['adopted_by'] = $request->user()->id;

        $tender = Tender::create($data);
        $tender->syncOwners($request->input('owner_ids') ?: [$request->user()->id]);

        return redirect()->route('tenders.show', $tender)->with('status', 'Tender added to the pipeline.');
    }

    public function edit(Tender $tender)
    {
        return view('tenders.form', [
            'tender' => $tender,
            'serviceLines' => ServiceLine::ordered()->get(),
            'owners' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Tender $tender)
    {
        $data = $this->validateData($request);
        $before = $tender->getRawOriginal();

        if ($this->baselineChanged($tender, $data) && $request->user()->cannot('tenders.edit_baseline')) {
            return back()->withInput()->with('error', 'You are not allowed to change a tender\'s value or deadline.');
        }

        $tender->updateWithLock($data, (int) $request->integer('lock_version'));
        $tender->syncOwners($request->input('owner_ids', []));
        $tender->refresh()->auditBaselineChanges($before);

        return redirect()->route('tenders.show', $tender)->with('status', 'Tender updated.');
    }

    public function transition(Request $request, Tender $tender, TenderStateMachine $machine)
    {
        abort_unless($tender->isAdopted(), 409, 'Pursue this opportunity before moving it through the lifecycle.');

        $validated = $request->validate([
            'state' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $machine->apply($tender, TenderState::from($validated['state']), $request->user(), $validated['note'] ?? null);

        return redirect()->route('tenders.show', $tender)->with('status', 'Tender moved to '.TenderState::from($validated['state'])->label().'.');
    }

    public function promote(Tender $tender, ProjectInitiator $initiator)
    {
        $project = $initiator->fromTender($tender, request()->user());

        return redirect()->route('projects.show', $project)->with('status', 'Project initiated from tender.');
    }

    private function runFetch(): string
    {
        @set_time_limit(0);
        Artisan::call('tenders:fetch');

        $line = collect(explode("\n", Artisan::output()))
            ->map(trim(...))
            ->last(fn ($l) => str_starts_with($l, 'Done'));

        return $line ?: 'Opportunities refreshed from external sources.';
    }

    private function baselineChanged(Tender $tender, array $data): bool
    {
        foreach ($tender->baselineFields() as $field) {
            if (array_key_exists($field, $data)
                && (string) $data[$field] !== (string) $tender->getRawOriginal($field)) {
                return true;
            }
        }

        return false;
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope_statement' => ['nullable', 'string'],
            'client' => ['nullable', 'string', 'max:255'],
            'buyer' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:100'],
            'service_line_id' => ['nullable', 'exists:service_lines,id'],
            'owner_ids' => ['array'],
            'owner_ids.*' => ['integer', 'exists:users,id'],
            'priority' => ['nullable', 'string', 'in:'.implode(',', Priority::values())],
            'value' => ['nullable', 'numeric', 'min:0'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'published_date' => ['nullable', 'date'],
            'deadline_date' => ['nullable', 'date'],
            'url' => ['nullable', 'url', 'max:255'],
        ]);
    }
}
