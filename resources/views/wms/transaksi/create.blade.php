<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>WMS • Tambah Transaksi</title>
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
      <a class="menu-item" href="{{ route('wms.transaksi.index') }}">&larr; Kembali</a>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Tambah Transaksi</h1>
      <div></div>
    </header>

    {{-- ===== Alerts ===== --}}
    @include('partials.alerts')

    {{-- form GET untuk pencarian (dipakai pagination juga) --}}
    <form id="searchForm" method="GET" action="{{ route('wms.transaksi.create') }}">
      <input type="hidden" name="pm" id="pickedMapField">
      <input type="hidden" name="q" value="{{ $q }}">
    </form>

    <form method="POST" action="{{ route('wms.transaksi.store') }}" id="postForm">
      @csrf

      {{-- ============ HEADER FORM ============ --}}
      <div class="card" style="margin-bottom:16px">
        @php $today = now('Asia/Jakarta')->toDateString(); @endphp
        <div class="form-grid">
          <div class="field">
            <label>No Invoice<span class="req">*</span></label>
            <input class="input @error('invoice') is-invalid @enderror" type="text" name="invoice" value="{{ old('invoice') }}" required>
            @error('invoice') <div class="field-error">{{ $message }}</div> @enderror
          </div>

          <div class="field">
            <label>Tanggal<span class="req">*</span></label>
            <input type="hidden" name="tanggal" value="{{ old('tanggal', $today) }}">
            <input class="input" type="date" value="{{ old('tanggal', $today) }}" disabled>
            <div class="help">Tanggal otomatis (hari ini).</div>
            @error('tanggal') <div class="field-error">{{ $message }}</div> @enderror
          </div>

          <div class="field">
            <label>Pengirim<span class="req">*</span></label>
            <input class="input @error('pengirim') is-invalid @enderror" type="text" name="pengirim" value="{{ old('pengirim') }}" required>
            @error('pengirim') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="field">
            <label>No Telp Pengirim<span class="req">*</span></label>
            <input class="input @error('no_telp_pengirim') is-invalid @enderror" type="text" name="no_telp_pengirim" value="{{ old('no_telp_pengirim') }}" placeholder="08xxxxxxxxxx" required>
            @error('no_telp_pengirim') <div class="field-error">{{ $message }}</div> @enderror
          </div>

          <div class="field">
            <label>Nama Penerima<span class="req">*</span></label>
            <input class="input @error('nama_penerima') is-invalid @enderror" type="text" name="nama_penerima" value="{{ old('nama_penerima') }}" required>
            @error('nama_penerima') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="field">
            <label>No Telp Penerima<span class="req">*</span></label>
            <input class="input @error('no_telp_penerima') is-invalid @enderror" type="text" name="no_telp_penerima" value="{{ old('no_telp_penerima') }}" placeholder="08xxxxxxxxxx" required>
            @error('no_telp_penerima') <div class="field-error">{{ $message }}</div> @enderror
          </div>

          <div class="field" style="grid-column:1/-1">
            <label>Alamat Penerima<span class="req">*</span></label>
            <textarea class="input @error('alamat_penerima') is-invalid @enderror" name="alamat_penerima" rows="2" required>{{ old('alamat_penerima') }}</textarea>
            @error('alamat_penerima') <div class="field-error">{{ $message }}</div> @enderror
          </div>

          <div class="field">
            <label>Jenis Logistik</label>
            <select name="jenis_logistik" class="select @error('jenis_logistik') is-invalid @enderror">
              <option value="">— Pilih jenis logistik —</option>
              @foreach($logistics as $opt)
                <option value="{{ $opt }}" {{ old('jenis_logistik')===$opt?'selected':'' }}>{{ $opt }}</option>
              @endforeach
            </select>
            @error('jenis_logistik') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="field">
            <label>No Resi</label>
            <input class="input @error('no_resi') is-invalid @enderror" type="text" name="no_resi" value="{{ old('no_resi') }}">
            @error('no_resi') <div class="field-error">{{ $message }}</div> @enderror
          </div>
        </div>
      </div>

      {{-- ============ PRODUK & QTY ============ --}}
      <div class="card" id="section-produk">
        <div class="form-row" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
          <h3 style="margin:0">Pilih Produk & Qty</h3>
          <div class="search-strip">
            <input class="input" form="searchForm" type="text" name="q" value="{{ $q }}" placeholder="Cari nama / SKU…" style="width:280px">
            <button form="searchForm" class="btn" type="submit">Cari</button>
            @if($q) <a class="btn" href="{{ route('wms.transaksi.create') }}">Reset</a> @endif
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
              @forelse($produk as $p)
                @php $val = old('qty.'.$p->id_produk, 0); @endphp
                <tr data-id="{{ $p->id_produk }}" class="{{ $val>0?'qty-positive':'' }}">
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
            <a class="btn" href="{{ route('wms.transaksi.index') }}">Batal</a>
            <button class="btn-primary" type="submit">Simpan</button>
          </div>
        </div>
      </div>
    </form>
  </main>
</div>

