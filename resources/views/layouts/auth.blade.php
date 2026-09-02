<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign in') &mdash; EMREC TPMS</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=3">
    <style>
        body { display: grid; grid-template-columns: 1fr 460px; min-height: 100vh; }
        .auth-hero {
            position: relative; color: #fff; padding: 3rem;
            display: flex; flex-direction: column; justify-content: flex-end;
            background: #0f1729 url('{{ asset('about-archi.webp') }}') center / cover no-repeat;
        }
        .auth-hero::before { content: ""; position: absolute; inset: 0; background: linear-gradient(160deg, rgba(15,23,41,.55), rgba(15,23,41,.9)); }
        .auth-hero > * { position: relative; }
        .auth-hero .mark {
            width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center;
            background: linear-gradient(135deg, #6366f1, #4f46e5); font-weight: 800; margin-bottom: 1.25rem;
        }
        .auth-hero h2 { font-size: 1.5rem; margin: 0 0 .5rem; color: #fff; }
        .auth-hero p { color: #b7bdca; max-width: 30rem; margin: 0; }
        .auth-panel { display: flex; align-items: center; justify-content: center; padding: 2rem; background: var(--surface); }
        .auth-card { width: 100%; max-width: 340px; }
        .auth-card .brand { font-size: .72rem; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); font-weight: 700; }
        .auth-card h1 { font-size: 1.35rem; margin: .35rem 0 1.5rem; }
        .auth-card label { margin-top: .9rem; }
        .auth-card button { width: 100%; justify-content: center; margin-top: 1.4rem; padding: .65rem; }
        .auth-card .error { background: var(--c-red-bg); color: var(--c-red); border: 1px solid #f3c2c2; border-radius: var(--radius-sm); padding: .6rem .75rem; font-size: .85rem; margin-bottom: 1rem; }
        @media (max-width: 820px) {
            body { grid-template-columns: 1fr; }
            .auth-hero { display: none; }
        }
    </style>
</head>
<body>
    <div class="auth-hero">
        <div class="mark">E</div>
        <h2>Tender &amp; Project Management</h2>
        <p>Track opportunities from pipeline to delivery — one place for tenders, service requests, projects and the team's work.</p>
    </div>
    <div class="auth-panel">
        <div class="auth-card">
            <div class="brand">EMREC TPMS</div>
            @yield('content')
        </div>
    </div>
</body>
</html>
