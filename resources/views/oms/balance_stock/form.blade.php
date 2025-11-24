<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>OMS • {{ $mode === 'create' ? 'Ajukan Balance Stock' : 'Edit Balance Stock' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="{{ asset('quark.svg') }}">
  <link rel="icon" type="image/x-icon" href="{{ asset('quark.svg') }}">
  <link rel="apple-touch-icon" href="{{ asset('quark.svg') }}">
  @vite('resources/css/wms-produk.css')
</head>
<body class="dash-body">
<div class="dash-layout">

  {{-- SIDEBAR OMS --}}
  <aside class="dash-sidebar">
    <div class="user-block">
      <div class="user-avatar">
        {{ strtoupper(mb_substr(auth()->user()->nama_pengguna ?? 'U',0,1,'UTF-8')) }}
      </div>
      <div class="user-meta">
        <div class="user-name">{{ auth()->user()->nama_pengguna ?? 'User' }}</div>
        <div class="user-email">{{ auth()->user()->email_pengguna ?? '' }}</div>
      </div>
    </div>

    <nav class="dash-menu">
      <a class="menu-item" href="{{ route('oms.inbound.index') }}">Inbound</a>
      <a class="menu-item" href="{{ route('oms.stock.index') }}">Stock</a>
      <a class="menu-item" href="{{ route('oms.transaksi.index') }}">Transaksi OMS</a>
      <a class="menu-item active" href="{{ route('oms.balance_stock.index') }}">Balance Stock</a>
      <form method="POST" action="{{ route('oms.logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  {{-- MAIN --}}
  <main class="dash-main">
    <header class="main-header">
      <h1>{{ $mode === 'create' ? 'Ajukan Balance Stock' : 'Edit Balance Stock' }}</h1>
    </header>

    @if($errors->any())
      <div class="alert-error">{{ $errors->first() }}</div>
    @endif
    @if(session('err'))
      <div class="alert-error">{{ session('err') }}</div>
    @endif
    @if(session('ok'))
      <div class="alert-ok">{{ session('ok') }}</div>
    @endif

    {{-- FORM SEARCH PRODUK (GET) --}}
    <form method="GET" action="{{ url()->current() }}" class="filters" style="margin-bottom:12px">
      <input type="text"
             name="q"
             value="{{ request('q') }}"
             placeholder="Cari nama / SKU…"
             style="width:260px">
      <button type="submit" class="btn">Cari</button>
    </form>

    <div class="card">
      {{-- FORM BALANCE (POST / PUT) --}}
      <form id="balanceForm"
            data-mode="{{ $mode }}"
            action="{{ $mode === 'create'
                      ? route('oms.balance_stock.store')
                      : route('oms.balance_stock.update', $header) }}"
            method="POST">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div style="margin-bottom:12px;">
          <label>Gudang</label>
          <input type="text"
                 name="gudang"
                 value="{{ old('gudang', $header->gudang ?? '') }}">
        </div>

        <p style="margin-bottom:8px;">
          Pilih produk yang ingin di-balance. Isi stok fisik untuk produk yang berbeda dari sistem.
        </p>

        <div style="overflow-x:auto;">
          <table class="table">
            <thead>
            <tr>
              <th>SKU</th>
              <th>Nama Produk</th>
              <th>Stok Sistem</th>
              <th>Stok Fisik</th>
              <th>Keterangan</th>
            </tr>
            </thead>
            <tbody>
            @foreach($produk as $p)
              @php
                  // stok sistem default
                  $stockSystem = $p->stok ?? ($p->stock->qty ?? 0);

                  // kalau mode edit, ambil dari detail yang sudah ada
                  $existing = null;
                  if($mode === 'edit') {
                      $existing = $details->firstWhere('id_produk', $p->id_produk);
                      if ($existing) {
                          $stockSystem = $existing->qty_system;
                      }
                  }
              @endphp
              <tr data-id-produk="{{ $p->id_produk }}">
                <td>{{ $p->sku ?? '—' }}</td>
                <td>{{ $p->nama_produk }}</td>
                <td>
                  <input type="number" min="0"
                         name="items[{{ $p->id_produk }}][qty_system]"
                         value="{{ old("items.$p->id_produk.qty_system", $stockSystem) }}"
                         class="qty-system input-small js-draft">
                </td>
                <td>
                  <input type="number" min="0"
                         name="items[{{ $p->id_produk }}][qty_fisik]"
                         value="{{ old("items.$p->id_produk.qty_fisik", $existing->qty_fisik ?? '') }}"
                         class="qty-fisik input-small js-draft">
                </td>
                <td>
                  <textarea name="items[{{ $p->id_produk }}][keterangan]"
                            rows="1"
                            class="js-draft">{{ old("items.$p->id_produk.keterangan", $existing->keterangan ?? '') }}</textarea>
                </td>
                <input type="hidden"
                       name="items[{{ $p->id_produk }}][id_produk]"
                       value="{{ $p->id_produk }}">
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>

        @if($mode === 'create')
            <div style="margin-top:8px;">
                {{ $produk->links() }}
            </div>
        @endif


        <div style="margin-top:16px; display:flex; gap:8px;">
            <a href="{{ route('oms.balance_stock.index') }}" class="btn-danger" id="btnCancel">Kembali</a>
            <button type="button" id="btnPreview" class="btn-primary">
                Review &amp; Submit
            </button>
          
        </div>
      </form>
    </div>

    {{-- MODAL KONFIRMASI --}}
    <div id="confirmModal"
         class="modal-backdrop hidden"
         aria-modal="true"
         role="dialog">
      <div class="modal" style="max-width:520px;">
        <h3>Konfirmasi Balance Stock</h3>
        <p>Pastikan kembali jumlah stok fisik berikut:</p>
        <ul id="summaryList"
            style="font-size:13px; max-height:220px; overflow:auto; margin-top:8px;"></ul>

        <p style="margin-top:8px; font-size:12px; color:#555;">
          Tombol <strong>Submit</strong> akan aktif dalam
          <span id="countdown">5</span> detik.
        </p>

        <div class="modal-actions" style="margin-top:12px;">
          <button type="button" id="btnRecheck" class="btn">Cek kembali</button>
          <button type="button" id="btnSubmitFinal" class="btn-primary" disabled>Submit</button>
        </div>
      </div>
    </div>

  </main>
