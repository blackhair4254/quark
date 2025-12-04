<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>OMS • Detail Inbound #{{ $inbound->id_inbound }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="{{ asset('quark.svg') }}">
  <link rel="icon" type="image/x-icon" href="{{ asset('quark.svg') }}">
  <link rel="apple-touch-icon" href="{{ asset('quark.svg') }}">
  @vite('resources/css/wms-produk.css')
  @vite('resources/css/wms-inbound.css')
  <style>
    .meta-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; }
    .meta-grid dt { color:#6b7280 }
    .meta-grid dd { margin:0; font-weight:600 }
    .badge { padding:.25rem .5rem; border-radius:.375rem; font-size:.75rem; }
    .badge-draft   { background:#e5e7eb; color:#111827; }
    .badge-sent    { background:#dbeafe; color:#1d4ed8; }
    .badge-accept  { background:#fef3c7; color:#92400e; }
    .badge-confirm { background:#dcfce7; color:#166534; }
    .badge-denied  { background:#fee2e2; color:#b91c1c; }
    .actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; align-items:center; }
    textarea[name="note"]{width:100%;min-height:80px;margin-top:6px;font-size:13px;}
  </style>
</head>
<body class="dash-body">
<div class="dash-layout">
  {{-- SIDEBAR OMS --}}
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
      <a class="menu-item" href="{{ url('/oms/dashboard') }}">Dashboard</a>
      <a class="menu-item" href="{{ url('/oms/transaksi') }}">Transaksi</a>
      <a class="menu-item active" href="{{ route('oms.inbound.index') }}">Inbound</a>
      {{-- tambahkan menu lain OMS kalau ada --}}

      <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  {{-- MAIN --}}
  <main class="dash-main">
    <header class="main-header">
      <h1>Detail Inbound #{{ $inbound->id_inbound }}</h1>
      <a class="btn" href="{{ route('oms.inbound.index') }}">&larr; Kembali</a>
    </header>

    @if(session('ok'))
      <div class="alert-ok">{{ session('ok') }}</div>
    @endif
    @if($errors->any())
      <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    {{-- META HEADER --}}
    <div class="card" style="margin-bottom:16px">
      <div class="meta-grid">
        <dt>Status</dt>
        <dd>
          <span class="badge badge-{{ $inbound->status }}">
            {{ strtoupper($inbound->status) }}
          </span>
        </dd>

        <dt>Tanggal</dt>
        <dd>{{ optional($inbound->tanggal_inbound)->format('Y-m-d H:i') ?? '—' }}</dd>

        <dt>No Resi</dt>
        <dd>{{ $inbound->no_resi ?? '—' }}</dd>

        <dt>Total SKU</dt>
        <dd>{{ $inbound->total_barang ?? $inbound->details->count() }}</dd>

        <dt>Total Qty</dt>
        <dd>{{ $inbound->total_qty ?? $inbound->details->sum('qty') }}</dd>

        <dt>Deskripsi</dt>
        <dd style="white-space:pre-line">{{ $inbound->deskripsi ?: '—' }}</dd>
      </div>
    </div>

    {{-- DETAIL PRODUK --}}
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
            <tr>
              <td colspan="4" style="text-align:center;color:#6b7280">
                Tidak ada detail.
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- AKSI OMS --}}
    <div class="card" style="margin-top:16px">
      <div class="actions">
        <a class="btn" href="{{ route('oms.inbound.index') }}">&larr; Kembali</a>

        {{-- sent -> OMS bisa ACCEPT --}}
        @if($inbound->status === 'sent')
          <form method="POST" action="{{ route('oms.inbound.accept', $inbound) }}"
                onsubmit="return confirm('Terima inbound ini? Status akan menjadi ACCEPT.')">
            @csrf
            <button class="btn-primary" type="submit">Terima (ACCEPT)</button>
          </form>
        @endif

        {{-- accept -> OMS bisa CONFIRM (tambah stok) atau DENY --}}
        @if($inbound->status === 'accept')
          <form method="POST" action="{{ route('oms.inbound.confirm', $inbound) }}"
                onsubmit="return confirm('Konfirmasi inbound ini dan tambahkan stok?')">
            @csrf
            <button class="btn-primary" type="submit">Konfirmasi & Tambah Stok</button>
          </form>

          <form method="POST" action="{{ route('oms.inbound.deny', $inbound) }}"
                onsubmit="return confirm('Tandai inbound ini sebagai DENIED?')"
                style="flex:1 1 100%;max-width:480px">
            @csrf
            <label style="font-size:13px;color:#374151;display:block;margin-bottom:4px">
              Alasan Deny (opsional)
            </label>
            <textarea name="note" placeholder="Tulis alasan penolakan, jika ada..."></textarea>
            <button class="btn-danger" type="submit" style="margin-top:6px">Tolak (DENY)</button>
          </form>
        @endif
      </div>
    </div>

  </main>
</div>
</body>
</html>
