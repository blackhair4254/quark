<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>WMS • Mapping Produk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('quark.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('quark.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('quark.svg') }}">
    @vite('resources/css/wms-produk.css')

    <style>
        .map-form-card,
        .map-list-card{
            margin-bottom:16px;
        }

        .map-form-grid{
            display:grid;
            grid-template-columns: repeat(12, minmax(0,1fr));
            gap:12px;
        }
        .col-2{grid-column:span 2 / span 2}
        .col-3{grid-column:span 3 / span 3}
        .col-4{grid-column:span 4 / span 4}
        .col-6{grid-column:span 6 / span 6}
        .col-12{grid-column:span 12 / span 12}

        @media (max-width: 900px){
            .col-2,.col-3,.col-4,.col-6{grid-column:span 12 / span 12}
        }

        label.form-label{
            display:block;
            font-size:13px;
            font-weight:600;
            color:#0f172a;
            margin-bottom:4px;
        }

        .map-table-wrap{
            overflow-x:auto;
        }

        table.map-table{
            width:100%;
            border-spacing:0;
            font-size:13px;
        }
        .map-table thead th{
            position:sticky;top:0;
            background:#f8fafc;
            border-bottom:1px solid #e5e7eb;
            padding:6px 8px;
            font-weight:600;
            color:#64748b;
            white-space:nowrap;
        }
        .map-table tbody td{
            padding:6px 8px;
            border-bottom:1px solid #f1f5f9;
            vertical-align:top;
        }
        .map-table tbody tr:hover{
            background:#f9fafb;
        }
        .num{text-align:right;font-variant-numeric:tabular-nums;}

        .badge-sel{
            display:inline-block;
            padding:2px 6px;
            border-radius:999px;
            background:#e5e7eb;
            font-size:11px;
        }

        /* suggestions dropdown */
        #produk_suggestions{
            position:relative;
            max-height:220px;
            overflow-y:auto;
            z-index:20;
        }

        .alert-status{
            padding:8px 10px;
            border-radius:8px;
            margin-bottom:10px;
            font-size:13px;
        }
        .alert-status.ok{
            background:#dcfce7;
            color:#166534;
        }
        .alert-status.err{
            background:#fee2e2;
            color:#b91c1c;
        }
    </style>