</div>

<script>
    (function () {
    const form        = document.getElementById('balanceForm');
    const mode        = form?.dataset.mode || 'create';
    const btnPreview  = document.getElementById('btnPreview');
    const modal       = document.getElementById('confirmModal');
    const summaryList = document.getElementById('summaryList');
    const btnRecheck  = document.getElementById('btnRecheck');
    const btnSubmit   = document.getElementById('btnSubmitFinal');
    const countdownEl = document.getElementById('countdown');
    const btnCancel   = document.getElementById('btnCancel');

    const STORAGE_KEY = 'oms_balance_stock_draft';

    let countdownTimer = null;

    function openModal() { modal.classList.remove('hidden'); }
    function closeModal() { modal.classList.add('hidden'); }

    // ========= DRAFT (localStorage, hanya untuk CREATE) =========
    function loadDraft() {
        if (mode !== 'create') return {};
        try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : {};
        } catch (e) {
        return {};
        }
    }

    function saveDraft(draft) {
        if (mode !== 'create') return;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
    }

    function clearDraft() {
        if (mode !== 'create') return;
        localStorage.removeItem(STORAGE_KEY);
    }

    function applyDraft() {
        if (!form || mode !== 'create') return;

        const draft = loadDraft();

        // gudang
        if (draft.gudang !== undefined) {
        const gudangInput = form.querySelector('input[name="gudang"]');
        if (gudangInput && !gudangInput.value) {
            gudangInput.value = draft.gudang;
        }
        }

        // item per produk (hanya halaman ini)
        if (draft.items) {
        const rows = form.querySelectorAll('tbody tr[data-id-produk]');
        rows.forEach(tr => {
            const id = tr.dataset.idProduk;
            if (!id || !draft.items[id]) return;
            const rowDraft = draft.items[id];

            const qtyS = tr.querySelector('.qty-system');
            const qtyF = tr.querySelector('.qty-fisik');
            const ket  = tr.querySelector('textarea');

            if (qtyS && rowDraft.qty_system !== undefined && qtyS.value === '') {
            qtyS.value = rowDraft.qty_system;
            }
            if (qtyF && rowDraft.qty_fisik !== undefined && qtyF.value === '') {
            qtyF.value = rowDraft.qty_fisik;
            }
            if (ket && rowDraft.keterangan !== undefined && ket.value === '') {
            ket.value = rowDraft.keterangan;
            }
        });
        }
    }

    function initDraftListeners() {
        if (!form || mode !== 'create') return;

        form.addEventListener('input', function (e) {
        const target = e.target;
        const draft  = loadDraft();

        if (!draft.items) draft.items = {};

        // gudang
        if (target.name === 'gudang') {
            draft.gudang = target.value;
            saveDraft(draft);
            return;
        }

        // field item
        const tr = target.closest('tr[data-id-produk]');
        if (!tr) return;

        const id = tr.dataset.idProduk;
        if (!id) return;

        if (!draft.items[id]) draft.items[id] = {};

        // simpan nama & SKU sekali saja (untuk summary)
        if (!draft.items[id].nama) {
            draft.items[id].nama = tr.children[2]?.innerText.trim();
            draft.items[id].sku  = tr.children[1]?.innerText.trim();
        }

        const qtySInput = tr.querySelector('.qty-system');
        if (qtySInput && draft.items[id].qty_system === undefined) {
            draft.items[id].qty_system = qtySInput.value;
        }

        if (target.classList.contains('qty-system')) {
            draft.items[id].qty_system = target.value;
        } else if (target.classList.contains('qty-fisik')) {
            draft.items[id].qty_fisik = target.value;
            // pastikan qty_system ikut terisi
            if (qtySInput && draft.items[id].qty_system === undefined) {
            draft.items[id].qty_system = qtySInput.value;
            }
        } else if (target.tagName === 'TEXTAREA') {
            draft.items[id].keterangan = target.value;
        }

        saveDraft(draft);
        });
    }

    applyDraft();
    initDraftListeners();

    // ========= REVIEW (pakai semua data di localStorage) =========
    btnPreview?.addEventListener('click', function () {
        summaryList.innerHTML = '';

        if (mode === 'create') {
        const draft = loadDraft();
        if (!draft.items) {
            alert('Isi stok fisik minimal 1 produk yang berbeda dengan stok sistem.');
            return;
        }

        let hasItem = false;
        let idx = 0;

        Object.keys(draft.items).forEach(id => {
            const row = draft.items[id];
            const qtyS = Number(row.qty_system || 0);
            const qtyF = row.qty_fisik === '' || row.qty_fisik === undefined
            ? null
            : Number(row.qty_fisik);

            if (qtyF === null || qtyF === qtyS) return;

            hasItem = true;
            idx++;

            const li = document.createElement('li');
            li.textContent =
            `${idx}. ${row.nama || '(tanpa nama)'} ` +
            `(SKU: ${row.sku || '-'}) — Sistem: ${qtyS}, Fisik: ${qtyF}`;
            summaryList.appendChild(li);
        });

        if (!hasItem) {
            alert('Isi stok fisik minimal 1 produk yang berbeda dengan stok sistem.');
            return;
        }
        } else {
        // MODE EDIT: tetap pakai baris di halaman aktif saja
        const rows = form.querySelectorAll('tbody tr');
        let hasItem = false;

        rows.forEach((tr, idx) => {
            const nama  = tr.children[2]?.innerText.trim();
            const sku   = tr.children[1]?.innerText.trim();
            const qtyS  = tr.querySelector('.qty-system')?.value || 0;
            const qtyF  = tr.querySelector('.qty-fisik')?.value || '';

            if (qtyF === '' || Number(qtyF) === Number(qtyS)) {
            return;
            }

            hasItem = true;
            const li = document.createElement('li');
            li.textContent = `${idx + 1}. ${nama} (SKU: ${sku}) — Sistem: ${qtyS}, Fisik: ${qtyF}`;
            summaryList.appendChild(li);
        });

        if (!hasItem) {
            alert('Isi stok fisik minimal 1 produk yang berbeda dengan stok sistem.');
            return;
        }
        }

        openModal();
        btnSubmit.disabled = true;
        let sec = 5;
        countdownEl.textContent = sec;

        if (countdownTimer) clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
        sec--;
        countdownEl.textContent = sec;
        if (sec <= 0) {
            clearInterval(countdownTimer);
            btnSubmit.disabled = false;
        }
        }, 1000);
    });

    btnRecheck?.addEventListener('click', () => {
        closeModal();
    });

    // ========= SUBMIT (buat hidden input dari draft semua halaman) =========
    btnSubmit?.addEventListener('click', () => {
        btnSubmit.disabled = true;

        if (mode === 'create') {
        const draft = loadDraft();
        if (draft.items) {
            // id produk yang ada di halaman saat ini
            const currentIds = new Set();
            form.querySelectorAll('tbody tr[data-id-produk]').forEach(tr => {
            if (tr.dataset.idProduk) {
                currentIds.add(tr.dataset.idProduk);
            }
            });

            // container hidden input
            let container = document.getElementById('draftPayload');
            if (container) container.remove();
            container = document.createElement('div');
            container.id = 'draftPayload';
            container.style.display = 'none';

            Object.keys(draft.items).forEach(id => {
            const row = draft.items[id];

            const qtyS = Number(row.qty_system || 0);
            if (row.qty_fisik === '' || row.qty_fisik === undefined) return;

            const qtyF = Number(row.qty_fisik);

            // produk yang ada di halaman aktif biar dikirim dari input biasa
            if (currentIds.has(id)) return;

            // siapkan data
            const data = {
                id_produk: id,
                qty_system: qtyS,
                qty_fisik: qtyF,
                keterangan: row.keterangan || '',
            };

            Object.keys(data).forEach(field => {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = `items[${id}][${field}]`;
                input.value = data[field];
                container.appendChild(input);
            });
            });

            form.appendChild(container);
        }

        clearDraft();
        }

        form.submit();
    });

    // Batal -> hapus draft
    btnCancel?.addEventListener('click', () => {
        clearDraft();
    });

    // tutup modal jika klik backdrop
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
    })();
</script>

</body>
</html>
