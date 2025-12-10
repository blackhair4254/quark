<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Log Cron</title>
</head>
<body>
    <h1>Daftar Log Cron</h1>

    <ul>
        @foreach($files as $key => $file)
            <li>
                <a href="{{ route('wms.logs.show', $key) }}">
                    {{ $file }}
                </a>
            </li>
        @endforeach
    </ul>
</body>
</html>