</head>
<body class="dash-body">
<div class="dash-layout">

    {{-- SIDEBAR --}}
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
            <a class="menu-item" href="{{ url('/wms/dashboard') }}">Dashboard</a>
            <a class="menu-item" href="{{ url('/wms/transaksi') }}">Transaksi</a>
            <a class="menu-item" href="{{ url('/wms/inbound') }}">Inbound</a>
            <a class="menu-item" href="{{ url('/wms/stock') }}">Stock</a>
            <a class="menu-item" href="{{ url('/wms/produk') }}">Produk</a>
            <a class="menu-item" href="{{ route('wms.balance_stock.index') }}">Balance Stock</a>
            <a class="menu-item" href="{{ route('wms.toko.edit') }}">Atur Toko</a>
            <a class="menu-item" href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>
            <a class="menu-item active" href="{{ route('mapping_produk.index') }}">Mapping Produk</a>

            <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="menu-item logout">Logout</button>
            </form>
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="dash-main">
        <header class="main-header">
            <h1>Mapping Produk Marketplace</h1>
            <div></div>
        </header>

        {{-- Alert --}}
        @if(session('ok'))
            <div class="alert-status ok">{{ session('ok') }}</div>
        @elseif(session('status'))
            <div class="alert-status ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert-status err">
                <strong>Terjadi kesalahan:</strong>
                <ul style="margin:4px 0 0 18px;padding:0;">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM TAMBAH MAPPING --}}
        <div class="card map-form-card">
            <div class="card-header">
                Tambah Mapping Produk Shopee → Produk Internal
            </div>
            <div class="card-body">
                <form action="{{ route('mapping_produk.store') }}" method="POST">
                    @csrf
                    <div class="map-form-grid">

                        <div class="col-2">
                            <label class="form-label">Marketplace</label>
                            <input type="text" class="input" value="Shopee" disabled>
                        </div>

                        <div class="col-2">
                            <label class="form-label">Shop ID</label>
                            <input type="number" name="shop_id" class="input"
                                   value="{{ old('shop_id', $shopId) }}">
                        </div>

                        <div class="col-3">
                            <label class="form-label">Shopee Item ID</label>
                            <input type="number" name="marketplace_item_id" class="form-control"
                                value="{{ old('marketplace_item_id', request()->query('marketplace_item_id')) }}" required>
                            <div class="help">
                                Wajib diisi. Dari field <code>item_id</code> di API.
                            </div>
                        </div>

                        <div class="col-3">
                            <label class="form-label">Shopee Model ID</label>
                            <input type="number" name="marketplace_model_id" class="form-control"
                                value="{{ old('marketplace_model_id', request()->query('marketplace_model_id')) }}">
                            <div class="help">
                                Isi <strong>model_id</strong> bila produk punya varian.
                                Kosongkan / isi 0 jika tidak punya varian.
                            </div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Produk Internal</label>
                            <div class="input-group">
                                <input type="hidden" name="id_produk" id="id_produk"
                                       value="{{ old('id_produk') }}">

                                <input type="text" id="produk_search" class="input"
                                       placeholder="Ketik nama / SKU produk untuk cari…">
                                <button class="btn" type="button" id="clearProduk">
                                    Clear
                                </button>
                            </div>
                            <div id="produk_info" class="help">
                                @if(old('id_produk'))
                                    @php
                                        $p = $produkList->firstWhere('id_produk', old('id_produk'));
                                    @endphp
                                    @if($p)
                                        Dipilih: {{ $p->nama_produk }} (ID: {{ $p->id_produk }}
                                        @if(!empty($p->sku)) , SKU: {{ $p->sku }} @endif
                                        )
                                    @else
                                        Produk dengan ID {{ old('id_produk') }} tidak ditemukan.
                                    @endif
                                @else
                                    Belum ada produk dipilih.
                                @endif
                            </div>
                            <ul id="produk_suggestions"
                                class="list-group"
                                style="display:none;margin-top:4px;"></ul>
                        </div>

                        <div class="col-12" style="margin-top:4px;">
                            <button class="btn-primary" type="submit">Simpan Mapping</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- DAFTAR MAPPING --}}
        <div class="card map-list-card">
            <div class="card-header">
                Daftar Mapping ({{ $mappings->total() }} data)
            </div>
            <div class="card-body map-table-wrap">
                <table class="map-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Shop ID</th>
                        <th>Item ID</th>
                        <th>Model ID</th>
                        <th>Produk Internal</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($mappings as $map)
                        <tr>
                            <td class="num">{{ $map->id }}</td>
                            <td>{{ $map->shop_id ?? '-' }}</td>
                            <td>{{ $map->marketplace_item_id }}</td>
                            <td>{{ $map->marketplace_model_id ?? '-' }}</td>
                            <td>
                                @if($map->produk)
                                    {{ $map->produk->nama_produk }} <br>
                                    <small class="text-muted">
                                        ID: {{ $map->produk->id_produk }}
                                        @if(!empty($map->produk->sku))
                                            &middot; SKU: {{ $map->produk->sku }}
                                        @endif
                                    </small>
                                @else
                                    <span style="color:#b91c1c;">Produk tidak ditemukan</span>
                                @endif
                            </td>
                            <td>{{ $map->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <form action="{{ route('mapping_produk.destroy', $map->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin hapus mapping ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">
                                        Unmap
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:#6b7280;padding:12px;">
                                Belum ada mapping.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($mappings->hasPages())
                <div class="card-footer">
                    {{ $mappings->links() }}
                </div>
            @endif
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputSearch = document.getElementById('produk_search');
    const ulSuggest   = document.getElementById('produk_suggestions');
    const hiddenId    = document.getElementById('id_produk');
    const info        = document.getElementById('produk_info');
    const clearBtn    = document.getElementById('clearProduk');

    let timer = null;

    function clearSuggestions() {
        ulSuggest.innerHTML = '';
        ulSuggest.style.display = 'none';
    }

    function setProduk(id, label, sku) {
        hiddenId.value = id;
        let text = 'Dipilih: ' + label + ' (ID: ' + id;
        if (sku) text += ', SKU: ' + sku;
        text += ')';
        info.textContent = text;
        clearSuggestions();
    }

    inputSearch.addEventListener('input', function () {
        const q = this.value.trim();
        hiddenId.value = '';
        info.textContent = 'Belum ada produk dipilih.';

        if (timer) clearTimeout(timer);

        if (q.length < 2) {
            clearSuggestions();
            return;
        }

        timer = setTimeout(function () {
            fetch("{{ url('/wms/mapping-produk/search-produk') }}?q=" + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => {
                    ulSuggest.innerHTML = '';
                    if (!data.length) {
                        clearSuggestions();
                        return;
                    }
                    data.forEach(p => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action';
                        let label = p.nama_produk + ' (ID: ' + p.id_produk;
                        if (p.sku) label += ', SKU: ' + p.sku;
                        label += ')';
                        li.textContent = label;
                        li.addEventListener('click', function () {
                            inputSearch.value = p.nama_produk;
                            setProduk(p.id_produk, p.nama_produk, p.sku || null);
                        });
                        ulSuggest.appendChild(li);
                    });
                    ulSuggest.style.display = 'block';
                })
                .catch(err => {
                    console.error(err);
                    clearSuggestions();
                });
        }, 300);
    });

    clearBtn.addEventListener('click', function () {
        inputSearch.value = '';
        hiddenId.value = '';
        info.textContent = 'Belum ada produk dipilih.';
        clearSuggestions();
    });

    document.addEventListener('click', function (e) {
        if (!ulSuggest.contains(e.target) && e.target !== inputSearch) {
            clearSuggestions();
        }
    });
});
</script>
</body>
</html>