<script>
(function(){
  const KEY = 'trxQty:' + @json(auth()->user()->chain_link ?? 'default');
  const searchForm = document.getElementById('searchForm');
  const pickedMapFld = document.getElementById('pickedMapField');
  const postForm = document.getElementById('postForm');
  const tbody = document.getElementById('produkTbody');
  const sumSkuEl = document.querySelector('.sum-sku');
  const sumQtyEl = document.querySelector('.sum-qty');

  const getMap = () => JSON.parse(localStorage.getItem(KEY) || '{}');
  const setMap = (m) => localStorage.setItem(KEY, JSON.stringify(m));
  const qtyInputs = () => Array.from(document.querySelectorAll('.qty-input'));

  function buildPm(){
    const map = getMap();
    return Object.entries(map)
      .filter(([,v])=>parseInt(v,10)>0)
      .map(([id,qty])=>`${id}:${parseInt(qty,10)}`)
      .join(',');
  }
  function syncPm(){ if(pickedMapFld) pickedMapFld.value = buildPm(); }

  function saveInputs(){
    const map = getMap();
    qtyInputs().forEach(inp=>{
      const id = (inp.name.match(/\[(\d+)\]/)||[])[1]; if(!id) return;
      const val = parseInt(inp.value||'0',10);
      if(val>0) map[id]=val; else delete map[id];
      inp.closest('tr')?.classList.toggle('qty-positive', val>0);
    });
    setMap(map); syncPm(); patchPaginationLinks(); updateSummary();
  }

  function patchPaginationLinks(){
    const pm = buildPm();
    document.querySelectorAll('nav[role="navigation"] a, .pagination a').forEach(a=>{
      const href=a.getAttribute('href')||''; try{
        const u=new URL(href, location.origin);
        if(pm) u.searchParams.set('pm', pm); else u.searchParams.delete('pm');
        a.href=u.toString();
      }catch{}
    });
  }

  function updateSummary(){
    const map = getMap();
    let sku=0, qty=0;
    Object.values(map).forEach(v=>{
      const n=parseInt(v,10)||0;
      if(n>0){ sku++; qty+=n; }
    });
    sumSkuEl.textContent = sku.toLocaleString('id-ID');
    sumQtyEl.textContent = qty.toLocaleString('id-ID');
  }

  // Reorder rows (yang qty>0 ke atas) sambil menjaga fokus
  function reorderRowKeepFocus(inp){
    const tr = inp.closest('tr'); if(!tr) return;
    const qty = parseInt(inp.value||'0',10);
    const selStart=inp.selectionStart, selEnd=inp.selectionEnd;
    if(qty>0) tbody.prepend(tr); else tbody.appendChild(tr);
    requestAnimationFrame(()=>{ inp.focus({preventScroll:true}); try{ inp.setSelectionRange(selStart, selEnd) }catch{} });
  }
  const debounce=(fn,ms)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};};
  const reorderDebounced = debounce(reorderRowKeepFocus, 150);

  // Qty input events
  document.addEventListener('input', e=>{
    if(!e.target.classList.contains('qty-input')) return;
    saveInputs(); reorderDebounced(e.target);
  });
  document.addEventListener('keydown', e=>{
    if(!e.target.classList || !e.target.classList.contains('qty-input')) return;
    if(e.key==='Enter'){ e.preventDefault();
      reorderRowKeepFocus(e.target);
      const inputs=qtyInputs(); const idx=inputs.indexOf(e.target);
      (inputs[idx+1]||inputs[0])?.focus();
    }
  });

  // Plus / minus buttons
  document.addEventListener('click', e=>{
    const btn = e.target.closest('.qty-btn'); if(!btn) return;
    const wrap = btn.closest('.qty-wrap'); const inp = wrap.querySelector('.qty-input');
    let v = parseInt(inp.value||'0',10)||0;
    v += (btn.dataset.act==='inc'? 1 : -1);
    if(v<0) v=0; inp.value=v; inp.dispatchEvent(new Event('input',{bubbles:true}));
  });

  // Persist saat cari & saat submit
  searchForm?.addEventListener('submit', saveInputs);
  postForm?.addEventListener('submit', ()=>{
    saveInputs();
    postForm.querySelectorAll('input.qty-hidden').forEach(n=>n.remove());
    const map = getMap();
    Object.entries(map).forEach(([id,val])=>{
      if(parseInt(val,10)>0){
        const h=document.createElement('input');
        h.type='hidden'; h.className='qty-hidden'; h.name=`qty[${id}]`; h.value=val;
        postForm.appendChild(h);
      }
    });
    localStorage.removeItem(KEY);
  });

  // Init: restore dari localStorage
  (function init(){
    const map = getMap();
    qtyInputs().forEach(inp=>{
      const id=(inp.name.match(/\[(\d+)\]/)||[])[1];
      if(map[id]!=null) inp.value = map[id];
      const qty = parseInt(inp.value||'0',10);
      inp.closest('tr').classList.toggle('qty-positive', qty>0);
      if(qty>0) tbody.prepend(inp.closest('tr'));
    });
    syncPm(); patchPaginationLinks(); updateSummary();
  })();
})();
</script>
</body>
</html>
