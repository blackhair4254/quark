<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>WMS • Produk</title>
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
      <a class="menu-item active" href="{{ route('wms.produk.index') }}">Produk</a>
      <a class="menu-item" href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>

      <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
        <h1>Produk</h1>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('wms.produk.create') }}" class="btn-primary">+ Tambah Produk</a>
        </div>
    </header>

    @if(session('ok'))
      <div class="alert-ok">{{ session('ok') }}</div>
    @endif

    <form method="GET" class="filters" style="margin-bottom:12px">
        <button class="btn" style="height:100%;">
            <img src="{{ asset('images/search.svg') }}" alt="search" class="search">
        </button>
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / SKU…" style="width:260px">
    </form>

    <div id="bulkActions" class="bulk-actions is-hidden">
        <form id="bulkForm" method="POST" action="{{ route('wms.produk.bulk-destroy') }}">
            @csrf
            @method('DELETE')
            <button id="bulkDeleteBtn" class="btn-danger" type="button">
            Hapus Terpilih (<span id="selCount">0</span>)
            </button>
        </form>
    </div>


    {{-- Modal konfirmasi --}}
    <div id="confirmModal" class="modal-backdrop hidden" aria-modal="true" role="dialog">
        <div class="modal">
            <h3>Hapus produk terpilih?</h3>
            <p>Anda akan menghapus <strong><span id="selCountConfirm">0</span> produk</strong>. Tindakan ini tidak bisa dibatalkan.</p>
            <div class="modal-actions">
            <button type="button" class="btn" id="cancelConfirm">Batal</button>
            <button type="button" class="btn-danger" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div style="overflow-x:auto">
            <table class="table">
                <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" id="checkAll"></th>
                    <th>Foto</th>
                    <th>Nama Produk</th>
                    <th>SKU</th>
                    <th>Stock</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th style="width:140px">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $p)
                <tr>
                    <td>
                    {{-- perhatikan: name="ids[]" + form="bulkForm" --}}
                    <input type="checkbox"
                            class="row-check"
                            name="ids[]"
                            value="{{ $p->id_produk }}"
                            form="bulkForm">
                    </td>
                    <td>
                    @if($p->foto)
                        <img src="{{ asset('storage/'.$p->foto) }}" style="width:60px;height:60px;object-fit:cover;border-radius:8px">
                    @else — @endif
                    </td>
                    <td>{{ $p->nama_produk }}</td>
                    <td>{{ $p->sku ?? '—' }}</td>
                    <td>{{ number_format($p->stock->qty ?? 0) }}</td>
                    <td>Rp {{ number_format($p->harga_beli,0,',','.') }}</td>
                    <td>Rp {{ number_format($p->harga_jual,0,',','.') }}</td>
                    <td>
                    <a class="btn-sm" href="{{ route('wms.produk.edit', $p) }}">Edit</a>
                    <form action="{{ route('wms.produk.destroy', $p) }}" method="POST" style="display:inline"
                            onsubmit="return confirm('Hapus produk ini?')">
                        @csrf @method('DELETE')
                        <button class="btn-sm btn-danger" type="submit">Hapus</button>
                    </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div style="margin-top:12px">
      {{ $items->links() }}
    </div>

  </main>
</div>
</body>
</html>
<script>
(function(){
  const checkAll        = document.getElementById('checkAll');
  const bulkBar         = document.getElementById('bulkActions');
  const bulkBtn         = document.getElementById('bulkDeleteBtn');
  const selCount        = document.getElementById('selCount');

  const modal           = document.getElementById('confirmModal');
  const selCountConfirm = document.getElementById('selCountConfirm');
  const cancelConfirm   = document.getElementById('cancelConfirm');
  const confirmDelete   = document.getElementById('confirmDelete');

  const bulkForm        = document.getElementById('bulkForm');
  const rowChecks = () => Array.from(document.querySelectorAll('input.row-check[form="bulkForm"]'));

  function refresh() {
    const checks   = rowChecks();
    const selected = checks.filter(c => c.checked);
    const n        = selected.length;

    // tampilkan/ sembunyikan bar bulk + update angka
    bulkBar.classList.toggle('is-hidden', n === 0);
    selCount.textContent = n;

    // state pilih semua
    checkAll.checked       = (n > 0 && n === checks.length);
    checkAll.indeterminate = (n > 0 && n  < checks.length);
  }

  // pilih semua
  checkAll?.addEventListener('change', () => {
    rowChecks().forEach(c => c.checked = checkAll.checked);
    refresh();
  });

  // perubahan tiap baris
  document.addEventListener('change', (e) => {
    if (e.target.classList.contains('row-check')) refresh();
  });

  // klik tombol "Hapus Terpilih" -> buka modal (jangan submit dulu)
  bulkBtn?.addEventListener('click', (e) => {
    const n = parseInt(selCount.textContent || '0', 10);
    if (n > 0) {
      selCountConfirm.textContent = n;
      modal.classList.remove('hidden');
    }
  });

  // tutup modal
  function closeModal(){ modal.classList.add('hidden'); }
  cancelConfirm?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', (e)=> { if (e.key === 'Escape') closeModal(); });

  // konfirmasi hapus -> submit form
  confirmDelete?.addEventListener('click', () => {
    closeModal();
    bulkForm.submit();
  });

  // init
  refresh();
})();
</script>
