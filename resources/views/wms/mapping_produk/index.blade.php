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
        .map-list-card,
        .produk-list-card{
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

        .card-header-line{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
        }

        .btn-toggle{
            border:none;
            background:#f1f5f9;
            border-radius:999px;
            padding:4px 10px;
            font-size:12px;
            cursor:pointer;
            color:#0f172a;
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

        .help{
            font-size:12px;
            color:#6b7280;
            margin-top:2px;
        }

        .produk-search-row{
            display:flex;
            gap:8px;
            align-items:center;
            margin-bottom:8px;
            flex-wrap:wrap;
        }
        .produk-search-row .grow{
            flex:1 1 180px;
        }
        .produk-table{
            width:100%;
            border-spacing:0;
            font-size:13px;
        }
        .produk-table thead th{
            position:sticky;top:0;
            background:#f8fafc;
            border-bottom:1px solid #e5e7eb;
            padding:6px 8px;
            font-weight:600;
            color:#64748b;
            white-space:nowrap;
        }
        .produk-table tbody td{
            padding:6px 8px;
            border-bottom:1px solid #f1f5f9;
            vertical-align:top;
        }
        .produk-table tbody tr:hover{
            background:#f9fafb;
        }
        .btn-sm{
            padding:4px 8px;
            font-size:12px;
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

        {{-- FORM KONTEXT SHOPEE (bisa hide) --}}
        <div class="card map-form-card">
            <div class="card-header card-header-line">
                <span>Tambah Mapping Produk Shopee → Produk Internal</span>
                <button type="button" class="btn-toggle" id="toggleFormBtn">Sembunyikan</button>
            </div>
            <div class="card-body" id="mapFormBody">
                {{-- form ini hanya untuk set konteks shop_id, item_id, model_id --}}
                <form action="{{ route('mapping_produk.index') }}" method="GET">
                    <div class="map-form-grid">
                        {{-- Row 1 --}}
                        <div class="col-6">
                            <label class="form-label">Marketplace</label>
                            <input type="text" class="input" value="Shopee" disabled>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Shop ID</label>
                            <input type="number" name="shop_id" class="input"
                                   value="{{ old('shop_id', $shopId) }}">
                            <div class="help">Shop ID toko Shopee yang digunakan.</div>
                        </div>

                        {{-- Row 2 --}}
                        <div class="col-6">
                            <label class="form-label">Shopee Item ID</label>
                            <input type="number" name="marketplace_item_id" class="input"
                                   value="{{ old('marketplace_item_id', $itemId) }}">
                            <div class="help">
                                ID item dari Shopee (field <code>item_id</code> di API).<br>
                                Nama item: <strong>{{ request()->query('shopee_item_name', '—') }}</strong>
                            </div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Shopee Model ID</label>
                            <input type="number" name="marketplace_model_id" class="input"
                                   value="{{ old('marketplace_model_id', $modelId) }}">
                            <div class="help">
                                Isi <strong>model_id</strong> bila produk punya varian (0 / kosong = non varian).<br>
                                Nama model: <strong>{{ request()->query('shopee_model_name', '—') }}</strong>
                            </div>
                        </div>

                        <div class="col-12">
                            <button class="btn-primary" type="submit">Terapkan Item & Shop</button>
                            <div class="help">
                                Setelah mengisi Item ID & Model ID, pilih produk internal dari daftar di bawah dan klik tombol <strong>Map</strong>.
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- DAFTAR PRODUK INTERNAL + TOMBOL MAP/UNMAP --}}
        <div class="card produk-list-card">
            <div class="card-header card-header-line">
                <span>Daftar Produk Internal (Mapping per Item Shopee)</span>
            </div>
            <div class="card-body">
                <div class="produk-search-row">
                    <form class="produk-search-row" method="GET" action="{{ route('mapping_produk.index') }}">
                        <input type="hidden" name="shop_id" value="{{ $shopId }}">
                        <input type="hidden" name="marketplace_item_id" value="{{ $itemId }}">
                        <input type="hidden" name="marketplace_model_id" value="{{ $modelId }}">

                        <div class="grow">
                            <input type="text"
                                   name="q"
                                   class="input"
                                   placeholder="Cari nama produk / SKU…"
                                   value="{{ $q }}">
                        </div>
                        <button type="submit" class="btn-ghost">Cari</button>
                    </form>
                </div>

                @if(!$itemId)
                    <div class="help" style="margin-bottom:6px;">
                        Isi dulu <strong>Shop ID</strong> dan <strong>Shopee Item ID</strong> di atas untuk mengaktifkan tombol Map/Unmap.
                    </div>
                @endif

                <div class="map-table-wrap">
                    <table class="produk-table">
                        <thead>
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th>Nama Produk</th>
                            <th>SKU</th>
                            <th style="width:130px;">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($produkPage as $p)
                            <tr>
                                <td class="num">{{ $p->id_produk }}</td>
                                <td>{{ $p->nama_produk }}</td>
                                <td>{{ $p->sku ?: '—' }}</td>
                                <td>
                                    @if($itemId)
                                        @if($activeMap && $activeMap->id_produk === $p->id_produk)
                                            {{-- tombol UNMAP --}}
                                            <form action="{{ route('mapping_produk.destroy', $activeMap->id) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Yakin unmapping item ini dari produk ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    Unmap
                                                </button>
                                            </form>
                                        @else
                                            {{-- tombol MAP --}}
                                            <form action="{{ route('mapping_produk.store') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="shop_id" value="{{ $shopId }}">
                                                <input type="hidden" name="marketplace_item_id" value="{{ $itemId }}">
                                                <input type="hidden" name="marketplace_model_id" value="{{ $modelId }}">
                                                <input type="hidden" name="id_produk" value="{{ $p->id_produk }}">
                                                <button type="submit" class="btn-primary btn-sm">
                                                    Map
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="help">Isi Item ID dulu</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center;color:#6b7280;padding:12px;">
                                    Tidak ada produk ditemukan.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($produkPage->hasPages())
                    <div class="card-footer" style="margin-top:8px;">
                        {{ $produkPage->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- DAFTAR MAPPING (bisa hide) --}}
        <div class="card map-list-card">
            <div class="card-header card-header-line">
                <span>Daftar Mapping ({{ $mappings->total() }} data)</span>
                <button type="button" class="btn-toggle" id="toggleMapListBtn">Sembunyikan</button>
            </div>
            <div class="card-body map-table-wrap" id="mapListBody">
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
    const formBody = document.getElementById('mapFormBody');
    const formBtn  = document.getElementById('toggleFormBtn');
    const mapBody  = document.getElementById('mapListBody');
    const mapBtn   = document.getElementById('toggleMapListBtn');

    const FORM_KEY = 'mappingProduk_form_collapsed';
    const LIST_KEY = 'mappingProduk_list_collapsed';

    function applyState(el, btn, key, labelShow, labelHide){
        const collapsed = localStorage.getItem(key) === '1';
        if (collapsed){
            el.style.display = 'none';
            btn.textContent = labelShow;
        } else {
            el.style.display = '';
            btn.textContent = labelHide;
        }
    }

    applyState(formBody, formBtn, FORM_KEY, 'Tampilkan', 'Sembunyikan');
    applyState(mapBody, mapBtn, LIST_KEY, 'Tampilkan', 'Sembunyikan');

    formBtn.addEventListener('click', function(){
        const collapsed = formBody.style.display === 'none';
        formBody.style.display = collapsed ? '' : 'none';
        localStorage.setItem(FORM_KEY, collapsed ? '0' : '1');
        formBtn.textContent = collapsed ? 'Sembunyikan' : 'Tampilkan';
    });

    mapBtn.addEventListener('click', function(){
        const collapsed = mapBody.style.display === 'none';
        mapBody.style.display = collapsed ? '' : 'none';
        localStorage.setItem(LIST_KEY, collapsed ? '0' : '1');
        mapBtn.textContent = collapsed ? 'Sembunyikan' : 'Tampilkan';
    });
});
</script>
</body>
</html>
