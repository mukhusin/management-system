<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Tender & Aid Opportunities')</title>
    <style>
        :root { --border: #e3e6ea; --muted: #6b7280; --accent: #1d4ed8; }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; background: #f7f8fa; color: #1a1a1a; }
        header { background: #111827; color: #fff; padding: 1rem 2rem; }
        header a { color: #fff; text-decoration: none; font-weight: 600; }
        main { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem; margin-bottom: 1rem; }
        .filters { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: end; }
        .filters label { display: block; font-size: 0.8rem; color: var(--muted); margin-bottom: 0.25rem; }
        .filters input, .filters select { padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px; min-width: 160px; }
        .filters button { padding: 0.55rem 1rem; background: var(--accent); color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        .tender-title { font-weight: 600; font-size: 1.05rem; }
        .meta { color: var(--muted); font-size: 0.85rem; margin-top: 0.25rem; }
        .badge { display: inline-block; background: #eef2ff; color: var(--accent); padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.75rem; margin-right: 0.4rem; }
        .deadline-soon { color: #b91c1c; font-weight: 600; }
        .pagination { margin-top: 1rem; }
        .pager { display: flex; flex-wrap: wrap; gap: 0.35rem; }
        .pager-link { display: inline-block; padding: 0.4rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; background: #fff; color: var(--accent); text-decoration: none; font-size: 0.9rem; line-height: 1; }
        .pager-link:hover { background: #eef2ff; }
        .pager-link.is-current { background: var(--accent); border-color: var(--accent); color: #fff; font-weight: 600; }
        .pager-link.is-disabled { color: var(--muted); background: #f3f4f6; cursor: default; }
        .pager-summary { color: var(--muted); font-size: 0.8rem; margin: 0.5rem 0 0; }
    </style>
</head>
<body>
<header style="display:flex; justify-content:space-between; align-items:center;">
    <a href="{{ route('tenders.index') }}">Tender &amp; Development-Aid Dashboard</a>
    <div style="display:flex; align-items:center; gap:0.75rem;">
        <a href="{{ route('tenders.create') }}" style="background:#1d4ed8; color:#fff; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none;">+ Add opportunity</a>
        @auth
            <span style="color:#cbd5e1; font-size:0.9rem;">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" style="background:none; border:1px solid #4b5563; color:#fff; padding:0.35rem 0.7rem; border-radius:6px; cursor:pointer;">Logout</button>
            </form>
        @endauth
    </div>
</header>
<main>
    @if (session('status'))
        <div class="card" style="border-color:#15803d; color:#15803d;">{{ session('status') }}</div>
    @endif
    @yield('content')
</main>
</body>
</html>
