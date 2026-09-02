<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TPMS') &mdash; EMREC</title>
    <style>
        :root {
            --border:#e3e6ea; --muted:#6b7280; --accent:#1d4ed8; --ink:#1a1a1a;
            --bg:#f7f8fa; --card:#fff;
            --c-gray:#6b7280; --c-blue:#1d4ed8; --c-green:#15803d; --c-red:#b91c1c;
            --c-amber:#b45309; --c-purple:#7c3aed;
        }
        * { box-sizing:border-box; }
        body { font-family:-apple-system,Segoe UI,Roboto,sans-serif; margin:0; background:var(--bg); color:var(--ink); }
        a { color:var(--accent); }
        header { background:#111827; color:#fff; padding:0.75rem 1.5rem; display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap; }
        header .brand { color:#fff; text-decoration:none; font-weight:700; }
        nav.main { display:flex; gap:0.25rem; flex-wrap:wrap; flex:1; }
        nav.main a { color:#cbd5e1; text-decoration:none; font-size:0.9rem; padding:0.35rem 0.6rem; border-radius:6px; }
        nav.main a:hover { background:#1f2937; color:#fff; }
        nav.main a.active { background:#1f2937; color:#fff; }
        header .right { display:flex; align-items:center; gap:0.75rem; }
        header .right .who { color:#cbd5e1; font-size:0.85rem; }
        header .bell { color:#fff; text-decoration:none; font-size:1rem; position:relative; }
        header .bell .count { position:absolute; top:-6px; right:-10px; background:var(--c-red); color:#fff; font-size:0.65rem; border-radius:999px; padding:0 4px; }
        main { max-width:1140px; margin:1.5rem auto; padding:0 1rem; }
        h1 { font-size:1.4rem; margin:0 0 1rem; }
        h2 { font-size:1.1rem; margin:1.5rem 0 0.75rem; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:1.25rem; margin-bottom:1rem; }
        .muted { color:var(--muted); font-size:0.85rem; }
        .meta { color:var(--muted); font-size:0.85rem; margin-top:0.25rem; }
        .row { display:flex; gap:1rem; flex-wrap:wrap; }
        .flash-ok { border-color:var(--c-green); color:var(--c-green); }
        .flash-err { border-color:var(--c-red); color:var(--c-red); }

        .filters { display:flex; gap:0.75rem; flex-wrap:wrap; align-items:end; }
        .filters label, .form-grid label { display:block; font-size:0.8rem; color:var(--muted); margin-bottom:0.25rem; }
        .filters input, .filters select, .form-grid input, .form-grid select, .form-grid textarea {
            padding:0.5rem; border:1px solid var(--border); border-radius:6px; min-width:160px; font:inherit;
        }
        .form-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:1rem; }
        .form-grid .full { grid-column:1/-1; }
        .form-grid textarea { width:100%; min-height:80px; }
        button, .btn { padding:0.5rem 0.9rem; background:var(--accent); color:#fff; border:none; border-radius:6px; cursor:pointer; font:inherit; text-decoration:none; display:inline-block; }
        button.ghost, .btn.ghost { background:#fff; color:var(--accent); border:1px solid var(--border); }
        button.danger, .btn.danger { background:var(--c-red); }
        button.small, .btn.small { padding:0.3rem 0.55rem; font-size:0.8rem; }

        table.grid { width:100%; border-collapse:collapse; }
        table.grid th, table.grid td { text-align:left; padding:0.55rem 0.6rem; border-bottom:1px solid var(--border); font-size:0.9rem; vertical-align:top; }
        table.grid th { color:var(--muted); font-weight:600; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.03em; }
        table.grid tr:hover td { background:#fafbfc; }

        .badge { display:inline-block; padding:0.12rem 0.5rem; border-radius:999px; font-size:0.72rem; font-weight:600; background:#eef2ff; color:var(--accent); white-space:nowrap; }
        .badge--gray { background:#f1f2f4; color:var(--c-gray); }
        .badge--blue { background:#e6effe; color:var(--c-blue); }
        .badge--green { background:#e5f4ea; color:var(--c-green); }
        .badge--red { background:#fdeaea; color:var(--c-red); }
        .badge--amber { background:#fdf1e3; color:var(--c-amber); }
        .badge--purple { background:#f1e9fd; color:var(--c-purple); }

        .progress { background:#eef0f3; border-radius:999px; height:10px; overflow:hidden; min-width:80px; }
        .progress > span { display:block; height:100%; background:var(--c-green); }
        .due-soon { color:var(--c-red); font-weight:600; }

        .kpi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:1rem; }
        .kpi { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:1rem; }
        .kpi .n { font-size:1.6rem; font-weight:700; }
        .kpi .l { color:var(--muted); font-size:0.8rem; }

        .bar-row { display:flex; align-items:center; gap:0.6rem; margin:0.3rem 0; font-size:0.85rem; }
        .bar-row .bar { background:var(--accent); height:14px; border-radius:3px; }
        .bar-row .lab { width:150px; color:var(--muted); }

        .thread { list-style:none; padding:0; margin:0; }
        .thread li { border-top:1px solid var(--border); padding:0.7rem 0; }
        .thread .by { font-weight:600; font-size:0.85rem; }
        .thread .at { color:var(--muted); font-size:0.75rem; }
        .thread .mention, .mdw-preview .mention { background:#eef2ff; color:var(--accent); border-radius:4px; padding:0 3px; }
        .cover { display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; }
        .scope-uncovered { color:var(--c-red); }
        .chip { display:inline-block; background:#f1f2f4; color:var(--muted); border-radius:4px; padding:0 5px; font-size:0.7rem; }

        .pagination { margin-top:1rem; }
        .pager { display:flex; flex-wrap:wrap; gap:0.35rem; }
        .pager-link { display:inline-block; padding:0.4rem 0.7rem; border:1px solid var(--border); border-radius:6px; background:#fff; color:var(--accent); text-decoration:none; font-size:0.9rem; line-height:1; }
        .pager-link:hover { background:#eef2ff; }
        .pager-link.is-current { background:var(--accent); border-color:var(--accent); color:#fff; font-weight:600; }
        .pager-link.is-disabled { color:var(--muted); background:#f3f4f6; cursor:default; }
        .pager-summary { color:var(--muted); font-size:0.8rem; margin:0.5rem 0 0; }

        .steps { display:flex; gap:0.4rem; flex-wrap:wrap; margin:0.5rem 0 1rem; }
        .steps .step { padding:0.3rem 0.7rem; border-radius:6px; background:#f1f2f4; color:var(--muted); font-size:0.8rem; }
        .steps .step.done { background:#e5f4ea; color:var(--c-green); }
        .steps .step.current { background:var(--accent); color:#fff; }

        .tree { margin-left:0; }
        .tree ul { list-style:none; padding-left:1.2rem; border-left:2px solid var(--border); margin:0.3rem 0; }
    </style>
    @stack('head')
</head>
<body>
@auth
@php($u = auth()->user())
<header>
    <a href="{{ route('dashboard') }}" class="brand">EMREC&nbsp;TPMS</a>
    <nav class="main">
        <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Dashboard</a>
        <a href="{{ route('tenders.index') }}" @class(['active' => request()->routeIs('tenders.*')])>Tenders</a>
        <a href="{{ route('service-requests.index') }}" @class(['active' => request()->routeIs('service-requests.*')])>Service Requests</a>
        <a href="{{ route('projects.index') }}" @class(['active' => request()->routeIs('projects.*','milestones.*','feature-sets.*','tasks.*')])>Projects</a>
        <a href="{{ route('tasks.mine') }}" @class(['active' => request()->routeIs('tasks.mine')])>My Work</a>
        <a href="{{ route('tracker.index') }}" @class(['active' => request()->routeIs('tracker.*')])>Tracker</a>
        <a href="{{ route('reports.index') }}" @class(['active' => request()->routeIs('reports.*')])>Reports</a>
        @can('audit.view')
            <a href="{{ route('audit.index') }}" @class(['active' => request()->routeIs('audit.*')])>Audit</a>
        @endcan
        @can('users.manage')
            <a href="{{ route('users.index') }}" @class(['active' => request()->routeIs('users.*','service-lines.*')])>Team</a>
        @endcan
    </nav>
    <div class="right">
        <a href="{{ route('notifications.index') }}" class="bell" title="Notifications">
            &#128276;
            @if ($u->unreadNotifications()->count())
                <span class="count">{{ $u->unreadNotifications()->count() }}</span>
            @endif
        </a>
        <span class="who">{{ $u->name }} &middot; {{ $u->role?->label() }}</span>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="ghost small">Logout</button>
        </form>
    </div>
</header>
@endauth
<main>
    @if (session('status'))
        <div class="card flash-ok">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="card flash-err">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="card flash-err">
            <ul style="margin:0; padding-left:1.2rem;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</main>
@stack('foot')
</body>
</html>
