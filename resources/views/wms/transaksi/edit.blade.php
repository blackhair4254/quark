<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>WMS • Edit Transaksi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @vite('resources/css/wms-produk.css')
  @vite('resources/css/wms-transaksi.css')
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
      <a class="menu-item" href="{{ route('wms.transaksi.show',$transaksi) }}">&larr; Kembali</a>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Edit Transaksi</h1>
      <div></div>
    </header>

    {{-- ===== Alerts ===== --}}
    @include('partials.alerts')

    {{-- form GET untuk pencarian (dipakai pagination juga) --}}
    <form id="searchForm" method="GET" action="{{ route('wms.transaksi.edit',$transaksi) }}">
      <input type="hidden" name="q" value="{{ $q }}">
    </form>

    {{-- FORM UPDATE --}}
    <form method="POST" action="{{ route('wms.transaksi.update',$transaksi) }}" id="editForm">
      @csrf @method('PUT')

      {{-- ============ HEADER FORM ============ --}}
      <div class="card" style="margin-bottom:16px">
        <div class="form-grid">
          <div class="field">
            <label>No Invoice<span class="req">*</span></label>
            <input class="input @error('invoice') is-invalid @enderror" type="text" name="invoice" value="{{ old('invoice',$transaksi->invoice) }}" required>
            @error('invoice') <div class="field-error">{{ $message }}</div> @enderror
          </div>

          <div class="field">
            <label>Tanggal</label>
            <input class="input" type="date" value="{{ $transaksi->tanggal?->format('Y-m-d') }}" disabled>
            <div class="help">Tanggal tidak dapat diubah.</div>
          </div>

          <div class="field">
            <label>Pengirim<span class="req">*</span></label>
            <input class="input @error('pengirim') is-invalid @enderror" type="text" name="pengirim" value="{{ old('pengirim',$transaksi->pengirim) }}" required>
            @error('pengirim') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="field">
            <label>No Telp Pengirim<span class="req">*</span></label>
            <input class="input @error('no_telp_pengirim') is-invalid @enderror" type="text" name="no_telp_pengirim" value="{{ old('no_telp_pengirim',$transaksi->no_telp_pengirim) }}" required>
            @error('no_telp_pengirim') <div class="field-error">{{ $message }}</div> @enderror
          </div>

          <div class="field">
            <label>Nama Penerima<span class="req">*</span></label>
            <input class="input @error('nama_penerima') is-invalid @enderror" type="text" name="nama_penerima" value="{{ old('nama_penerima',$transaksi->nama_penerima) }}" required>
            @error('nama_penerima') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="field">
            <label>No Telp Penerima<span class="req">*</span></label>
            <input class="input @error('no_telp_penerima') is-invalid @enderror" type="text" name="no_telp_penerima" value="{{ old('no_telp_penerima',$transaksi->no_telp_penerima) }}" required>
            @error('no_telp_penerima') <div class="field-error">{{ $message }}</div> @enderror
          </div>

          <div class="field" style="grid-column:1/-1">
            <label>Alamat Penerima<span class="req">*</span></label>
            <textarea class="input @error('alamat_penerima') is-invalid @enderror" name="alamat_penerima" rows="2" required>{{ old('alamat_penerima',$transaksi->alamat_penerima) }}</textarea>
            @error('alamat_penerima') <div class="field-error">{{ $message }}</div> @enderror
          </div>

          <div class="field">
            <label>Jenis Logistik</label>
            <select name="jenis_logistik" class="select @error('jenis_logistik') is-invalid @enderror">
              <option value="">— Pilih jenis logistik —</option>
              @foreach($logistics as $opt)
                <option value="{{ $opt }}" {{ old('jenis_logistik',$transaksi->jenis_logistik)===$opt?'selected':'' }}>{{ $opt }}</option>
              @endforeach
            </select>
            @error('jenis_logistik') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="field">
            <label>No Resi</label>
            <input class="input @error('no_resi') is-invalid @enderror" type="text" name="no_resi" value="{{ old('no_resi',$transaksi->no_resi) }}">
            @error('no_resi') <div class="field-error">{{ $message }}</div> @enderror
          </div>
        </div>
      </div>

      {{-- ============ PRODUK & QTY ============ --}}
      <div class="card" id="section-produk">
        <div class="form-row" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
          <h3 style="margin:0">Ubah Produk & Qty</h3>
          <div class="search-strip">
            <input class="input" form="searchForm" type="text" name="q" value="{{ $q }}" placeholder="Cari nama / SKU…" style="width:280px">
            <button form="searchForm" class="btn" type="submit">Cari</button>
            @if($q) <a class="btn" href="{{ route('wms.transaksi.edit',$transaksi) }}">Reset</a> @endif
          </div>
        </div>

        @if ($errors->has('qty'))
          <div class="alert alert-error" role="alert" style="margin-top:8px">
            <button type="button" class="alert-close" aria-label="Tutup">✕</button>
            <div class="ttl">Jumlah / pilihan produk belum valid</div>
            <div class="msg">{!! nl2br(e($errors->first('qty'))) !!}</div>
          </div>
        @endif

        <div style="overflow-x:auto;margin-top:12px">
          <table class="table products">
            <thead>
              <tr>
                <th>Nama Produk</th>
                <th>SKU</th>
                <th style="width:160px">Qty</th>
              </tr>
            </thead>
            <tbody id="produkTbody">
              @php $pf = $prefill ?? []; @endphp
              @forelse($produk as $p)
                @php $val = old('qty.'.$p->id_produk, $pf[$p->id_produk] ?? 0); @endphp
                <tr class="{{ $val>0?'qty-positive':'' }}">
                  <td>{{ $p->nama_produk }}</td>
                  <td>{{ $p->sku ?? '—' }}</td>
                  <td>
                    <div class="qty-wrap">
                      <button class="qty-btn" type="button" data-act="dec">–</button>
                      <input class="input qty-input" type="number" min="0" step="1"
                             name="qty[{{ $p->id_produk }}]" value="{{ $val }}">
                      <button class="qty-btn" type="button" data-act="inc">+</button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" style="text-align:center;color:#6b7280">Belum ada produk.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div style="margin-top:12px">{{ $produk->links() }}</div>
      </div>

      {{-- ============ CTA STICKY BAR ============ --}}
      <div class="sticky-cta">
        <div class="cta-card">
          <div class="metrics">
            <div class="metric">SKU dipilih: <b class="sum-sku">0</b></div>
            <div class="metric">Total Qty: <b class="sum-qty">0</b></div>
          </div>
          <div style="display:flex;gap:8px">
            <a class="btn" href="{{ route('wms.transaksi.show',$transaksi) }}">Batal</a>
            <button class="btn-primary" type="submit">Simpan Perubahan</button>
          </div>
        </div>
      </div>
    </form>
  </main>
