<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · @yield('title')</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 24px; background: #f4f6f9; color: #1f2937;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .card {
            width: 100%; max-width: 460px; background: #fff; border-radius: 14px; padding: 32px 28px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08); text-align: center;
        }
        .code { font-size: 56px; font-weight: 800; color: #407bff; line-height: 1; margin: 0 0 8px; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        p { margin: 0 0 22px; color: #4b5563; line-height: 1.55; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a.button {
            display: inline-block; padding: 10px 18px; border-radius: 999px; text-decoration: none; font-weight: 600;
            background: #407bff; color: #fff;
        }
        a.button.secondary { background: #eef2ff; color: #1e3a8a; }
    </style>
</head>
<body>
    <main class="card">
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a class="button secondary" href="javascript:history.back()">Kembali</a>
            <a class="button" href="{{ url('/dashboard') }}">Ke Beranda</a>
        </div>
    </main>
</body>
</html>
