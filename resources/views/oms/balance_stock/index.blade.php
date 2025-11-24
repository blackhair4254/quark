<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>OMS • Balance Stock</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  <style>
    html, body, .dash-layout, .dash-main { overflow-x: hidden; }

    /* ===== Tabs (sama seperti transaksi/index) ===== */
    .tabs-wrap{margin-bottom:12px}
    .tabs-scroll{
      display:flex;gap:8px;overflow-x:auto;
      padding-bottom:4px;-webkit-overflow-scrolling:touch
    }
    .tab{
      display:inline-flex;align-items:center;gap:8px;
      padding:8px 12px;border-radius:999px;
      background:#f1f5f9;color:#0f172a;font-weight:600;white-space:nowrap;
      border:1px solid #e5e7eb;transition:.15s;
      text-decoration:none !important;
    }
    .tab:hover{background:#e5e7eb}
    .tab.active{
      background:#111827;color:#fff;border-color:#111827;
    }
    .tab .dot{
      width:8px;height:8px;border-radius:999px;
      background:#cbd5e1;flex:none;
    }
    .tabs-scroll .tab,
    .tabs-scroll .tab:link,
    .tabs-scroll .tab:visited,
    .tabs-scroll .tab:hover,
    .tabs-scroll .tab:focus,
    .tabs-scroll .tab:active{
      text-decoration:none !important;
    }

    /* Warna dot untuk status balance stock
       (pakai palette yang sama dengan transaksi: kuning/green/merah/dsb) */
    .dot.all       { background:#94a3b8; } /* mirip 'new' / netral */
    .dot.submitted { background:#f59e0b; } /* mirip 'processing' */
    .dot.approved  { background:#22c55e; } /* mirip 'done' */
    .dot.rejected  { background:#ef4444; } /* mirip 'cancel' */
    
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
      <h1>Balance Stock (OMS)</h1>
      <div style="display:flex; gap:8px;">
        <a href="{{ route('oms.balance_stock.create') }}" class="btn-primary">+ Ajukan Balance Stock</a>
      </div>
    </header>

    @if(session('ok'))
      <div class="alert-ok">{{ session('ok') }}</div>
    @endif
    @if(session('err'))
      <div class="alert-error">{{ session('err') }}</div>
    @endif

    {{-- Tabs status (DESAIN SAMA DENGAN TRANSAKSI) --}}
    <div class="tabs-wrap">
      <div class="tabs-scroll">
        @foreach($statuses as $s)
          @php $isActive = ($tab === $s); @endphp
          <a href="{{ route('oms.balance_stock.index',['status'=>$s]) }}"
             class="tab {{ $isActive ? 'active' : '' }}">
            <span class="dot {{ $s }}"></span>
            <span>{{ strtoupper($s) }}</span>
          </a>
        @endforeach
      </div>
    </div>

    <div class="card">
      <div style="overflow-x:auto;">
        <table class="table">
          <thead>
          <tr>
            <th>Kode</th>
            <th>Gudang</th>
            <th>Status</th>
            <th>Dibuat</th>
            <th>Diajukan Oleh</th>
            <th>Aksi</th>
          </tr>
          </thead>
          <tbody>
          @forelse($list as $h)
            <tr>
              <td>{{ $h->kode_adjustment }}</td>
              <td>{{ $h->gudang }}</td>
              <td>{{ strtoupper($h->status) }}</td>
              <td>{{ $h->created_at }}</td>
              <td>{{ $h->creator->nama_pengguna ?? '-' }}</td>
              <td>
                <a class="btn-sm" href="{{ route('oms.balance_stock.show',$h) }}">Detail</a>
                @if($h->status === 'submitted')
                  <a class="btn-sm" href="{{ route('oms.balance_stock.edit',$h) }}">Edit</a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="text-align:center;color:#6b7280;">
                Belum ada data.
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div style="margin-top:12px">
      {{ $list->links() }}
    </div>
  </main>
</div>
</body>
</html>
