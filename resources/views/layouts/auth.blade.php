<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign in') &mdash; EMREC TPMS</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, Segoe UI, Roboto, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #0f172a url('{{ asset('about-archi.webp') }}') center / cover no-repeat;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: linear-gradient(120deg, rgba(15,23,42,0.85), rgba(15,23,42,0.55));
        }
        .box {
            position: relative;
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid #e3e6ea;
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 45px -15px rgba(0, 0, 0, 0.5);
        }
        .box .brand { font-size: 0.8rem; letter-spacing: 0.08em; text-transform: uppercase; color: #6b7280; margin-bottom: 0.35rem; }
        .box h1 { font-size: 1.25rem; margin: 0 0 1rem; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.25rem; color: #374151; }
        input { width: 100%; padding: 0.55rem; border: 1px solid #e3e6ea; border-radius: 6px; margin-bottom: 1rem; }
        button { width: 100%; padding: 0.6rem; background: #1d4ed8; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        button:hover { background: #1e40af; }
        .error { color: #b91c1c; font-size: 0.85rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="box">
        <div class="brand">EMREC TPMS</div>
        @yield('content')
    </div>
</body>
</html>
