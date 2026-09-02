<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use Illuminate\Http\Request;

class TenderController extends Controller
{
    public function index(Request $request)
    {
        $query = Tender::query()
            ->search($request->input('q'))
            ->fromSource($request->input('source'))
            ->inCountry($request->input('country'));

        if ($request->boolean('open_only', true)) {
            $query->open();
        }

        $tenders = $query
            ->with('user')
            ->orderByRaw('deadline_date IS NULL, deadline_date asc')
            ->orderByDesc('id') // stable tiebreaker so rows don't shuffle between pages
            ->paginate(20)
            ->withQueryString();

        $sources = Tender::query()->distinct()->pluck('source');
        $countries = Tender::query()->whereNotNull('country')->distinct()->pluck('country')->take(50);

        return view('tenders.index', [
            'tenders' => $tenders,
            'sources' => $sources,
            'countries' => $countries,
            'filters' => $request->only(['q', 'source', 'country', 'open_only']),
        ]);
    }

    public function show(Tender $tender)
    {
        return view('tenders.show', ['tender' => $tender]);
    }

    public function create()
    {
        return view('tenders.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:100'],
            'buyer' => ['nullable', 'string', 'max:255'],
            'value' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:8'],
            'published_date' => ['nullable', 'date'],
            'deadline_date' => ['nullable', 'date'],
            'url' => ['nullable', 'url', 'max:255'],
        ]);

        $data['source'] = 'manual';
        $data['user_id'] = auth()->id();
        // Manually added items don't have a natural external id from an
        // API, so derive a stable one from the URL if given, otherwise
        // just generate one — it only needs to be unique within 'manual'.
        $data['external_id'] = $data['url'] ? md5($data['url']) : (string) \Illuminate\Support\Str::uuid();

        $tender = Tender::updateOrCreate(
            ['source' => 'manual', 'external_id' => $data['external_id']],
            $data
        );

        return redirect()->route('tenders.show', $tender)->with('status', 'Opportunity added.');
    }
}
