<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>WMS • Inbound</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  @vite('resources/css/wms-inbound.css')
</head>
<body class="dash-body">
<div class="dash-layout">

  {{-- Sidebar (samakan dengan yang lain) --}}
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
      <a class="menu-item active" href="{{ route('wms.inbound.index') }}">Inbound</a>
      <a class="menu-item" href="{{ url('/wms/stock') }}">Stock</a>
      <a class="menu-item" href="{{ route('wms.produk.index') }}">Produk</a>
      <a class="menu-item " href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>
      <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
        @csrf <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">

    <header class="main-header">
      <h1>Inbound</h1>
      <a href="{{ route('wms.inbound.create') }}" class="btn-primary">+ Buat Inbound</a>
    </header>

    @if(session('ok')) <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    {{-- Tabs --}}
    @php
      $tabs = ['all'=>'Semua','draft'=>'Dibuat','sent'=>'Dikirim','accept'=>'Diterima','confirm'=>'Dikonfirmasi','denied'=>'Ditolak'];
    @endphp

    <div class="tabs">
      @foreach($tabs as $key=>$label)
        <a class="tab {{ $tab===$key?'active':'' }}" href="{{ route('wms.inbound.index', ['tab'=>$key]) }}">
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
              <td>
                <span class="badge badge-{{ $it->status }}">{{ strtoupper($it->status) }}</span>
              </td>
              <td>
                {{-- Aksi berdasarkan status --}}
                @if($it->status === 'draft')
                  <form action="{{ route('wms.inbound.send', $it) }}" method="POST" style="display:inline">
                    @csrf
                    <button class="btn-sm">Kirim</button>
                  </form>
                  <form action="{{ route('wms.inbound.cancel', $it) }}" method="POST" style="display:inline" class="cancel-form" data-inbound-id="{{ $it->id_inbound }}">
                    @csrf @method('DELETE')
                    <button type="button" class="btn-sm btn-danger btn-cancel">Batalkan</button>
                  </form>

                  <a class="btn-sm" href="{{ route('wms.inbound.edit', $it) }}">Edit</a>
                @elseif($it->status === 'sent')
                  -
                @elseif($it->status === 'accept')
                  -
                @elseif($it->status === 'denied')
                  <a class="btn-sm" href="{{ route('wms.inbound.edit', $it) }}">Edit</a>
                @else
                  <a class="btn-sm" href="{{ route('wms.inbound.show', $it) }}">Detail</a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" style="text-align:center;color:#6b7280">Belum ada inbound.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div style="margin-top:12px">{{ $items->links() }}</div>

  </main>
</div>
{{-- Modal Konfirmasi Batalkan --}}
<div id="cancelModal" class="modal-backdrop hidden" aria-modal="true" role="dialog">
  <div class="modal">
    <h3>Batalkan draft inbound #<span id="cancelInboundId"></span>?</h3>
    <p>Tindakan ini <strong>tidak bisa dibatalkan</strong>.</p>
    <div class="modal-actions">
      <button type="button" class="btn" id="cancelNo">Tidak</button>
      <button type="button" class="btn-danger" id="cancelYes">Ya, batalkan</button>
    </div>
  </div>
</div>

{{-- Toast Notifikasi --}}
<div id="toast" class="toast hidden">
  <span id="toastMsg"></span>
</div>

</body>
</html>
<script>
(function(){
  const modal = document.getElementById('cancelModal');
  const cancelNo = document.getElementById('cancelNo');
  const cancelYes = document.getElementById('cancelYes');
  const cancelInboundIdEl = document.getElementById('cancelInboundId');

  const toast = document.getElementById('toast');
  const toastMsg = document.getElementById('toastMsg');

  let pendingForm = null;

  function openModal(form){
    pendingForm = form;
    const id = form?.dataset?.inboundId || '';
    cancelInboundIdEl.textContent = id;
    modal.classList.remove('hidden');
  }
  function closeModal(){
    modal.classList.add('hidden');
    pendingForm = null;
  }

  function showToast(msg, type='success', timeout=2200){
    toastMsg.textContent = msg;
    toast.className = 'toast show '+type;
    setTimeout(()=>{ toast.classList.remove('show'); }, timeout);
  }

  // Tangkap klik tombol "Batalkan"
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-cancel');
    if (!btn) return;
    e.preventDefault();
    const form = btn.closest('form.cancel-form');
    if (form) openModal(form);
  });

  // Tindakan modal
  cancelNo?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeModal(); });

  cancelYes?.addEventListener('click', () => {
    if (!pendingForm) return;
    // beri efek "processing" singkat
    showToast('Membatalkan draft...', 'error', 900);
    // submit form DELETE
    pendingForm.submit();
  });

  // Flash notif dari server (session('ok')) → tampilkan sebagai toast
  @if(session('ok'))
    showToast(@json(session('ok')), 'success');
  @endif
  @if($errors->any())
    showToast(@json($errors->first()), 'error');
  @endif
})();
</script>
