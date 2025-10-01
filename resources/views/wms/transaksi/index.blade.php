<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>WMS • Transaksi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  <style>
    /* ===== Tabs ===== */
    .tabs-wrap{margin-bottom:12px}
    .tabs-scroll{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;-webkit-overflow-scrolling:touch}
    .tab{
      display:inline-flex;align-items:center;gap:8px;
      padding:8px 12px;border-radius:999px;
      background:#f1f5f9;color:#0f172a;font-weight:600;white-space:nowrap;
      border:1px solid #e5e7eb; transition:.15s;
    }
    .tab:hover{background:#e5e7eb}
    .tab.active{background:#111827;color:#fff;border-color:#111827}
    .tab .dot{width:8px;height:8px;border-radius:999px;background:#cbd5e1;flex:none}
    .tab .count{
      padding:2px 8px;border-radius:999px;
      background:rgba(255,255,255,.22); /* aktif */
      font-size:12px;font-weight:700;flex:none
    }
    .tab:not(.active) .count{background:#fff}

    /* HAPUS underline & warna visited khusus untuk tab */
    .tabs-scroll .tab,
    .tabs-scroll .tab:link,
    .tabs-scroll .tab:visited,
    .tabs-scroll .tab:hover,
    .tabs-scroll .tab:focus,
    .tabs-scroll .tab:active{
      text-decoration:none !important;
    }

    /* warna titik status */
    .dot.ready{background:#60a5fa}        /* biru muda */
    .dot.processing{background:#f59e0b}   /* amber */
    .dot.shipped{background:#a78bfa}      /* purple */
    .dot.done{background:#22c55e}         /* green */
    .dot.cancel{background:#ef4444}       /* red */
    .dot.new{background:#94a3b8}          /* slate */

    /* ===== Toolbar di atas tabel ===== */
    .list-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px}
    .list-meta{color:#64748b;font-size:12px}
    .right-tools{display:flex;gap:8px;align-items:center}
    .btn-ghost{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;color:#0f172a}

    /* ===== Tabel ===== */
    .table-wrap{overflow-x:auto}
    .table thead th{position:sticky;top:0;background:#f8fafc;z-index:1}
    .table tr:hover{background:#f9fafb}
    .num{ text-align:right; font-variant-numeric:tabular-nums; }
    .table.dense thead th,.table.dense td{padding:6px 8px}
    .table.dense{font-size:13px}

    /* badge status */
    .badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700;background:#e5e7eb;color:#111827}
    .badge.new{background:#e5e7eb}
    .badge.ready{background:#dbeafe}
    .badge.processing{background:#fef3c7}
    .badge.shipped{background:#ddd6fe}
    .badge.done{background:#dcfce7}
    .badge.cancel{background:#fee2e2}

    /* Responsif: sembunyikan kolom kurang penting di layar kecil */
    @media (max-width: 860px){
      .col-log,.col-resi{display:none}
    }
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
      <a class="menu-item" href="{{ route('wms.dashboard') }}">Dashboard</a>
      <a class="menu-item active" href="{{ route('wms.transaksi.index') }}">Transaksi</a>
      <a class="menu-item" href="{{ route('wms.inbound.index') }}">Inbound</a>
      <a class="menu-item" href="{{ url('/wms/stock') }}">Stock</a>
      <a class="menu-item" href="{{ route('wms.produk.index') }}">Produk</a>
      <a class="menu-item" href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>
      <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
        @csrf <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Transaksi</h1>
      <a href="{{ route('wms.transaksi.create') }}" class="btn-primary">+ Tambah Transaksi</a>
    </header>

    @if(session('ok')) <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    {{-- Tabs (scrollable pills) --}}
    <div class="tabs-wrap">
      <div class="tabs-scroll">
        @foreach($tabs as $key=>$label)
          @php
            $cnt = isset($counts) ? ($counts[$key] ?? null) : null;
          @endphp
          <a class="tab {{ $tab===$key?'active':'' }}" href="{{ route('wms.transaksi.index', ['tab'=>$key]) }}">
            <span class="dot {{ $key }}"></span>
            <span>{{ $label }}</span>
            @if(!is_null($cnt)) <span class="count">{{ number_format($cnt,0,',','.') }}</span> @endif
          </a>
        @endforeach
      </div>
    </div>

    <div class="card">
      {{-- toolbar kecil --}}
      <div class="list-toolbar">
        <div class="list-meta">
          Total: <strong>{{ number_format($items->total(),0,',','.') }}</strong> transaksi
          • Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}
        </div>
        <div class="right-tools">
          <button id="densityBtn" class="btn-ghost" type="button">Mode Rapat</button>
        </div>
      </div>

      <div class="table-wrap">
        <table id="trxTable" class="table">
          <thead>
            <tr>
              <th>No Invoice</th>
              <th>Tgl Transaksi</th>
              <th>Pengirim</th>
              <th class="num">Nilai Total</th>
              <th>Penerima</th>
              <th class="col-log">Jenis Logistik</th>
              <th class="col-resi">No Resi</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          @forelse($items as $it)
            <tr>
              <td>
                <a class="link" href="{{ route('wms.transaksi.show', $it->id_transaksi) }}">
                  {{ $it->invoice }}
                </a>
              </td>
              <td>{{ \Carbon\Carbon::parse($it->tanggal)->format('Y-m-d') }}</td>
              <td>{{ $it->pengirim }}</td>
              <td class="num">Rp {{ number_format($it->total_nilai,0,',','.') }}</td>
              <td>{{ $it->nama_penerima }}</td>
              <td class="col-log">{{ $it->jenis_logistik ?: '—' }}</td>
              <td class="col-resi">{{ $it->no_resi ?: '—' }}</td>
              <td><span class="badge {{ $it->status }}">{{ strtoupper($it->status) }}</span></td>
            </tr>
          @empty
            <tr><td colspan="8" style="text-align:center;color:#6b7280">Tidak ada transaksi.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div style="margin-top:12px">{{ $items->links() }}</div>
  </main>
</div>

<script>
  // Toggle mode rapat (densify table)
  const btn = document.getElementById('densityBtn');
  const table = document.getElementById('trxTable');
  btn?.addEventListener('click', ()=>{
    table.classList.toggle('dense');
    btn.textContent = table.classList.contains('dense') ? 'Mode Normal' : 'Mode Rapat';
  });
</script>
</body>
</html>
