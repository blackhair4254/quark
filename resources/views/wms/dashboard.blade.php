{{-- resources/views/wms/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>WMS • Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @vite('resources/css/wms-dashboard.css')
</head>
<body class="dash-body">

<div class="dash-layout">
    {{-- SIDEBAR --}}
    <aside class="dash-sidebar">
        <div class="user-block">
            <div class="user-avatar">
                {{ strtoupper(mb_substr(auth()->user()->nama_pengguna ?? 'U',0,1,'UTF-8')) }}
            </div>
            <div class="user-meta">
                <div class="user-name">{{ auth()->user()->nama_pengguna ?? 'User' }}</div>
                <div class="user-email">{{ auth()->user()->email_pengguna ?? '' }}</div>
            </div>
        </div>

        <nav class="dash-menu">
            <a class="menu-item active" href="{{ url('/wms/dashboard') }}">Dashboard</a>
            <a class="menu-item" href="{{ url('/wms/transaksi') }}">Transaksi</a>
            <a class="menu-item" href="{{ url('/wms/inbound') }}">Inbound</a>
            <a class="menu-item" href="{{ url('/wms/stock') }}">Stock</a>
            <a class="menu-item" href="{{ url('/wms/produk') }}">Produk</a>
            <a class="menu-item" href="{{ route('wms.toko.edit') }}">Atur Toko</a>
            <a class="menu-item" href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>
            


            <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="menu-item logout">Logout</button>
            </form>
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="dash-main">
        <header class="main-header">
            <h1>Dashboard</h1>
            <div class="chain">{{ auth()->user()->chain_link ?? '' }}</div>
        </header>

        {{-- FILTER & RINGKASAN --}}
        <section class="report-filters">
            <form method="GET" action="{{ url('/wms/dashboard') }}" class="filters">
                <label>
                    Produk (nama / SKU)
                    <input type="text" name="q" placeholder="Cari produk…" value="{{ request('q') }}">
                </label>
                <label>
                    Bulan
                    <input type="month" name="bulan" value="{{ request('bulan', now()->format('Y-m')) }}">
                </label>
                <button class="btn">Terapkan</button>
            </form>
        </section>

        <section class="report-summary">
            <div class="card kpi">
                <div class="kpi-title">Total Penjualan</div>
                <div class="kpi-value">{{ number_format($totalQty ?? 0) }} pcs</div>
                <div class="kpi-sub">
                    Produk: {{ $produkNama ?? '—' }}
                    • Bulan:
                    {{ \Carbon\Carbon::parse((request('bulan') ?? now()->format('Y-m')).'-01')->translatedFormat('F Y') }}
                </div>
            </div>
        </section>

        {{-- TABEL HARIAN --}}
        @if(!empty($harian))
        <section class="report-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Qty (pcs)</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($harian as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row['tanggal'])->format('d-m-Y') }}</td>
                        <td>{{ number_format($row['qty']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
        @endif

        {{-- FOOTER KECIL DI DASHBOARD (opsional) --}}
        <footer class="dash-footer">
            <span>Hak Cipta © 2025 PT Quark Neural Partikel</span>
        </footer>
    </main>
</div>

</body>
</html>
