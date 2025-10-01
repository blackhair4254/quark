<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>OMS • Inbound</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-inbound.css')
  @vite('resources/css/wms-produk.css')
</head>
<body class="dash-body">
<div class="dash-layout">

  <aside class="dash-sidebar">
    <div class="user-block">
      <div class="user-avatar">{{ strtoupper(mb_substr(auth()->user()->nama_pengguna ?? 'O',0,1,'UTF-8')) }}</div>
      <div class="user-meta">
        <div class="user-name">{{ auth()->user()->nama_pengguna ?? 'OMS' }}</div>
        <div class="user-email">{{ auth()->user()->email_pengguna ?? '' }}</div>
      </div>
    </div>

    <nav class="dash-menu">
      <a class="menu-item active" href="{{ route('oms.inbound.index') }}">Inbound</a>
      <a class="menu-item" href="{{ route('oms.stock.index') }}">Stock</a>
      {{-- menu Transaksi (nanti) --}}
      <form method="POST" action="{{ route('oms.logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Inbound</h1>
      <div></div>
    </header>

    @if(session('ok')) <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    @php $tabs = ['all'=>'Semua','sent'=>'Dikirim','accept'=>'Diterima','confirm'=>'Dikonfirmasi','denied'=>'Ditolak']; @endphp
    <div class="tabs">
      @foreach($tabs as $key=>$label)
        <a class="tab {{ $tab===$key?'active':'' }}" href="{{ route('oms.inbound.index', ['tab'=>$key]) }}">
          {{ $label }}
        </a>
      @endforeach
    </div>

    <div class="card">
      <div style="overflow-x:auto">
        <table class="table">
          <thead>
            <tr>
              <th>ID Inbound</th>
              <th>Tanggal</th>
              <th>Total SKU</th>
              <th>Total Qty</th>
              <th>No Resi</th>
              <th>Status</th>
              <th style="width:260px">Aksi</th>
            </tr>
          </thead>
          <tbody>
          @forelse($items as $it)
            <tr>
              <td>#{{ $it->id_inbound }}</td>
              <td>{{ optional($it->tanggal_inbound)->format('Y-m-d H:i') }}</td>
              <td>{{ $it->total_sku ?? 0 }}</td>
              <td>{{ $it->total_qty ?? 0 }}</td>
              <td>{{ $it->no_resi ?? '—' }}</td>
              <td><span class="badge badge-{{ $it->status }}">{{ strtoupper($it->status) }}</span></td>
              <td>
                @if($it->status==='sent')
                    <form action="{{ route('oms.inbound.accept',$it) }}" method="POST" style="display:inline">
                        @csrf <button class="btn-sm">Terima</button>
                    </form>
                @elseif($it->status==='accept')
                    <form action="{{ route('oms.inbound.confirm',$it) }}" method="POST" style="display:inline">
                        @csrf <button class="btn-sm btn-primary">Konfirmasi</button>
                    </form>
                    <form action="{{ route('oms.inbound.deny',$it) }}" method="POST" style="display:inline">
                        @csrf <button class="btn-sm btn-danger">Tolak</button>
                    </form>
                @else
                -
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" style="text-align:center;color:#6b7280">Tidak ada data.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div style="margin-top:12px">{{ $items->links() }}</div>
  </main>
</div>
</body>
</html>
