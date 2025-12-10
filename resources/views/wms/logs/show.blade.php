<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Log: {{ $filename }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --bg: #050816;
            --bg-elevated: #0b1020;
            --accent: #4ea3ff;
            --accent-soft: rgba(78, 163, 255, 0.15);
            --text-main: #f5f5f5;
            --text-muted: #a0a0a0;
            --border-subtle: #22263a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #1b2b4b 0, #050816 45%, #02030a 100%);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: stretch;
            padding: 16px;
        }

        .page {
            width: 100%;
            max-width: 1200px;
            background: linear-gradient(135deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow:
                0 20px 40px rgba(0, 0, 0, 0.7),
                0 0 0 1px rgba(255, 255, 255, 0.02);
            padding: 18px 16px 70px;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 768px) {
            .page {
                padding: 22px 24px 80px;
                border-radius: 22px;
            }
        }

        .top-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px 16px;
            margin-bottom: 12px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            font-size: 13px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.35);
            color: var(--text-muted);
            text-decoration: none;
            transition: 0.18s ease;
        }

        .back-link:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(15, 76, 129, 0.4);
        }

        .back-link span.icon {
            font-size: 14px;
        }

        .title-wrap {
            flex: 1;
            min-width: 200px;
        }

        h1 {
            font-size: 18px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pill {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.45);
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
            word-break: break-all;
        }

        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .btn {
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(0,0,0,0.4);
            color: var(--text-main);
            font-size: 12px;
            padding: 7px 11px;
            border-radius: 999px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: 0.18s ease;
            white-space: nowrap;
        }

        .btn:hover {
            border-color: var(--accent);
            background: rgba(30, 64, 175, 0.65);
        }

        .btn-outline {
            background: transparent;
        }

        .badge-soft {
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
        }

        .log-wrapper {
            margin-top: 14px;
            border-radius: 14px;
            border: 1px solid var(--border-subtle);
            background: radial-gradient(circle at top left, rgba(78,163,255,0.15), transparent 55%) #020513;
            overflow: hidden;
        }

        .log-header {
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: linear-gradient(90deg, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.6));
            font-size: 12px;
        }

        .log-header-left {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            align-items: center;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.7);
        }

        .log-header-right {
            display: flex;
            gap: 6px;
        }

        .log-container {
            max-height: 70vh;
            min-height: 300px;
            padding: 10px 12px 12px;
            overflow: auto;
        }

        pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: "JetBrains Mono", SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12px;
            line-height: 1.5;
        }

        /* floating scroll buttons */
        .float-scroll {
            position: fixed;
            right: 16px;
            bottom: 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 40;
        }

        @media (min-width: 768px) {
            .float-scroll {
                right: 28px;
                bottom: 26px;
            }
        }

        .float-scroll button {
            border-radius: 999px;
            padding: 7px 11px;
            border: 1px solid rgba(148, 163, 184, 0.8);
            background: radial-gradient(circle at top left, rgba(148,163,184,0.35), rgba(15,23,42,0.95));
            color: #e5e7eb;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.75);
            backdrop-filter: blur(10px);
            transition: 0.18s ease transform, 0.18s ease box-shadow, 0.18s ease background;
        }

        .float-scroll button span.icon {
            font-size: 14px;
        }

        .float-scroll button:hover {
            transform: translateY(-1px);
            background: radial-gradient(circle at top left, rgba(59,130,246,0.6), rgba(15,23,42,0.95));
            box-shadow: 0 16px 35px rgba(30,64,175,0.8);
        }

        .float-scroll button:active {
            transform: translateY(1px) scale(0.98);
            box-shadow: 0 6px 18px rgba(15,23,42,0.9);
        }

    </style>
</head>
<body>

<div class="page">
    <div class="top-bar">
        <a href="{{ route('wms.logs.index') }}" class="back-link">
            <span class="icon">←</span>
            <span>Kembali</span>
        </a>

        <div class="title-wrap">
            <h1>
                {{ $filename }}
                <span class="pill">Log File</span>
            </h1>
            <div class="meta">
                Path: {{ $fullPath }}
            </div>
        </div>

        <div class="controls">
            <button type="button" class="btn btn-outline" id="btnScrollLogTop">
                <span class="icon">⬆</span> Log ke atas
            </button>
            <button type="button" class="btn" id="btnScrollLogBottom">
                <span class="icon">⬇</span> Log ke bawah
            </button>
        </div>
    </div>

    <div class="log-wrapper">
        <div class="log-header">
            <div class="log-header-left">
                <div class="dot"></div>
                <span>Output terbaru di bawah</span>
                <span class="badge-soft">Live log view</span>
            </div>
            <div class="log-header-right">
                <button type="button" class="btn btn-outline" id="btnReload">
                    🔄 Reload
                </button>
            </div>
        </div>

        <div class="log-container" id="logContainer">
            <pre>{{ $content }}</pre>
        </div>
    </div>
</div>

{{-- Tombol global scroll halaman --}}
<div class="float-scroll">
    <button type="button" id="btnPageTop">
        <span class="icon">⇧</span> Halaman atas
    </button>
    <button type="button" id="btnPageBottom">
        <span class="icon">⇩</span> Halaman bawah
    </button>
</div>

<script>
    (function () {
        const logContainer    = document.getElementById('logContainer');
        const btnScrollLogTop = document.getElementById('btnScrollLogTop');
        const btnScrollLogBottom = document.getElementById('btnScrollLogBottom');
        const btnPageTop      = document.getElementById('btnPageTop');
        const btnPageBottom   = document.getElementById('btnPageBottom');
        const btnReload       = document.getElementById('btnReload');

        // Scroll log ke atas/bawah (di dalam box log)
        function logToTop() {
            if (!logContainer) return;
            logContainer.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function logToBottom() {
            if (!logContainer) return;
            logContainer.scrollTo({ top: logContainer.scrollHeight, behavior: 'smooth' });
        }

        // Scroll seluruh halaman ke atas/bawah
        function pageToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function pageToBottom() {
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }

        if (btnScrollLogTop) {
            btnScrollLogTop.addEventListener('click', logToTop);
        }
        if (btnScrollLogBottom) {
            btnScrollLogBottom.addEventListener('click', logToBottom);
        }
        if (btnPageTop) {
            btnPageTop.addEventListener('click', pageToTop);
        }
        if (btnPageBottom) {
            btnPageBottom.addEventListener('click', pageToBottom);
        }

        if (btnReload) {
            btnReload.addEventListener('click', function () {
                // reload halaman untuk ambil log terbaru
                window.location.reload();
            });
        }

        // Auto scroll ke bawah saat pertama dibuka, supaya langsung lihat log terbaru
        window.addEventListener('load', function () {
            logToBottom();
        });
    })();
</script>
</body>
</html>
