<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Log: {{ $filename }}</title>
    <style>
        body { font-family: monospace; background:#111; color:#eee; }
        pre  { white-space: pre-wrap; word-wrap: break-word; }
        a    { color: #4ea3ff; }
    </style>
</head>
<body>
    <p>
        <a href="{{ route('wms.logs.index') }}">← Kembali ke daftar log</a>
    </p>

    <h2>{{ $filename }}</h2>
    <p><small>Path: {{ $fullPath }}</small></p>

    <hr>

    <pre>{{ $content }}</pre>
</body>
</html>
