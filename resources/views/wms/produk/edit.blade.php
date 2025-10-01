<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Produk • WMS</title>
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
      <h1>Edit Produk</h1>
      <a href="{{ route('wms.produk.index') }}" class="btn">← Kembali</a>
    </header>

    @if ($errors->any())
      <div class="alert-err">
        <ul style="margin:0;padding-left:18px">
          @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('wms.produk.update', $produk) }}" method="POST" enctype="multipart/form-data" class="card form-grid">
      @csrf @method('PUT')

      <label>Nama Produk* <input type="text" name="nama_produk" value="{{ old('nama_produk',$produk->nama_produk) }}" required></label>
      <label>SKU <input type="text" name="sku" value="{{ old('sku',$produk->sku) }}"></label>
      <label>Category* <input type="text" name="category" value="{{ old('category',$produk->category) }}" required></label>
      <label>Deskripsi* <textarea name="deskripsi" rows="4" required>{{ old('deskripsi',$produk->deskripsi) }}</textarea></label>
      <label>Berat* (gram) <input type="number" step="1" min="0" name="berat" value="{{ old('berat',$produk->berat) }}" required></label>
      <label>Harga Beli* <input type="number" step="0.01" min="0" name="harga_beli" value="{{ old('harga_beli',$produk->harga_beli) }}" required></label>
      <label>Harga Jual* <input type="number" step="0.01" min="0" name="harga_jual" value="{{ old('harga_jual',$produk->harga_jual) }}" required></label>

      <label>Foto (opsional)
        <input type="file" name="foto" accept="image/*">
        @if($produk->foto)
          <div style="margin-top:8px">
            <img src="{{ asset('storage/'.$produk->foto) }}" alt="" style="width:100px;height:100px;object-fit:cover;border-radius:8px">
          </div>
        @endif
      </label>

      <div class="form-actions">
        <button class="btn-primary" type="submit">Simpan Perubahan</button>
      </div>
    </form>
  </main>
</div>
</body>
</html>
