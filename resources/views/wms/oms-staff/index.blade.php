<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>WMS • Staff OMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
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
      <a class="menu-item" href="{{ url('/wms/inbound') }}">Inbound</a>
      <a class="menu-item" href="{{ url('/wms/stock') }}">Stock</a>
      <a class="menu-item" href="{{ route('wms.produk.index') }}">Produk</a>
      <a class="menu-item active" href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>

      <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Staff OMS</h1>
      <div style="display:flex; gap:8px;">
        <a href="{{ route('wms.oms-staff.create') }}" class="btn-primary">+ Tambah Staff OMS</a>
      </div>
    </header>

    @if(session('ok')) <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <form method="GET" class="filters" style="margin-bottom:12px; display:flex; gap:8px;">
      <button class="btn" style="height:100%;">
        <img src="{{ asset('images/search.svg') }}" alt="search" class="search">
      </button>
      <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / email…" style="width:260px">
      @if($q)<a class="btn" href="{{ route('wms.oms-staff.index') }}">Reset</a>@endif
    </form>

    <div class="card">
      <div style="overflow-x:auto">
        <table class="table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Chain</th>
              <th>Role</th>
              <th style="width:160px">Aksi</th>
            </tr>
          </thead>
          <tbody>
          @forelse($items as $u)
            <tr>
              <td>{{ $u->nama_pengguna }}</td>
              <td>{{ $u->email_pengguna }}</td>
              <td>{{ $u->chain_link }}</td>
              <td><span class="badge">OMS</span></td>
              <td>
                <form action="{{ route('wms.oms-staff.destroy',$u) }}" method="POST" style="display:inline"
                      onsubmit="return confirm('Hapus staff ini?')">
                  @csrf @method('DELETE')
                  <button class="btn-sm btn-danger" type="submit">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" style="text-align:center;color:#6b7280">Belum ada staff OMS.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div style="margin-top:12px">
      {{ $items->links() }}
    </div>
  </main>
</div>
</body>
</html>
