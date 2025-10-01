<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>WMS • Detail Transaksi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  <style>
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:900px){.grid-2{grid-template-columns:1fr}}
    .row{display:flex;gap:8px}
    .lbl{color:#64748b;width:160px}
    .val{color:#0f172a;font-weight:600}
    .badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700;background:#e5e7eb;color:#111827}
    .badge.ready{background:#dbeafe}.badge.processing{background:#fef3c7}
    .badge.shipped{background:#ddd6fe}.badge.done{background:#dcfce7}.badge.cancel{background:#fee2e2}
    .num{ text-align:right; font-variant-numeric:tabular-nums; }

    /* ===== Modal ===== */
    .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.55);z-index:50;padding:16px}
    .modal.open{display:flex}
    .modal-card{width:100%;max-width:980px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.25)}
    .modal-head{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #e5e7eb}
    .modal-title{font-weight:800}
    /* Modal full height agar viewport bisa scroll */
    .modal-card{ width:186mm; display:flex; flex-direction:column; }

    /* Area viewer yang bisa digeser/di-scroll */
    .preview-viewport{
      flex:1 1 auto;
      overflow:auto;
      background:#f8fafc;
      padding:16px;
    }

    /* Pusatkan halaman */
    .page-center{ min-width:100%; display:flex; justify-content:center; }

    /* Kanvas preview: kita atur ukuran sesuai zoom via JS */
    .preview-canvas{ position:relative; }

    /* Yang di-zoom adalah .sheet; kita absolutkan agar ukuran scroll mengikuti .preview-canvas */
    #sheetA4{
      position:absolute; inset:0 auto auto 0;
      transform-origin: top left;
      will-change: transform;
    }
    .modal .sheet{ width:794px; max-width:none; }          /* jangan ikut menyusut */
    .modal .thanks{ position:static !important; margin-top:22px !important; 

    /* Tombol kecil lebih rapat */
    .icon-btn.ghost{ min-width:36px; }

    .icon-btn{border:0;background:#111827;color:#fff;width:36px;height:36px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
    .icon-btn.ghost{background:#f3f4f6;color:#111827}
    .icon-btn + .icon-btn{margin-left:6px}
  </style>
  <style id="invoiceStyle">
    :root{
      --ink:#111827;
      --muted:#6b7280;
      --line:#e5e7eb;
      --accent:#f9cf3a; /* kuning */
    }
    .paper{width:100%;background:#fff}
    .sheet{width:794px; /* A4 minus margin preview */ max-width:100%; margin:0 auto; padding:22px 26px 28px 26px; color:var(--ink)}
    .inv-head{display:flex;align-items:center;justify-content:space-between}
    .brand-left{display:flex;align-items:center;gap:12px}
    .brand-left .logo{height:54px;width:auto;object-fit:contain}
    .brand-name{font-weight:800;letter-spacing:.6px}
    .big-title{font-size:44px;font-weight:900;letter-spacing:2px}
    .sub-id{font-size:12px;color:var(--muted);text-align:right}
    .hr-accent{height:3px;background:var(--accent);margin:10px 0 16px}
    .to-box{margin:10px 0 18px}
    .to-title{font-weight:800}
    .to-name{font-size:20px;font-weight:900;margin:8px 0}
    .right-contact{font-size:12px;color:var(--ink);text-align:right;line-height:1.4}
    .inv-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}

    table.inv-table{width:100%;border-collapse:collapse;margin-top:10px}
    .inv-table th,.inv-table td{border:1px solid #d1d5db;padding:10px 12px;vertical-align:top}
    .inv-table th{background:var(--accent);text-transform:uppercase;font-size:12px;letter-spacing:.5px;text-align:left}
    .inv-table .num{text-align:right}
    .totals{display:grid;grid-template-columns:1fr 260px;gap:0;margin-top:8px}
    .totals .box{border:1px solid #d1d5db}
    .totals .row{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e5e7eb;padding:10px 12px}
    .totals .row:last-child{border-bottom:0}
    .totals .label{font-weight:800}
    .totals .final{background:var(--accent);font-weight:900}

    .pay-block{margin-top:16px}
    .pay-title{font-weight:800;margin-bottom:6px}
    .thanks{position:fixed;left:26px;right:26px;bottom:28px;font-size:12px;font-weight:800}
    .sign{display:flex;align-items:flex-end;gap:24px;justify-content:flex-end;margin-top:28px}
    .sign .name{font-weight:800;text-align:center;margin-top:8px}
    @media print{
      /* A4 + margin aman */
      @page{ size:A4; margin:14mm 12mm; }

      html, body{ height:auto !important; }
      body{ background:#fff !important; margin:0 !important; }

      /* Sembunyikan semua child langsung body, kecuali invoice clone */
      body > *{ display:none !important; }
      body > .for-print{ display:block !important; }

      /* Tampilkan invoice apa adanya (biarkan display child tetap grid, flex, dll) */
      .for-print{ position:static !important; margin:0 !important; padding:0 !important; }

      /* Lebar konten pas area cetak */
      .sheet{
        width:186mm !important;          /* 210mm - (12+12) */
        max-width:none !important;
        margin:0 auto !important;
        padding:12mm 10mm !important;
        box-sizing:border-box;
        break-inside:avoid; page-break-inside:avoid;
      }
      .thanks{ position:static !important; margin-top:22px !important; }
    }

  </style>

  <style>
    /* Paksa warna background ikut tercetak */
    #invoicePrintable, #invoicePrintable *,
    .for-print, .for-print *{
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important; /* Firefox */
    }
    /* Sedikit perbaikan tipografi agar sama persis */
    .sheet{ font:14px/1.45 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
  </style>


  <!-- ===== Thermal (80mm) ===== -->
  <style id="thermalStyle">
    .receipt{width:80mm; padding:6mm 4mm; color:#000; font: 12px/1.4 Arial, Helvetica, sans-serif}
    .rc-head{display:flex; align-items:center; gap:8px; margin-bottom:6px}
    .rc-logo{height:28px}
    .rc-name{font-weight:700}
    .rc-mid{border-top:1px dashed #000; border-bottom:1px dashed #000; padding:6px 0; margin:6px 0}
    .rc-center{text-align:center}
    .rc-right{text-align:right}
    .rc-table{width:100%; border-collapse:collapse; margin-top:4px}
    .rc-table th,.rc-table td{padding:4px 0}
    .rc-table th{text-align:left; border-bottom:1px dashed #000}
    .rc-table .num{text-align:right}
    .rc-total{margin-top:4px; border-top:1px dashed #000; padding-top:4px}
    @media print{@page{size:80mm auto;margin:0}}
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
      <a class="menu-item" href="{{ route('wms.transaksi.index') }}">&larr; Kembali</a>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Detail Transaksi <small style="font-size:14px;font-weight:600;color:#64748b">#{{ $trx->invoice }}</small></h1>
      <span class="badge {{ $trx->status }}">{{ strtoupper($trx->status) }}</span>
    </header>

    @if(session('ok')) <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if(session('err')) <div class="alert-error">{!! nl2br(e(session('err'))) !!}</div> @endif

    <div class="card" style="margin-bottom:12px">
      <div class="grid-2">
        <div class="row"><div class="lbl">Tanggal</div><div class="val">{{ $trx->tanggal?->format('Y-m-d') }}</div></div>
        <div class="row"><div class="lbl">Pengirim</div><div class="val">{{ $trx->pengirim }} ({{ $trx->no_telp_pengirim }})</div></div>
        <div class="row"><div class="lbl">Penerima</div><div class="val">{{ $trx->nama_penerima }} ({{ $trx->no_telp_penerima }})</div></div>
        <div class="row" style="grid-column:1/-1"><div class="lbl">Alamat</div><div class="val">{{ $trx->alamat_penerima }}</div></div>
        <div class="row"><div class="lbl">Logistik</div><div class="val">{{ $trx->jenis_logistik ?: '—' }}</div></div>
        <div class="row"><div class="lbl">No Resi</div><div class="val">{{ $trx->no_resi ?: '—' }}</div></div>
      </div>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Item</h3>
      <div style="overflow-x:auto">
        <table class="table">
          <thead>
            <tr>
                <th>Nama Produk</th>
                <th>Qty</th>
                <th>harga</th>
                <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @foreach($details as $d)
                <tr>
                    <td>{{ $d->nama_produk }}</td>
                    <td>{{ number_format($d->qty) }}</td>
                    <td class="num">Rp {{ number_format($d->harga,0,',','.') }}</td>
                    <td class="num">Rp {{ number_format($d->subtotal,0,',','.') }}</td>
                </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
                <th colspan="3" style="text-align:right">Total Nilai</th>
                <th class="num">Rp {{ number_format($totalNilai,0,',','.') }}</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    {{-- PENDING INFO --}}
    @if($trx->pending_action)
      <div class="alert-error" style="margin-top:12px">
        Ada permintaan {{ $trx->pending_action==='edit'?'perubahan':'pembatalan' }} menunggu persetujuan OMS.
      </div>
    @endif

    {{-- ACTIONS FOOTER --}}
    <div class="card" style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;align-items:center;">
      {{-- READY: edit & batal langsung --}}
      @if($trx->status==='ready')
        <a class="btn" href="{{ route('wms.transaksi.edit',$trx) }}">Edit</a>
        <form method="POST" action="{{ route('wms.transaksi.cancel',$trx) }}" onsubmit="return confirm('Batalkan transaksi ini?')">
          @csrf <button class="btn-danger">Batalkan</button>
        </form>
      @endif

      {{-- PROCESSING: ajukan perubahan / pembatalan --}}
      @if($trx->status==='processing')
        <a class="btn" href="{{ route('wms.transaksi.edit',$trx) }}?mode=request" onclick="event.preventDefault();document.getElementById('reqEditForm').submit();">Ajukan Perubahan</a>
        <form id="reqEditForm" method="POST" action="{{ route('wms.transaksi.request-edit',$trx) }}" style="display:none">
          @csrf
        </form>

        <form method="POST" action="{{ route('wms.transaksi.request-cancel',$trx) }}" onsubmit="return confirm('Ajukan pembatalan ke OMS?')">
          @csrf <button class="btn">Ajukan Pembatalan</button>
        </form>

        <form method="POST" action="{{ route('wms.transaksi.to-shipped',$trx) }}" onsubmit="return confirm('Tandai sebagai DIKIRIM?')">
          @csrf <button class="btn-primary">Tandai Dikirim</button>
        </form>
      @endif

      {{-- SHIPPED: boleh tandai selesai --}}
      @if($trx->status==='shipped')
        <form method="POST" action="{{ route('wms.transaksi.to-done',$trx) }}" onsubmit="return confirm('Tandai sebagai SELESAI?')">
          @csrf <button class="btn-primary">Tandai Selesai</button>
        </form>
      @endif

      {{-- CETAK INVOICE --}}
      <button type="button" class="btn" id="btnPreviewInvoice">Cetak Invoice</button>
    </div>
  </main>
</div>

{{-- ========= INVOICE MODAL ========= --}}
@php
  $discPct = (float)($trx->discount_pct ?? 0);
  $discAmt = $discPct > 0 ? floor($totalNilai * $discPct / 100) : 0;
  $final   = $totalNilai - $discAmt;
  $alamatToko = trim(($toko->alamat ?? '').', '.($toko->kota ?? '').', '.($toko->provinsi ?? ''));
@endphp

<div class="modal" id="invoiceModal" aria-hidden="true">
  <div class="modal-card">
    <div class="modal-head">
      <div class="modal-title">Preview Invoice</div>
      <div>
        <button class="icon-btn ghost" id="btnZoomOut"  title="Zoom out">−</button>
        <button class="icon-btn ghost" id="btnZoomReset" title="Reset zoom">100%</button>
        <button class="icon-btn ghost" id="btnZoomFit"  title="Fit to width">↔︎</button>

        <button class="icon-btn ghost" id="btnPrintNormal" title="Cetak (berwarna)" aria-label="Cetak berwarna">
          <!-- printer -->
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
        </button>
        <button class="icon-btn ghost" id="btnPrintThermal" title="Cetak Thermal" aria-label="Cetak thermal">
          <!-- receipt -->
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 2h16v14l-3-2-3 2-3-2-3 2-4-2z"></path>
            <path d="M8 7h8M8 11h8"></path>
          </svg>
        </button>
        <button class="icon-btn" id="btnCloseInvoice" title="Tutup" aria-label="Tutup">
          <!-- x -->
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>

    <div class="preview-viewport" id="previewViewport">
    <div class="page-center">
    <div class="preview-canvas" id="previewCanvas">
    <div class="paper" id="invoicePrintable">
      <div class="sheet" id="sheetA4">
        <div class="inv-head">
          <div class="brand-left">
            @if(($toko->logo_path ?? null))
              <img class="logo" src="{{ asset('storage/'.$toko->logo_path) }}" alt="Logo">
            @else
              <div class="logo" style="height:54px;width:54px;border:2px solid #111827;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800">LOGO</div>
            @endif
            <div class="brand-name">{{ strtoupper($toko->nama_toko ?? 'TOKO') }}</div>
          </div>
          <div style="text-align:right">
            <div class="big-title">INVOICE</div>
            <div class="sub-id">Invoice ID : {{ $trx->invoice }}</div>
          </div>
        </div>
        <div class="hr-accent"></div>

        <div class="inv-grid">
          <div class="to-box">
            <div class="to-title">INVOICE TO:</div>
            <div class="to-name">{{ strtoupper($trx->nama_penerima) }}</div>
          </div>
          <div class="right-contact">
            {{ $toko->no_telp ? $toko->no_telp : $trx->no_telp_pengirim }}<br>
            {{ $toko->email ?? '' }}<br>
            {{ $alamatToko }}<br>
            {{ $toko->negara ?? 'Indonesia' }}
          </div>
        </div>

        <table class="inv-table">
          <thead>
            <tr>
              <th style="width:52%">Product</th>
              <th style="width:16%" class="num">Price</th>
              <th style="width:12%" class="num">Qty</th>
              <th style="width:20%" class="num">Sub-total</th>
            </tr>
          </thead>
          <tbody>
            @foreach($details as $d)
              <tr>
                <td>{{ strtoupper($d->nama_produk) }}</td>
                <td class="num">Rp {{ number_format($d->harga,0,',','.') }}</td>
                <td class="num">{{ number_format($d->qty) }} pcs</td>
                <td class="num">Rp {{ number_format($d->subtotal,0,',','.') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="totals">
          <div></div>
          <div class="box">
            <div class="row">
              <div class="label">TOTAL</div>
              <div class="num">Rp {{ number_format($totalNilai,0,',','.') }}</div>
            </div>
            @if($discPct>0)
              <div class="row">
                <div class="label">DISCOUNT ({{ rtrim(rtrim(number_format($discPct,2), '0'), '.') }}%)</div>
                <div class="num">Rp {{ number_format($discAmt,0,',','.') }}</div>
              </div>
            @endif
            <div class="row final">
              <div class="label">FINAL</div>
              <div class="num">Rp {{ number_format($final,0,',','.') }}</div>
            </div>
          </div>
        </div>

        <div class="pay-block">
          <div class="pay-title">Payment Method</div>
          <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 10px;max-width:520px">
            <div>Name</div><div>: {{ $toko->bank_account_name ?? ($toko->nama_toko ?? '') }}</div>
            <div>id Bank</div><div>: {{ $toko->bank_account_no ?? '-' }}</div>
            <div>Bank</div><div>: {{ $toko->bank_name ?? '-' }}</div>
          </div>
        </div>

        <div class="sign">
          <div style="width:220px">
            <div style="height:80px;border-bottom:2px solid #f59e0b;margin-bottom:8px"></div>
            <div class="name">{{ $trx->pengirim ?: ($toko->nama_toko ?? '') }}</div>
          </div>
        </div>

        <div class="thanks">THANK YOU FOR PLACING YOUR TRUST IN OUR COMPANY</div>
      </div>
    </div>
    </div>
    </div>
    </div>

    <!-- === Thermal template (hidden, dipakai saat cetak thermal) === -->
    <div id="invoiceThermal" style="display:none">
      <div class="receipt">
        <div class="rc-head">
          @if(($toko->logo_path ?? null))
            <img class="rc-logo" src="{{ asset('storage/'.$toko->logo_path) }}" alt="Logo">
          @endif
          <div class="rc-name">{{ strtoupper($toko->nama_toko ?? 'TOKO') }}</div>
        </div>
        <div class="rc-center"><strong>INVOICE #{{ $trx->invoice }}</strong></div>
        <div style="display:flex;justify-content:space-between">
          <div>{{ $trx->tanggal?->format('Y-m-d') }}</div>
          <div>{{ $toko->currency ?? 'IDR' }}</div>
        </div>
        <div class="rc-mid">
          Kepada: <strong>{{ $trx->nama_penerima }}</strong><br>
          {{ $trx->alamat_penerima }}
        </div>

        <table class="rc-table">
          <thead><tr><th>Produk</th><th class="num">Sub</th></tr></thead>
          <tbody>
          @foreach($details as $d)
            <tr>
              <td>
                {{ $d->nama_produk }}<br>
                {{ number_format($d->qty) }} x Rp {{ number_format($d->harga,0,',','.') }}
              </td>
              <td class="num">Rp {{ number_format($d->subtotal,0,',','.') }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>

        <div class="rc-total">
          <div style="display:flex;justify-content:space-between">
            <div>Total</div><div>Rp {{ number_format($totalNilai,0,',','.') }}</div>
          </div>
          @if($discPct>0)
          <div style="display:flex;justify-content:space-between">
            <div>Disc ({{ rtrim(rtrim(number_format($discPct,2), '0'), '.') }}%)</div><div>Rp {{ number_format($discAmt,0,',','.') }}</div>
          </div>
          @endif
          <div style="display:flex;justify-content:space-between;font-weight:700">
            <div>Final</div><div>Rp {{ number_format($final,0,',','.') }}</div>
          </div>
        </div>

        <div class="rc-center" style="margin-top:8px">Terima kasih 🙏</div>
      </div>
    </div>

  </div>
</div>

<script>
  const modal = document.getElementById('invoiceModal');
  document.getElementById('btnPreviewInvoice')?.addEventListener('click',()=> modal.classList.add('open'));
  document.getElementById('btnCloseInvoice')?.addEventListener('click',()=> modal.classList.remove('open'));
  modal?.addEventListener('click',e=>{ if(e.target===modal) modal.classList.remove('open'); });

  // --- CETAK BIASA (BERWARNA) : clone node yang sama, print, lalu bersihkan ---
  function printCurrentInvoice(){
    const src = document.getElementById('invoicePrintable');
    const clone = src.cloneNode(true);
    clone.classList.add('for-print');
    document.body.appendChild(clone);

    // Pastikan gambar sudah siap sebelum print
    const imgs = clone.querySelectorAll('img');
    let loaded = 0;
    const done = () => { setTimeout(()=>window.print(), 25); };

    if(imgs.length===0){ done(); }
    imgs.forEach(img=>{
      if (img.complete) { if(++loaded===imgs.length) done(); }
      else { img.addEventListener('load', ()=>{ if(++loaded===imgs.length) done(); }); }
    });

    window.onafterprint = () => {
      clone.remove();
      window.onafterprint = null;
    };
  }
  document.getElementById('btnPrintNormal')?.addEventListener('click', printCurrentInvoice);

  // --- CETAK THERMAL (tetap pakai window baru 80mm) ---
  function printWith(html, styleCss){
    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.open();
    w.document.write(
      '<!doctype html><html><head><meta charset="utf-8"><title>Print</title>' +
      '<style>'+styleCss+'</style></head><body>'+html+'</body></html>'
    );
    w.document.close();
    w.onload = () => { w.focus(); w.print(); };
  }
  document.getElementById('btnPrintThermal')?.addEventListener('click', ()=>{
    const html  = document.getElementById('invoiceThermal').innerHTML;
    const style = document.getElementById('thermalStyle').innerHTML;
    printWith(html, style);
  });
  
  // ====== Viewer Zoom ======
  const viewport   = document.getElementById('previewViewport');
  const canvas     = document.getElementById('previewCanvas');
  const sheet      = document.getElementById('sheetA4');      // elemen yang di-scale
  const btnOut     = document.getElementById('btnZoomOut');
  const btnReset   = document.getElementById('btnZoomReset');
  const btnFit     = document.getElementById('btnZoomFit');

  let zoom = 1;
  let baseW = 794;  // fallback: lebar A4 preview (px) sesuai CSS .sheet
  let baseH = 1123; // fallback tinggi kira-kira; akan diperbarui saat modal dibuka

  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

  function measureBase(){
    // ukur dari DOM aktual
    // (pakai getBoundingClientRect agar pasti)
    const r = sheet.getBoundingClientRect();
    baseW = sheet.offsetWidth || r.width || 794;
    baseH = sheet.offsetHeight || r.height || 1123;
  }

  function applyZoom(pivotX=null, pivotY=null){
    // set ukuran kanvas agar scrollbar mengikuti skala (supaya tak "terpotong")
    canvas.style.width  = (baseW * zoom) + 'px';
    canvas.style.height = (baseH * zoom) + 'px';
    sheet.style.transform = `scale(${zoom})`;

    // update label 100%
    if (btnReset) btnReset.textContent = Math.round(zoom*100)+'%';

    // jaga posisi sekitar kursor saat zoom via wheel/pinch
    if (pivotX!=null && pivotY!=null){
      const viewRect = viewport.getBoundingClientRect();
      const viewX = pivotX - viewRect.left + viewport.scrollLeft;
      const viewY = pivotY - viewRect.top  + viewport.scrollTop;

      const relX = viewX / (baseW * lastZoom);
      const relY = viewY / (baseH * lastZoom);

      viewport.scrollLeft = relX * (baseW * zoom) - (pivotX - viewRect.left);
      viewport.scrollTop  = relY * (baseH * zoom) - (pivotY - viewRect.top);
    }
  }

  let lastZoom = zoom;
  modal?.addEventListener('transitionend', () => { // jika pakai animasi; aman dipanggil berulang
    if (modal.classList.contains('open')) {
      measureBase();
      lastZoom = zoom = 1;
      applyZoom();
    }
  });

  // Tombol
  btnOut?.addEventListener('click', () => { lastZoom = zoom; zoom = clamp(+(zoom-0.1).toFixed(2), 0.3, 3); applyZoom(); });
  btnReset?.addEventListener('click', () => { lastZoom = zoom; zoom = 1; applyZoom(); });
  btnFit?.addEventListener('click', () => {
    measureBase();
    const pad = 32; // padding viewport
    const w = viewport.clientWidth - pad;
    lastZoom = zoom;
    zoom = clamp(w / baseW, 0.3, 3);
    applyZoom();
  });

  // Ctrl/Cmd + wheel untuk pinch/zoom
  viewport?.addEventListener('wheel', (e) => {
    if (!e.ctrlKey) return; // hanya saat pinch/ctrl
    e.preventDefault();
    lastZoom = zoom;
    const dir = e.deltaY > 0 ? -1 : 1;
    zoom = clamp(+(zoom + dir*0.1).toFixed(2), 0.3, 3);
    applyZoom(e.clientX, e.clientY);
  }, { passive:false });

  // Shortcut keyboard (Cmd/Ctrl + +/-/0)
  window.addEventListener('keydown', (e)=>{
    if (!modal.classList.contains('open')) return;
    if (!(e.ctrlKey || e.metaKey)) return;
    if (['=','+'].includes(e.key)){ e.preventDefault(); lastZoom=zoom; zoom=clamp(zoom+0.1,0.3,3); applyZoom(); }
    if (e.key==='-'){ e.preventDefault(); lastZoom=zoom; zoom=clamp(zoom-0.1,0.3,3); applyZoom(); }
    if (e.key==='0'){ e.preventDefault(); lastZoom=zoom; zoom=1; applyZoom(); }
  });

  // Saat jendela di-resize, mode fit bisa diulang cepat (opsional)
  window.addEventListener('resize', () => {
    if (!modal.classList.contains('open')) return;
    // kalau sebelumnya fit, tekan tombol fit lagi untuk menyesuaikan
    // (biarkan manual agar simple)
  });


</script>

</body>
</html>