</div>

<script>
(function(){
  const sumSkuEl=document.querySelector('.sum-sku');
  const sumQtyEl=document.querySelector('.sum-qty');

  function updateSummary(){
    let sku=0,qty=0;
    document.querySelectorAll('.qty-input').forEach(inp=>{
      const v=parseInt(inp.value||'0',10)||0;
      if(v>0){ sku++; qty+=v; }
      inp.closest('tr')?.classList.toggle('qty-positive', v>0);
    });
    sumSkuEl.textContent=sku.toLocaleString('id-ID');
    sumQtyEl.textContent=qty.toLocaleString('id-ID');
  }

  document.addEventListener('click',e=>{
    const btn=e.target.closest('.qty-btn'); if(!btn) return;
    const wrap=btn.closest('.qty-wrap'); const inp=wrap.querySelector('.qty-input');
    let v=parseInt(inp.value||'0',10)||0;
    v += (btn.dataset.act==='inc'?1:-1); if(v<0) v=0;
    inp.value=v; updateSummary();
  });

  document.addEventListener('input',e=>{
    if(!e.target.classList.contains('qty-input')) return;
    let v=parseInt(e.target.value||'0',10)||0; if(v<0) v=0;
    e.target.value=v; updateSummary();
  });

  updateSummary();
})();
</script>
</body>
</html>
