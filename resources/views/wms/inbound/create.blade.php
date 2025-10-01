<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Buat Inbound</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  @vite('resources/css/wms-inbound.css')
  <style>
    /* opsional: highlight baris yang qty>0 */
    tr.qty-positive { background: #fffbeb; }
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
      <a class="menu-item" href="{{ url('/wms/dashboard') }}">Dashboard</a>
      <a class="menu-item" href="{{ url('/wms/transaksi') }}">Transaksi</a>
      <a class="menu-item active" href="{{ url('/wms/inbound') }}">Inbound</a>
      <a class="menu-item" href="{{ url('/wms/stock') }}">Stock</a>
      <a class="menu-item" href="{{ route('wms.produk.index') }}">Produk</a>
      <a class="menu-item" href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>

      <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
        <h1>Buat Inbound</h1>
        <nav class="dash-menu">
            <a class="btn" href="{{ route('wms.inbound.index') }}" id="btnBack">&larr; Kembali</a>
        </nav>
    </header>
    {{-- FORM GET untuk pencarian --}}
    <form id="searchForm" method="GET" action="{{ route('wms.inbound.create') }}">
        <input type="hidden" name="picked" id="pickedField">
        <input type="hidden" name="pm" id="pickedMapField"> 
    </form>

    @if($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif
    {{-- FORM POST untuk inbound --}}
    <form method="POST" action="{{ route('wms.inbound.store') }}">
      @csrf

      <div class="card" style="margin-bottom:16px">
        <div class="form-row">
          <label>Tanggal*</label>
          <input type="hidden" name="tanggal_inbound" value="{{ now('Asia/Jakarta')->format('Y-m-d\TH:i') }}">
          <input type="datetime-local" value="{{ now('Asia/Jakarta')->format('Y-m-d\TH:i') }}" disabled
                 style="background:#f3f4f6;color:#111827;cursor:not-allowed;">
        </div>

        <div class="form-row">
          <label>No Resi</label>
          <input type="text" name="no_resi" placeholder="Opsional" value="{{ old('no_resi') }}">
        </div>

        <div class="form-row">
          <label>Deskripsi</label>
          <textarea name="deskripsi" rows="2" placeholder="Catatan (opsional)">{{ old('deskripsi') }}</textarea>
        </div>
      </div>

      {{-- Search + tabel produk --}}
      <div class="card">
        <div class="form-row" style="justify-content:space-between; gap:12px; align-items:center">
            <h3 style="margin:0">Pilih Produk & Qty</h3>
            <div style="display:flex;gap:8px">
                <input form="searchForm" type="text" name="q"
                    value="{{ $q }}" placeholder="Cari nama / SKU…" style="width:260px">
                <button form="searchForm" class="btn" type="submit">Cari</button>
                @if($q)
                <a class="btn" href="{{ route('wms.inbound.create') }}">Reset</a>
                @endif
            </div>
        </div>

        <div style="overflow-x:auto; margin-top:12px">
          <table class="table">
            <thead>
              <tr>
                <th>Nama Produk</th>
                <th>SKU</th>
                <th style="width:140px">Qty</th>
              </tr>
            </thead>
            <tbody id="produkTbody">
              @forelse($produk as $p)
                @php
                  $val = old('qty.'.$p->id_produk, 0);
                @endphp
                <tr data-id="{{ $p->id_produk }}" class="{{ $val>0?'qty-positive':'' }}">
                  <td>{{ $p->nama_produk }}</td>
                  <td>{{ $p->sku ?? '—' }}</td>
                  <td>
                    <input class="qty-input"
                           type="number" min="0" step="1"
                           name="qty[{{ $p->id_produk }}]"
                           value="{{ $val }}"
                           style="width:120px">
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" style="text-align:center;color:#6b7280">Belum ada produk.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- paginate 10/halaman --}}
        <div style="margin-top:12px">
          {{ $produk->links() }}
        </div>
      </div>

      <div style="margin-top:12px; display:flex; gap:8px">
        <a class="btn" href="{{ route('wms.inbound.index') }}" id="btnCancel">Batal</a>
        <button class="btn-primary" type="submit">Simpan Draft</button>
      </div>
    </form>
  </main>
</div>

<script>
(function () {
  const KEY = 'inboundQty:' + @json(auth()->user()->chain_link ?? 'default');
  const CREATE_PATH = @json(parse_url(route('wms.inbound.create'), PHP_URL_PATH));

  const formPost   = document.querySelector('form[action$="{{ route('wms.inbound.store') }}"]');
  const searchForm = document.getElementById('searchForm');
  const pickedFld  = document.getElementById('pickedField');
  const pickedMapFld = document.getElementById('pickedMapField');
  const tbody      = document.getElementById('produkTbody');

  const getMap = () => JSON.parse(localStorage.getItem(KEY) || '{}');
  const setMap = (m) => localStorage.setItem(KEY, JSON.stringify(m));
  const qtyInputs = () => Array.from(document.querySelectorAll('.qty-input'));

  function buildPickedCsv() {
    const map = getMap();
    return Object.entries(map)
      .filter(([,v]) => parseInt(v,10) > 0)
      .map(([id]) => id)
      .join(',');
  }
  function buildPickedMapCsv() {
    const map = getMap();
    return Object.entries(map)
      .filter(([,v]) => parseInt(v,10) > 0)
      .map(([id,qty]) => `${id}:${parseInt(qty,10)}`)
      .join(',');
  }

  function syncPickedFields(){
    if (pickedFld) pickedFld.value = buildPickedCsv();
    if (pickedMapFld) pickedMapFld.value = buildPickedMapCsv();
  }

  function saveInputsToStorage() {
    const map = getMap();
    qtyInputs().forEach(inp => {
      const id  = (inp.name.match(/\[(\d+)\]/)||[])[1];
      if (!id) return;
      const val = parseInt(inp.value || '0', 10);
      if (val > 0) map[id] = val; else delete map[id];
    });
    setMap(map);
    syncPickedFields();
    patchLinksWithPicked();
  }

  function patchLinksWithPicked() {
    const picked = buildPickedCsv();
    const pm     = buildPickedMapCsv();

    document.querySelectorAll('nav[role="navigation"] a, .pagination a, a.btn').forEach(a => {
      const href = a.getAttribute('href') || '';
      try {
        const u = new URL(href, location.origin);

        // tempel picked & pm ke SEMUA link relevant
        if (picked) u.searchParams.set('picked', picked); else u.searchParams.delete('picked');
        if (pm)     u.searchParams.set('pm', pm);         else u.searchParams.delete('pm');

        const isPagination = !!a.closest('nav[role="navigation"], .pagination');
        const isCreateLink = u.pathname === CREATE_PATH;

        // Jangan paksa page=1 pada pagination.
        // Untuk tombol Reset (link ke create), hapus page agar kembali ke atas.
        if (!isPagination && isCreateLink) {
          u.searchParams.delete('page');
        }

        a.href = u.toString();
      } catch {}
    });
  }

  function reorderRowKeepFocus(inp){
    const tr = inp.closest('tr'); if (!tr) return;
    const qty = parseInt(inp.value || '0', 10);
    const selStart = inp.selectionStart, selEnd = inp.selectionEnd;

    tr.classList.toggle('qty-positive', qty > 0);
    if (qty > 0) tbody.prepend(tr); else tbody.appendChild(tr);

    requestAnimationFrame(() => {
      inp.focus({ preventScroll: true });
      try { if (selStart != null) inp.setSelectionRange(selStart, selEnd); } catch {}
    });
  }
  const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); } };
  const reorderDebounced = debounce(reorderRowKeepFocus, 250);
  
  function clearAllQty() {
    // hapus cache qty lintas halaman
    localStorage.removeItem(KEY);
    // opsional: nolkan input yang sedang tampil agar terasa langsung “bersih”
    document.querySelectorAll('.qty-input').forEach(inp => {
      inp.value = '0';
      const tr = inp.closest('tr');
      tr?.classList.remove('qty-positive');
    });
  }

  // Klik "Kembali" dan "Batal" → clear qty lalu lanjut navigasi
  ['btnBack','btnCancel'].forEach(id => {
    const a = document.getElementById(id);
    if (!a) return;
    a.addEventListener('click', () => { clearAllQty(); });
  });

  document.querySelectorAll('a[data-clear-inbound="1"]').forEach(a=>{
    a.addEventListener('click', () => { clearAllQty(); });
  });

  document.addEventListener('input', (e) => {
    if (!e.target.classList.contains('qty-input')) return;
    const inp = e.target;
    const qty = parseInt(inp.value || '0', 10);
    inp.closest('tr').classList.toggle('qty-positive', qty>0);
    saveInputsToStorage();
    reorderDebounced(inp);
  });

  document.addEventListener('keydown', (e) => {
    if (!e.target.classList || !e.target.classList.contains('qty-input')) return;
    if (e.key === 'Enter') {
      e.preventDefault();
      reorderRowKeepFocus(e.target);
      const inputs = qtyInputs();
      const idx = inputs.indexOf(e.target);
      const next = inputs[idx+1] || inputs[0];
      next?.focus(); next?.select?.();
    }
  });

  searchForm?.addEventListener('submit', () => { saveInputsToStorage(); });

  formPost?.addEventListener('submit', () => {
    saveInputsToStorage();
    formPost.querySelectorAll('input.qty-hidden').forEach(n => n.remove());
    const map = getMap();
    Object.entries(map).forEach(([id,val])=>{
      if (parseInt(val,10) > 0) {
        const h = document.createElement('input');
        h.type='hidden'; h.className='qty-hidden';
        h.name=`qty[${id}]`; h.value=val;
        formPost.appendChild(h);
      }
    });
    localStorage.removeItem(KEY);
  });

  // init
  (function init(){
    const map = getMap();
    qtyInputs().forEach(inp=>{
      const id = (inp.name.match(/\[(\d+)\]/)||[])[1];
      if (map[id] != null) inp.value = map[id];
      const qty = parseInt(inp.value || '0', 10);
      inp.closest('tr').classList.toggle('qty-positive', qty>0);
      if (qty > 0) tbody.prepend(inp.closest('tr'));
    });
    syncPickedFields();
    patchLinksWithPicked();
  })();
})();
</script>
