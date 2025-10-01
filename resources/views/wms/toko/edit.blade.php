{{-- resources/views/wms/toko/edit.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>WMS • Atur Toko</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  <style>
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:900px){.form-grid{grid-template-columns:1fr}}
    .field{display:flex;flex-direction:column;gap:6px}
    .row-span-2{grid-column:1/-1}

    .label-required{display:inline-flex;align-items:baseline;gap:6px}

    .input, .select, textarea.input{
      width:40vw;max-width:720px;padding:10px;
      border:2px solid #0099ff;border-radius:8px;background:#fff;
    }
    .field .input[readonly]{background:#f3f4f6!important;color:#6b7280!important;cursor:not-allowed}
    .help{font-size:12px;color:#6b7280}

    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-bottom:12px}
    .section-title{margin:0 0 8px 0;font-size:16px;font-weight:700;color:#0f172a}
    .section-desc{margin:0 0 8px 0;font-size:12px;color:#6b7280}

    .logo-wrap{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    .logo-prev{display:block;height:64px;max-width:100%;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;background:#fff;padding:6px}

    .sticky-cta{position:sticky;bottom:0;margin-top:12px}
    .cta-card{display:flex;justify-content:flex-end;gap:8px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:8px}
    .btn{padding:10px 14px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;cursor:pointer}
    .btn-primary{background:#111827;color:#fff;border-color:#111827}
    .btn-danger{background:#fee2e2;border-color:#fecaca;color:#7f1d1d}
    .btn-danger:hover{background:#fecaca}
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
      <a class="menu-item" href="{{ route('wms.dashboard') }}">Dashboard</a>
      <a class="menu-item" href="{{ route('wms.transaksi.index') }}">Transaksi</a>
      <a class="menu-item" href="{{ route('wms.inbound.index') }}">Inbound</a>
      <a class="menu-item" href="{{ route('wms.stock.index') }}">Stock</a>
      <a class="menu-item" href="{{ route('wms.produk.index') }}">Produk</a>
      <a class="menu-item active" href="{{ route('wms.toko.edit') }}">Atur Toko</a>
      <a class="menu-item" href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>
      <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
        @csrf <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Atur Toko</h1>
      <div></div>
    </header>

    @if(session('ok')) <div class="alert-ok">{{ session('ok') }}</div> @endif
    @if(session('err')) <div class="alert-error">{{ session('err') }}</div> @endif
    @if($errors->any())
      <div class="alert-error">
        <ul style="margin:0;padding-left:18px">
          @foreach ($errors->all() as $msg) <li>{{ $msg }}</li> @endforeach
        </ul>
      </div>
    @endif

    {{-- FORM UTAMA (UPDATE) --}}
    <form id="tokoUpdateForm" method="POST" action="{{ route('wms.toko.update') }}" enctype="multipart/form-data">
      @csrf @method('PUT')

      {{-- ====== Identitas & Branding ====== --}}
      <div class="card">
        <h3 class="section-title">Identitas & Branding</h3>
        <div class="form-grid">
          <div class="field">
            <label class="label-required">Nama Toko*</label>
            <input class="input" type="text" name="nama_toko" value="{{ old('nama_toko', $toko->nama_toko ?? '') }}" required>
          </div>

          <div class="field row-span-2">
            <label>Logo Toko <span class="help">(jpg, png, webp, svg • maks 2MB)</span></label>
            @php $logo = $toko->logo_path ?? null; @endphp
            @if($logo)
              <div class="logo-wrap">
                <img class="logo-prev" src="{{ asset('storage/'.$logo) }}" alt="Logo toko">
                {{-- Tombol submit ke form terpisah (bukan form utama) --}}
                <button type="submit" class="btn btn-danger"
                        form="logoDelForm"
                        onclick="return confirm('Hapus logo toko sekarang?');">
                  Hapus Logo
                </button>
              </div>
              <div class="help" style="margin-top:6px">Unggah file baru untuk mengganti logo.</div>
            @endif
            <input class="input" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.svg">
          </div>
        </div>
      </div>

      {{-- ====== Alamat Toko ====== --}}
      <div class="card">
        <h3 class="section-title">Alamat Toko</h3>
        <div class="form-grid">
          <div class="field row-span-2">
            <label>Alamat</label>
            <textarea class="input" name="alamat" rows="2">{{ old('alamat', $toko->alamat ?? '') }}</textarea>
          </div>

          <div class="field">
            <label>Kota/Kabupaten</label>
            <input class="input" type="text" name="kota" value="{{ old('kota', $toko->kota ?? '') }}">
          </div>

          <div class="field">
            <label>Provinsi</label>
            <input class="input" type="text" name="provinsi" value="{{ old('provinsi', $toko->provinsi ?? '') }}">
          </div>

          <div class="field">
            <label>Kode Pos</label>
            <input class="input" type="text" name="kode_pos" value="{{ old('kode_pos', $toko->kode_pos ?? '') }}">
          </div>

          <div class="field">
            <label class="label-required">Negara*</label>
            <input class="input" type="text" name="negara" value="{{ old('negara', $toko->negara ?? 'Indonesia') }}" required>
          </div>
        </div>
      </div>

      {{-- ====== Kontak ====== --}}
      <div class="card">
        <h3 class="section-title">Kontak</h3>
        <div class="form-grid">
          <div class="field">
            <label>No. Telepon</label>
            <input class="input" type="text" name="no_telp" value="{{ old('no_telp', $toko->no_telp ?? '') }}">
          </div>

          <div class="field">
            <label>Email</label>
            <input class="input" type="email" name="email" value="{{ old('email', $toko->email ?? '') }}">
          </div>

          <div class="field">
            <label>Website</label>
            <input class="input" type="text" name="website" value="{{ old('website', $toko->website ?? '') }}">
          </div>
        </div>
      </div>

      {{-- ====== Pengaturan ====== --}}
      <div class="card">
        <h3 class="section-title">Pengaturan</h3>
        <div class="form-grid">
          <div class="field">
            <label class="label-required">Timezone*</label>
            <input class="input" type="text" name="timezone"
                   value="{{ old('timezone', $toko->timezone ?? 'Asia/Jakarta') }}"
                   readonly aria-readonly="true">
          </div>

          <div class="field">
            <label class="label-required">Mata Uang*</label>
            <input class="input" type="text" name="currency"
                   value="{{ old('currency', $toko->currency ?? 'IDR') }}"
                   readonly aria-readonly="true">
          </div>

          <div class="field">
            <label class="label-required">Prefix Invoice*</label>
            <input class="input" type="text" name="invoice_prefix" value="{{ old('invoice_prefix', $toko->invoice_prefix ?? 'INV') }}" required>
            <div class="help">Disiapkan untuk penomoran invoice otomatis (belum dipakai sekarang).</div>
          </div>
        </div>
      </div>

      {{-- ====== Rekening Bank (Opsional) ====== --}}
      <div class="card">
        <h3 class="section-title">Rekening Bank <span class="section-desc">(opsional)</span></h3>
        <div class="form-grid">
          <div class="field">
            <label>Nama Bank</label>
            <input class="input" type="text" name="bank_name" value="{{ old('bank_name', $toko->bank_name ?? '') }}" placeholder="Contoh: BCA / BRI / Mandiri">
          </div>

          <div class="field">
            <label>No. Rekening</label>
            <input class="input" type="text" name="bank_account_no" value="{{ old('bank_account_no', $toko->bank_account_no ?? '') }}" placeholder="1234567890">
          </div>

          <div class="field">
            <label>Atas Nama Rekening</label>
            <input class="input" type="text" name="bank_account_name" value="{{ old('bank_account_name', $toko->bank_account_name ?? '') }}" placeholder="Nama Pemilik Rekening">
          </div>
        </div>
      </div>

      <div class="sticky-cta">
        <div class="cta-card">
          <a class="btn" href="{{ route('wms.dashboard') }}">Batal</a>
          <button class="btn-primary" type="submit">Simpan</button>
        </div>
      </div>
    </form>

    {{-- FORM TERPISAH (HAPUS LOGO) — tidak berada di dalam form utama --}}
    <form id="logoDelForm" method="POST" action="{{ route('wms.toko.logo.destroy') }}" style="display:none">
      @csrf
      @method('DELETE')
    </form>

  </main>
</div>
</body>
</html>
