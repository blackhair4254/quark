<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>OMS • Stock</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
    <style>
        html, body, .dash-layout, .dash-main { overflow-x: hidden; }

        .stock-toolbar { display:flex; gap:12px; align-items:center; }
        .stock-toolbar .btn { height:36px }


        .table-scroll{
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain; 
        }

        
        .table.stock-table{
            font-size:14px; border-spacing:0;
            table-layout:auto;
            width:max-content;      
            min-width:100%;         
        }

        .table.stock-table thead th{
            position:sticky; top:0;
            background:#f8fafc; color:#64748b;
            font-size:12.5px; font-weight:700; letter-spacing:.02em;
            border-bottom:1px solid #e5e7eb;
            padding:8px 10px; z-index:1;
            white-space:nowrap;
        }
        .table.stock-table td{
            padding:8px 10px; vertical-align:middle;
            border-bottom:1px solid #f1f5f9;
            white-space:nowrap;
        }
        .table.stock-table tbody tr:hover{ background:#f9fafb; }

        
        .table.stock-table .col-foto{ width:200px; min-width:64px; }
        .prod-thumb{ width:10vw; height:auto; border-radius:10px; object-fit:cover; background:#e5e7eb; }

        
        .desc-cell{
            /* kunci kolom deskripsi tetap “jangkar” lebar */
            min-width: 10vw;
            max-width: 20vw;

            /* biar membungkus ke bawah */
            white-space: normal !important;
            overflow: visible;
            overflow-wrap: anywhere;     /* patahkan kata panjang */
            word-break: break-word;      /* fallback */

            /* batalkan efek clamp sebelumnya */
            display: table-cell;         /* reset dari display:-webkit-box */
            -webkit-line-clamp: unset;
            -webkit-box-orient: unset;

            line-height: 1.4;
            vertical-align: top;         /* teks mulai dari atas sel */
        }

        
        .num{ text-align:right; font-variant-numeric:tabular-nums; }

        
        .table.stock-table.dense thead th,
        .table.stock-table.dense td{ padding:6px 8px; }
        .table.stock-table.dense{ font-size:13px; }
    </style>


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
      <a class="menu-item" href="{{ route('oms.inbound.index') }}">Inbound</a>
      <a class="menu-item active" href="{{ route('oms.stock.index') }}">Stock</a>
      {{-- Transaksi nanti --}}
      <form method="POST" action="{{ route('oms.logout') }}" class="logout-form">
        @csrf <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Stock</h1>
      <div></div>
    </header>

    @if(session('ok'))  <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <form method="GET" class="filters" style="margin-bottom:12px">
      <button class="btn" style="height:100%;">
        <img src="{{ asset('images/search.svg') }}" alt="search" class="search">
      </button>
      <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / SKU / kategori…" style="width:300px">
    </form>

    <div class="card">
      <div class="table-scroll">
        <table class="table stock-table dense">
            <thead>
                <tr>
                <th class="col-foto">Foto</th>
                <th>Nama Produk</th>
                <th>SKU</th>
                <th>Kategori</th>
                <th class="num">Qty</th>
                <th>Berat (gram)</th>
                <th>Dimensi (P×L×T cm)</th>
                <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $p)
                @php
                $kategori = $p->kategori ?? $p->category ?? '—';
                $desc     = $p->deskripsi ?? $p->deskripsi_produk ?? $p->keterangan ?? null;
                $berat    = $p->berat ?? $p->berat_gram ?? null;
                $panjang  = $p->panjang ?? $p->panjang_cm ?? $p->dim_p ?? null;
                $lebar    = $p->lebar   ?? $p->lebar_cm   ?? $p->dim_l ?? null;
                $tinggi   = $p->tinggi  ?? $p->tinggi_cm  ?? $p->dim_t ?? null;
                $dimensi  = ($panjang||$lebar||$tinggi) ? ( ($panjang ?? '—').'×'.($lebar ?? '—').'×'.($tinggi ?? '—') ) : '—';
                $qty      = number_format($p->stock->qty ?? 0);
                @endphp
                <tr>
                    <td class="col-foto">
                        @if($p->foto)
                        <img class="prod-thumb" src="{{ asset('storage/'.$p->foto) }}" alt="">
                        @else
                        <div class="prod-thumb"></div>
                        @endif
                    </td>
                    <td>{{ $p->nama_produk }}</td>
                    <td>{{ $p->sku ?? '—' }}</td>
                    <td>{{ $kategori }}</td>
                    <td class="num">{{ $qty }}</td>
                    <td class="num">{{ $berat ?? '—' }}</td>
                    <td>{{ $dimensi }}</td>
                    <td class="desc-cell" title="{{ $desc }}">{{ $desc ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#6b7280">Tidak ada data.</td></tr>
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
<script>
  // Geser tabel hanya saat benar-benar ada input horizontal (trackpad atau Shift+scroll)
  document.querySelectorAll('.table-scroll').forEach(sc => {
    sc.addEventListener('wheel', (e) => {
      // Deteksi niat horizontal:
      // - Trackpad: |deltaX| >= |deltaY|
      // - Mouse dengan Shift: gunakan deltaY sebagai horizontal
      const horizontalDelta = e.deltaX || (e.shiftKey ? e.deltaY : 0);
      const isMostlyHorizontal = Math.abs(e.deltaX) >= Math.abs(e.deltaY) || e.shiftKey;

      if (!isMostlyHorizontal || horizontalDelta === 0) {
        // Biarkan vertikal menerus ke halaman
        return;
      }

      // Coba geser horizontal tabel
      const before = sc.scrollLeft;
      sc.scrollLeft += horizontalDelta;

      // Prevent default HANYA kalau kita memang menggeser (tidak mentok)
      if (sc.scrollLeft !== before) {
        e.preventDefault();
      }
      // Jika mentok kiri/kanan, jangan prevent → event boleh dipakai halaman
    }, { passive: false });
  });
</script>
