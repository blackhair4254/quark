<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>OMS • Transaksi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  <style>
    html, body, .dash-layout, .dash-main { overflow-x: hidden; }

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
    .tabs-scroll .tab,
    .tabs-scroll .tab:link,
    .tabs-scroll .tab:visited,
    .tabs-scroll .tab:hover,
    .tabs-scroll .tab:focus,
    .tabs-scroll .tab:active{
      text-decoration:none !important;
    }
    .dot.ready{background:#60a5fa}
    .dot.processing{background:#f59e0b}
    .dot.shipped{background:#a78bfa}
    .dot.done{background:#22c55e}
    .dot.cancel{background:#ef4444}
    .dot.new{background:#94a3b8}

    /* toolbar */
    .list-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px}
    .list-meta{color:#64748b;font-size:12px}
    .right-tools{display:flex;gap:8px;align-items:center}
    .btn-ghost{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;color:#0f172a}

    /* scroll container */
    .table-scroll{
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior-x: contain;
      padding-bottom: 25%;   /* trik supaya dropdown tidak kepotong */
    }

    /* tabel */
    .table.transaksi-table{
      font-size:14px;border-spacing:0;table-layout:auto;
      width:max-content;min-width:100%;
      border-collapse:separate;
    }
    .table.transaksi-table thead th{
      position:sticky;top:0;background:#f8fafc;color:#64748b;
      font-size:12.5px;font-weight:700;letter-spacing:.02em;
      border-bottom:1px solid #e5e7eb;
      padding:8px 10px;z-index:1;white-space:nowrap;
    }
    .table.transaksi-table td {
        position: relative;
        overflow: visible !important;
    }
    .table.transaksi-table tbody tr:hover{ background:#f9fafb; }
    .num{ text-align:right; font-variant-numeric:tabular-nums; }
    .table.transaksi-table.dense thead th,
    .table.transaksi-table.dense td{ padding:6px 8px; }
    .table.transaksi-table.dense{ font-size:13px; }

    @media (max-width: 860px){
      .col-log,.col-resi{display:none}
    }

    .badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700;background:#e5e7eb;color:#111827}
    .badge.new{background:#e5e7eb}
    .badge.ready{background:#dbeafe}
    .badge.processing{background:#fef3c7}
    .badge.shipped{background:#ddd6fe}
    .badge.done{background:#dcfce7}
    .badge.cancel{background:#fee2e2}

    .col-check { width:40px; text-align:center; }
    .link { color: #0f172a; text-decoration: none; font-weight:600; }

    /* === DOT ACTION MENU === */
    .action-menu {
        position: relative;
        display: inline-block;
    }
    .action-trigger {
        border: none;
        background: transparent;
        padding: 0;
        cursor: pointer;
    }
    .dot-btn {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .dot-btn span {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #fff;
        display: block;
        margin: 0 2px;
    }

    .action-dropdown {
        position: absolute;
        right: 0;
        top: 36px;
        background: #111827;
        color: #f9fafb;
        border-radius: 16px;
        padding: 10px;
        min-width: 170px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        display: none;
        flex-direction: column;
        z-index: 9999;
        white-space: nowrap;
    }
    .action-menu.open .action-dropdown { display:flex; }

    .action-item-btn,
    .action-item-link {
        background: #374151;
        color: #fff;
        border-radius: 999px;
        padding: 8px 12px;
        text-align: left;
        text-decoration: none;
        display: block;
        border: none;
        margin: 3px 0;
        font-size: 13px;
        cursor: pointer;
    }
    .action-item-btn:hover,
    .action-item-link:hover { background:#4b5563; }

    .action-separator {
        height: 1px;
        background: #4b5563;
        margin: 4px 0;
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
      <a class="menu-item" href="{{ route('oms.inbound.index') }}">Inbound</a>
      <a class="menu-item" href="{{ route('oms.stock.index') }}">Stock</a>
      <a class="menu-item active" href="{{ route('oms.transaksi.index') }}">Transaksi OMS</a>
      <a class="menu-item" href="{{ route('oms.balance_stock.index') }}">Balance Stock</a>
      <form method="POST" action="{{ route('oms.logout') }}" class="logout-form">
        @csrf <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Transaksi (OMS)</h1>
    </header>

    @if(session('ok')) <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if(session('err')) <div class="alert-error">{{ session('err') }}</div> @endif
    @if($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    {{-- Tabs --}}
    <div class="tabs-wrap">
      <div class="tabs-scroll">
        @foreach($tabs as $key)
          @php $isActive = ($tab === $key); @endphp
          <a class="tab {{ $isActive ? 'active' : '' }}" href="{{ route('oms.transaksi.index', ['tab'=>$key]) }}">
            <span class="dot {{ $key }}"></span>
            <span>{{ strtoupper($key) }}</span>
          </a>
        @endforeach
      </div>
    </div>

    <div class="card">
      <div class="list-toolbar">
        <div class="list-meta">
          Total: <strong>{{ number_format($list->total(),0,',','.') }}</strong> transaksi
          • Halaman {{ $list->currentPage() }} dari {{ $list->lastPage() }}
        </div>
        
        <div id="massPrintTools" class="right-tools" style="display:none;" @if($tab === 'all') hidden @endif >
            <button id="massPrintBtn" type="submit" form="massPrintForm" class="btn-primary">
                Cetak Resi Massal
            </button>

            <button id="clearSelectionBtn" type="button" class="btn-ghost">
                Bersihkan Pilihan
            </button>
        </div>
        
        <div class="right-tools">
          <button id="densityBtn" class="btn-ghost" type="button">Mode Rapat</button>
        </div>
      </div>

      {{-- FORM HANYA UNTUK MASS PRINT --}}
      <form id="massPrintForm" action="{{ route('oms.transaksi.print-resi-mass') }}" method="POST">
        @csrf
        <input type="hidden" name="ids" id="mass_ids">

        <div class="table-scroll">
          <table id="trxTable" class="table transaksi-table">
            <thead>
              <tr>
                @if($tab !== 'all')
                    <th class="col-check"><input type="checkbox" id="pick_all"></th>
                @else
                    <th class="col-check"></th>
                @endif
                <th>No Invoice / Order</th>
                <th>Tgl Transaksi</th>
                <th>Pengirim</th>
                <th class="num">Nilai Total</th>
                <th>Penerima</th>
                <th class="col-log">Jenis Logistik</th>
                <th class="col-resi">No Resi</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
            @forelse($list as $row)
              <tr>
                <td class="col-check">
                    @if($tab !== 'all')
                        <input type="checkbox" name="pick[]" value="{{ $row->id_transaksi }}" class="pick">
                    @endif
                </td>

                <td>
                  <a class="link" href="{{ route('oms.transaksi.show', $row) }}">
                    {{ $row->invoice ?? ($row->order_sn ?? '-') }}
                  </a>
                </td>
                <td>{{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') }}</td>
                <td>{{ $row->pengirim ?? '-' }}</td>
                <td class="num">
                  Rp {{ isset($row->total_nilai) ? number_format($row->total_nilai,0,',','.') : '-' }}
                </td>
                <td>{{ $row->nama_penerima }}</td>
                <td class="col-log">{{ $row->jenis_logistik ?: '—' }}</td>
                <td class="col-resi">{{ $row->no_resi ?: '—' }}</td>
                <td><span class="badge {{ $row->status }}">{{ strtoupper($row->status) }}</span></td>
                <td>
                  {{-- DOT ACTION BUTTON --}}
                  <div class="action-menu">
                    <button type="button" class="action-trigger" aria-label="Aksi untuk transaksi ini">
                      <div class="dot-btn">
                        <span></span><span></span><span></span>
                      </div>
                    </button>

                    <div class="action-dropdown">
                      <a href="{{ route('oms.transaksi.show', $row) }}" class="action-item-link">
                        Lihat Detail
                      </a>

                      @if($row->status === 'processing')
                        <a href="{{ route('oms.transaksi.print-resi', $row) }}"
                           target="_blank"
                           class="action-item-link">
                          Cetak Resi
                        </a>
                      @endif

                      <div class="action-separator"></div>

                      @if($row->status === 'ready')
                        <button type="button"
                                class="action-item-btn js-status-btn"
                                data-url="{{ route('oms.transaksi.to-processing', $row) }}"
                                >
                          To Process
                        </button>
                      @endif

                      @if($row->status === 'processing')
                        <button type="button"
                                class="action-item-btn js-status-btn"
                                data-url="{{ route('oms.transaksi.to-shipped', $row) }}"
                                >
                          To Shipped
                        </button>
                      @endif

                      @if($row->status === 'shipped')
                        <button type="button"
                                class="action-item-btn js-status-btn"
                                data-url="{{ route('oms.transaksi.to-done', $row) }}"
                                >
                          To Done
                        </button>
                      @endif

                      @if(in_array($row->status, ['ready','processing','shipped','done']))
                        <div class="action-separator"></div>
                        <button type="button"
                                class="action-item-btn js-status-btn"
                                data-url="{{ route('oms.transaksi.to-cancel', $row) }}"
                                data-confirm="Yakin batalkan pesanan ini?"
                                style="background:#991b1b">
                          To Cancel
                        </button>
                      @endif
                    </div>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="10" style="text-align:center;color:#6b7280">Tidak ada transaksi.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
        
        <div style="margin-top:10px;">
            {{ $list->links() }}
        </div>
      </form>

      {{-- HIDDEN FORM UNTUK AKSI STATUS --}}
      <form id="singleActionForm" method="POST" style="display:none;">
        @csrf
      </form>
    </div>

  </main>
</div>

<script>
    // mode rapat
    const btn = document.getElementById('densityBtn');
    const table = document.getElementById('trxTable');
    // === Munculkan / sembunyikan tombol mass print ===
    const tabNow = "{{ $tab }}";
    const massTools = document.getElementById('massPrintTools');
    const massPrintBtn = document.getElementById('massPrintBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');

    function refreshMassTools() {
        if (tabNow === 'all') {
            massTools.style.display = 'none';
            return;
        }
        const picks = document.querySelectorAll('input.pick:checked').length;

        const tabAllow = (tabNow === 'processing' || tabNow === 'shipped');
        
        if (picks > 0 && tabAllow) {
            massTools.style.display = 'flex';
        } else {
            massTools.style.display = 'none';
        }
    }

    // event: checkbox individual
    document.querySelectorAll('input.pick').forEach(ch => {
        ch.addEventListener('change', refreshMassTools);
    });

    // event: checkbox select-all
    document.getElementById('pick_all')?.addEventListener('change', refreshMassTools);

    // clear selection
    clearSelectionBtn?.addEventListener('click', function(){
        document.querySelectorAll('input.pick').forEach(ch => ch.checked = false);
        document.getElementById('pick_all').checked = false;
        refreshMassTools();
    });

    // panggil awal
    refreshMassTools();

    btn?.addEventListener('click', ()=>{
        table.classList.toggle('dense');
        btn.textContent = table.classList.contains('dense') ? 'Mode Normal' : 'Mode Rapat';
    });

    // mass print
    document.getElementById('massPrintForm')?.addEventListener('submit', function(e){
        e.preventDefault();
        const picks = Array.from(document.querySelectorAll('input[name="pick[]"]:checked')).map(i => i.value);
        if (picks.length === 0) {
        alert('Pilih minimal 1 transaksi');
        return false;
        }
        document.getElementById('mass_ids').value = picks.join(',');
        this.submit();
    });

    // select all
    const pickAll = document.getElementById('pick_all');
    pickAll?.addEventListener('change', function(){
        document.querySelectorAll('input.pick').forEach(ch => {
            ch.checked = pickAll.checked;
        });

        refreshMassTools(); // 👈 WAJIB! supaya tombol muncul juga saat check-all
    });


    // clear selection
    document.getElementById('clearSelection')?.addEventListener('click', function(){
        document.querySelectorAll('input.pick').forEach(ch => ch.checked = false);
        if (pickAll) pickAll.checked = false;
    });

    // scroll horizontal seperti stock
    document.querySelectorAll('.table-scroll').forEach(sc => {
        sc.addEventListener('wheel', (e) => {
        if (document.querySelector('.action-menu.open')) return;
        const horizontalDelta = e.deltaX || (e.shiftKey ? e.deltaY : 0);
        const isMostlyHorizontal = Math.abs(e.deltaX) >= Math.abs(e.deltaY) || e.shiftKey;
        if (!isMostlyHorizontal || horizontalDelta === 0) return;
        const before = sc.scrollLeft;
        sc.scrollLeft += horizontalDelta;
        if (sc.scrollLeft !== before) e.preventDefault();
        }, { passive: false });
    });

    // toggle action menu
    document.addEventListener('click', function(e){
        document.querySelectorAll('.action-menu.open').forEach(m => {
        if (!m.contains(e.target)) m.classList.remove('open');
        });

        const trigger = e.target.closest('.action-trigger');
        if (trigger) {
        e.stopPropagation();
        const menu = trigger.closest('.action-menu');
        menu.classList.toggle('open');
        }
    });

    // === HANDLE TOMBOL STATUS (To Process / To Shipped / To Done / To Cancel) ===
    const singleForm = document.getElementById('singleActionForm');
    document.addEventListener('click', function(e){
        const btnStatus = e.target.closest('.js-status-btn');
        if (!btnStatus) return;

        e.preventDefault();

        const url = btnStatus.dataset.url;
        const confirmMsg = btnStatus.dataset.confirm;

        if (!url) {
        alert('URL aksi tidak ditemukan.');
        return;
        }

        if (confirmMsg && !window.confirm(confirmMsg)) {
        return;
        }

        singleForm.action = url;
        singleForm.submit();
    });
</script>
</body>
</html>
