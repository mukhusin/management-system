<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign in')</title>
    <style>
        body { font-family: -apple-system, Segoe UI, Roboto, sans-serif; background: #f7f8fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: #fff; border: 1px solid #e3e6ea; border-radius: 10px; padding: 2rem; width: 100%; max-width: 380px; }
        .box h1 { font-size: 1.25rem; margin-top: 0; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.25rem; color: #374151; }
        input { width: 100%; padding: 0.55rem; border: 1px solid #e3e6ea; border-radius: 6px; margin-bottom: 1rem; }
        button { width: 100%; padding: 0.6rem; background: #1d4ed8; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .error { color: #b91c1c; font-size: 0.85rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="box">
        @yield('content')
    </div>
</body>
</html>
