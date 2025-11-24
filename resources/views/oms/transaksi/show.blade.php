<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>OMS • Detail Transaksi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  <style>
    html, body, .dash-layout, .dash-main { overflow-x:hidden; }

    .badge{display:inline-block;padding:4px 8px;border-radius:999px;
           font-size:12px;font-weight:700;background:#e5e7eb;color:#111827}
    .badge.new{background:#e5e7eb}
    .badge.ready{background:#dbeafe}
    .badge.processing{background:#fef3c7}
    .badge.shipped{background:#ddd6fe}
    .badge.done{background:#dcfce7}
    .badge.cancel{background:#fee2e2}

    .pill{display:inline-flex;align-items:center;gap:6px;
          padding:4px 10px;border-radius:999px;background:#f3f4f6;font-size:12px}

    .meta-grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:8px 24px;
      font-size:13px;
    }
    .meta-label{color:#6b7280;font-size:12px}
    .meta-value{color:#111827;font-weight:500}

    @media(max-width:768px){
      .meta-grid{grid-template-columns:1fr}
    }

    .card-section-title{
      font-size:14px;font-weight:700;color:#111827;margin-bottom:6px;
    }

    /* tabel detail barang */
    .table-scroll{
      width:100%;
      overflow-x:auto;
      -webkit-overflow-scrolling:touch;
      overscroll-behavior-x:contain;
      margin-top:8px;
    }
    .table.detail-table{
      font-size:14px;border-spacing:0;
      table-layout:auto;
      width:max-content;
      min-width:100%;
    }
    .table.detail-table thead th{
      position:sticky;top:0;background:#f8fafc;color:#64748b;
      font-size:12.5px;font-weight:700;letter-spacing:.02em;
      border-bottom:1px solid #e5e7eb;
      padding:8px 10px;z-index:1;white-space:nowrap;
    }
    .table.detail-table td{
      padding:8px 10px;vertical-align:middle;
      border-bottom:1px solid #f1f5f9;white-space:nowrap;
    }
    .table.detail-table tbody tr:hover{background:#f9fafb;}
    .num{text-align:right;font-variant-numeric:tabular-nums;}

    .actions-row{
      display:flex;flex-wrap:wrap;gap:8px;
      margin-top:12px;
    }
    .btn-secondary{
      border-radius:10px;
      padding:8px 14px;
      font-size:13px;
      border:1px solid #e5e7eb;
      cursor:pointer;
    }
    .btn-secondary:hover{background:#e5e7eb;}

    .btn-secondary.shipped{ background: #ddd6fe;}
    .btn-secondary.processing{background: #fef3c7;}
    .btn-secondary.done{background:#dcfce7 ;}

    .btn-link{
      font-size:13px;
      color:#2563eb;
      text-decoration:none;
    }
    .btn-link:hover{text-decoration:underline;}
    .back-link{
      font-size:13px;
      color:#4b5563;
      text-decoration:none;
      display:inline-flex;align-items:center;gap:4px;
      margin-bottom:10px;
    }
    .back-link:hover{color:#111827;}
  </style>
</head>
<body class="dash-body">
<div class="dash-layout">

  {{-- Sidebar sama seperti index --}}
  <aside class="dash-sidebar">
    <div class="user-block">
      <div class="user-avatar">{{ strtoupper(mb_substr(auth()->user()->nama_pengguna ?? 'U',0,1,'UTF-8')) }}</div>
      <div class="user-meta">
        <div class="user-name">{{ auth()->user()->nama_pengguna ?? 'User' }}</div>
        <div class="user-email">{{ auth()->user()->email_pengguna ?? '' }}</div>
      </div>
    </div>

    <nav class="dash-menu">
      <a class="menu-item" href="{{ route('oms.inbound.index') }}">Inbound</a>
      <a class="menu-item" href="{{ route('oms.stock.index') }}">Stock</a>
      <a class="menu-item active" href="{{ route('oms.transaksi.index') }}">Transaksi OMS</a>
      <a class="menu-item" href="{{ route('oms.balance_stock.index') }}">Balance Stock</a>
      <form method="POST" action="{{ route('oms.logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Detail Transaksi</h1>
      <div></div>
    </header>

    @if(session('ok')) <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if(session('err')) <div class="alert-error">{{ session('err') }}</div> @endif
    @if($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <a href="{{ route('oms.transaksi.index', ['tab' => $transaksi->status ?? 'all']) }}" class="back-link">
      ← Kembali ke daftar
    </a>

    <div class="card" style="margin-bottom:16px">
      {{-- header info --}}
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:8px">
        <div>
          <div style="font-size:13px;color:#6b7280">No Invoice / Order</div>
          <div style="font-size:18px;font-weight:700">
            {{ $transaksi->invoice ?? ($transaksi->order_sn ?? '—') }}
          </div>
          <div style="margin-top:4px;font-size:12px;color:#6b7280">
            ID Transaksi: #{{ $transaksi->id_transaksi }}
          </div>
        </div>
        <div style="text-align:right">
          <div class="badge {{ $transaksi->status ?? 'new' }}">{{ strtoupper($transaksi->status ?? 'NEW') }}</div>
          <div style="margin-top:6px;font-size:12px;color:#6b7280">
            Tanggal: {{ \Carbon\Carbon::parse($transaksi->tanggal ?? $transaksi->created_at)->format('Y-m-d') }}
          </div>
        </div>
      </div>

      {{-- meta grid --}}
      <div class="meta-grid" style="margin-top:8px">
        <div>
          <div class="meta-label">Pengirim</div>
          <div class="meta-value">{{ $transaksi->pengirim ?? '—' }}</div>
        </div>
        <div>
          <div class="meta-label">No. Telp Pengirim</div>
          <div class="meta-value">{{ $transaksi->no_telp_pengirim ?? '—' }}</div>
        </div>

        <div>
          <div class="meta-label">Penerima</div>
          <div class="meta-value">{{ $transaksi->nama_penerima ?? '—' }}</div>
        </div>
        <div>
          <div class="meta-label">No. Telp Penerima</div>
          <div class="meta-value">{{ $transaksi->no_telp_penerima ?? '—' }}</div>
        </div>

        <div>
          <div class="meta-label">Jenis Logistik</div>
          <div class="meta-value">{{ $transaksi->jenis_logistik ?? '—' }}</div>
        </div>
        <div>
          <div class="meta-label">No Resi</div>
          <div class="meta-value">{{ $transaksi->no_resi ?? '—' }}</div>
        </div>

        <div style="grid-column:1/-1">
          <div class="meta-label">Alamat Penerima</div>
          <div class="meta-value">{{ $transaksi->alamat_penerima ?? '—' }}</div>
        </div>
      </div>

    </div>

    {{-- Detail barang --}}
    <div class="card">
      <div class="card-section-title">Daftar Item</div>
      <div class="table-scroll">
        <table class="table detail-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Produk</th>
              <th>SKU</th>
              <th class="num">Qty</th>
            </tr>
          </thead>
          <tbody>
          @forelse($details as $i => $d)
            <tr>
              <td class="num">{{ $i+1 }}</td>
              <td>{{ $d->nama_produk }}</td>
              <td>{{ $d->produk->sku ?? '—' }}</td>
              <td class="num">{{ number_format($d->qty,0,',','.') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" style="text-align:center;color:#6b7280">Tidak ada item.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;align-items:center;">
      {{-- tombol aksi status --}}
      @if($canAct)
        <div class="actions-row">
          @if($transaksi->status === 'ready')
            <form method="POST" action="{{ route('oms.transaksi.to-processing', $transaksi) }}">
              @csrf
              <button type="submit" class="btn-secondary processing">
                To PROCESSING
              </button>
            </form>
          @endif

          @if($transaksi->status === 'processing')
            <form method="POST" action="{{ route('oms.transaksi.to-shipped', $transaksi) }}">
              @csrf
              <button type="submit" class="btn-secondary shipped">
                To SHIPPED
              </button>
            </form>

            <form method="GET" action="{{ route('oms.transaksi.print-resi', $transaksi) }}">
              <button type="submit" class="btn-primary">
                Cetak Resi
              </button>
            </form>
          @endif

          @if($transaksi->status === 'shipped')
            <form method="POST" action="{{ route('oms.transaksi.to-done', $transaksi) }}">
              @csrf
              <button type="submit" class="btn-secondary done">
                To DONE
              </button>
            </form>

            <form method="GET" action="{{ route('oms.transaksi.print-resi', $transaksi) }}">
              <button type="submit" class="btn-primary">
                Cetak Resi
              </button>
            </form>
          @endif
        </div>
      @else
        <p style="margin-top:10px;font-size:13px;color:#6b7280">
          Transaksi berstatus <strong>NEW</strong>. Anda hanya dapat melihat detail, tidak dapat mengubah status.
        </p>
      @endif
    </div>

  </main>
</div>
</body>
</html>
