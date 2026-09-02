<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\TrackerCategory;
use App\Enums\TrackerStatus;
use App\Models\ServiceLine;
use App\Models\TrackerItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TrackerItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:tracker.manage', except: ['index', 'show']),
        ];
    }

    public function index(Request $request)
    {
        $items = TrackerItem::query()
            ->search($request->input('q'))
            ->category($request->input('category'))
            ->status($request->input('status'))
            ->when($request->input('owner'), fn ($qq, $o) => $qq->where('owner_id', $o))
            ->with(['owner', 'serviceLine'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('tracker.index', [
            'items' => $items,
            'categories' => TrackerCategory::options(),
            'statuses' => TrackerStatus::options(),
            'owners' => User::orderBy('name')->get(),
            'filters' => $request->only(['q', 'category', 'status', 'owner']),
        ]);
    }

    public function show(TrackerItem $trackerItem)
    {
        $trackerItem->load(['owner', 'serviceLine', 'comments.user', 'comments.mentions', 'attachments.user', 'auditLogs.user']);

        return view('tracker.show', ['item' => $trackerItem]);
    }

    public function create()
    {
        return $this->form(new TrackerItem([
            'status' => TrackerStatus::NotStarted,
            'priority' => Priority::Medium,
            'entry_date' => now(),
        ]));
    }

    public function store(Request $request)
    {
        $item = TrackerItem::create($this->validateData($request));

        return redirect()->route('tracker.show', $item)->with('status', 'Tracker item added ('.$item->reference.').');
    }

    public function edit(TrackerItem $trackerItem)
    {
        return $this->form($trackerItem);
    }

    public function update(Request $request, TrackerItem $trackerItem)
    {
        $trackerItem->updateWithLock($this->validateData($request), (int) $request->integer('lock_version'));

        return redirect()->route('tracker.show', $trackerItem)->with('status', 'Tracker item updated.');
    }

    public function destroy(TrackerItem $trackerItem)
    {
        $trackerItem->delete();

        return redirect()->route('tracker.index')->with('status', 'Tracker item deleted.');
    }

    private function form(TrackerItem $item)
    {
        return view('tracker.form', [
            'item' => $item,
            'categories' => TrackerCategory::options(),
            'statuses' => TrackerStatus::options(),
            'serviceLines' => ServiceLine::ordered()->get(),
            'owners' => User::orderBy('name')->get(),
        ]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'in:'.implode(',', TrackerCategory::values())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'service_line_id' => ['nullable', 'exists:service_lines,id'],
            'status' => ['required', 'in:'.implode(',', TrackerStatus::values())],
            'priority' => ['required', 'in:'.implode(',', Priority::values())],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'entry_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);
    }
}
