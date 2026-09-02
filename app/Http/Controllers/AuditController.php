<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with(['user', 'auditable'])
            ->when($request->input('event'), fn ($q, $e) => $q->where('event', $e))
            ->when($request->input('type'), fn ($q, $t) => $q->where('auditable_type', $t))
            ->when($request->input('user'), fn ($q, $u) => $q->where('user_id', $u))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'events' => AuditLog::query()->distinct()->orderBy('event')->pluck('event'),
            'types' => AuditLog::query()->distinct()->orderBy('auditable_type')->pluck('auditable_type'),
            'users' => User::orderBy('name')->get(),
            'filters' => $request->only(['event', 'type', 'user']),
        ]);
    }
}
