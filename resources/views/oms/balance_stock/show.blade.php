<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>OMS • Detail Balance Stock</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="{{ asset('quark.svg') }}">
  <link rel="icon" type="image/x-icon" href="{{ asset('quark.svg') }}">
  <link rel="apple-touch-icon" href="{{ asset('quark.svg') }}">
  @vite('resources/css/wms-produk.css')
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
      <a class="menu-item" href="{{ route('oms.inbound.index') }}">Inbound</a>
      <a class="menu-item" href="{{ route('oms.stock.index') }}">Stock</a>
      <a class="menu-item" href="{{ route('oms.transaksi.index') }}">Transaksi OMS</a>
      <a class="menu-item active" href="{{ route('oms.balance_stock.index') }}">Balance Stock</a>
      <form method="POST" action="{{ route('oms.logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  {{-- MAIN --}}
  <main class="dash-main">
    <header class="main-header">
      <h1>Detail Balance Stock {{ $header->kode_adjustment }}</h1>
    </header>

    @if(session('ok'))  <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if(session('err')) <div class="alert-error">{{ session('err') }}</div> @endif

    <div class="card" style="margin-bottom:12px;">
      <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:4px; font-size:14px;">
        <div><strong>Gudang:</strong> {{ $header->gudang }}</div>
        <div><strong>Status:</strong> {{ strtoupper($header->status) }}</div>
        <div><strong>Dibuat:</strong> {{ $header->created_at }}</div>
        <div><strong>Diajukan oleh:</strong> {{ $header->creator->nama_pengguna ?? '-' }}</div>
        @if($header->approver)
          <div><strong>Di-approve oleh:</strong> {{ $header->approver->nama_pengguna }}</div>
        @endif
      </div>
    </div>

    <div class="card">
      <div style="overflow-x:auto;">
        <table class="table">
          <thead>
          <tr>
            <th>No</th>
            <th>SKU</th>
            <th>Nama Produk</th>
            <th>Stok Sistem</th>
            <th>Stok Fisik</th>
            <th>Selisih</th>
            <th>Tipe</th>
            <th>Keterangan</th>
          </tr>
          </thead>
          <tbody>
          @foreach($header->details as $i => $d)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $d->produk->sku ?? '-' }}</td>
              <td>{{ $d->produk->nama_produk ?? '-' }}</td>
              <td>{{ $d->qty_system }}</td>
              <td>{{ $d->qty_fisik }}</td>
              <td>{{ $d->selisih }}</td>
              <td>{{ $d->tipe_selisih }}</td>
              <td>{{ $d->keterangan }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div style="margin-top:12px; display:flex; gap:8px;">
      <a href="{{ route('oms.balance_stock.index') }}" class="btn-danger">Kembali</a>
      @if($header->status === 'submitted')
        <a href="{{ route('oms.balance_stock.edit',$header) }}" class="btn-primary">Edit</a>
      @endif
    </div>
  </main>
</div>
</body>
</html>
