<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Inbound #{{ $inbound->id_inbound }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  @vite('resources/css/wms-inbound.css')
  <style>
    .meta-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; }
    .meta-grid dt { color:#6b7280 }
    .meta-grid dd { margin:0; font-weight:600 }
    .badge { padding:.25rem .5rem; border-radius:.375rem; font-size:.75rem; }
    .actions { display:flex; gap:8px; flex-wrap:wrap }
  </style>
</head>
<body class="dash-body">
<div class="dash-layout">
  <aside class="dash-sidebar">
    <div class="user-block">
      <div class="user-avatar">{{ strtoupper(mb_substr(auth()->user()->nama_pengguna ?? 'U',0,1,'UTF-8')) }}</div>
      <div class="user-meta">
        <div class="user-name">{{ auth()->user()->nama_pengguna ?? 'User' }}</div>
        <div class="user-email">{{ auth()->user()->email_pengguna ?? '' }}</div>
      </div>
    </div>
    <nav class="dash-menu">
      <a class="menu-item" href="{{ url('/wms/dashboard') }}">Dashboard</a>
      <a class="menu-item" href="{{ url('/wms/transaksi') }}">Transaksi</a>
      <a class="menu-item active" href="{{ url('/wms/inbound') }}">Inbound</a>
      <a class="menu-item" href="{{ url('/wms/stock') }}">Stock</a>
      <a class="menu-item" href="{{ route('wms.produk.index') }}">Produk</a>
      <a class="menu-item" href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>

      <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Detail Inbound #{{ $inbound->id_inbound }}</h1>
      <a class="btn" href="{{ route('wms.inbound.index') }}">&larr; Kembali</a>
    </header>

    @if(session('ok')) <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <div class="card" style="margin-bottom:16px">
      <div class="meta-grid">
        <dt>Status</dt>
        <dd><span class="badge badge-{{ $inbound->status }}">{{ strtoupper($inbound->status) }}</span></dd>

        <dt>Tanggal</dt>
        <dd>{{ optional($inbound->tanggal_inbound)->format('Y-m-d H:i') ?? '—' }}</dd>

        <dt>No Resi</dt>
        <dd>{{ $inbound->no_resi ?? '—' }}</dd>

        <dt>Total SKU</dt>
        <dd>{{ $inbound->total_barang ?? $inbound->details->count() }}</dd>

        <dt>Total Qty</dt>
        <dd>{{ $inbound->total_qty ?? $inbound->details->sum('qty') }}</dd>

        <dt>Deskripsi</dt>
        <dd>{{ $inbound->deskripsi ?: '—' }}</dd>
      </div>
    </div>

    <div class="card">
      <h3 style="margin:0 0 12px 0">Daftar Produk</h3>
      <div style="overflow-x:auto">
        <table class="table">
          <thead>
            <tr>
              <th style="width:64px">#</th>
              <th>Nama Produk</th>
              <th>SKU</th>
              <th style="width:140px; text-align:right">Qty</th>
            </tr>
          </thead>
          <tbody>
            @forelse($inbound->details as $i => $d)
              <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ optional($d->produk)->nama_produk ?? '— (produk dihapus)' }}</td>
                <td>{{ optional($d->produk)->sku ?? '—' }}</td>
                <td style="text-align:right">{{ $d->qty }}</td>
              </tr>
            @empty
              <tr><td colspan="4" style="text-align:center;color:#6b7280">Tidak ada detail.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
</body>
</html>
