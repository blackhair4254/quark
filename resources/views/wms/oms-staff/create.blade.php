<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>WMS • Tambah Staff OMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/wms-produk.css')
  @vite('resources/css/wms-staffoms.css')
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
      <a class="menu-item" href="{{ route('wms.produk.index') }}">Produk</a>
      <a class="menu-item active" href="{{ route('wms.oms-staff.index') }}">Akun Staff OMS</a>
      <form method="POST" action="{{ route('wms.logout') }}" class="logout-form">
        @csrf <button type="submit" class="menu-item logout">Logout</button>
      </form>
    </nav>
  </aside>

  <main class="dash-main">
    <header class="main-header">
      <h1>Tambah Staff OMS</h1>
      <a class="btn btn-ghost" href="{{ route('wms.oms-staff.index') }}">← Kembali</a>
    </header>

    @if($errors->any())
      <div class="alert-error" style="margin-bottom:12px">{{ $errors->first() }}</div>
    @endif

    <div class="form-card">
      <form method="POST" action="{{ route('wms.oms-staff.store') }}" id="staffForm">
        @csrf
        <div class="field">
          <label for="email_pengguna">Email*</label>
          <input id="email_pengguna" type="email" name="email_pengguna" required
                  value="{{ old('email_pengguna') }}"
                  class="@error('email_pengguna') invalid @enderror">
          @error('email_pengguna') <div class="hint" style="color:#ef4444">{{ $message }}</div> @enderror
        </div>

        <div class="grid-2">
          <div class="field">
            <label for="nama_pengguna">Nama*</label>
            <input id="nama_pengguna" type="text" name="nama_pengguna" required
                   value="{{ old('nama_pengguna') }}"
                   class="@error('nama_pengguna') invalid @enderror">
            @error('nama_pengguna') <div class="hint" style="color:#ef4444">{{ $message }}</div> @enderror
          </div>

          <div class="field" style="grid-column:1 / -1">
            <label for="password">Password*</label>
            <div class="pwd-wrap">
              <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"
                     placeholder="Minimal 8 karakter"
                     class="@error('password') invalid @enderror">
              <button type="button" class="pwd-toggle" id="togglePwd">Lihat</button>
            </div>
            <div class="meter" id="pwdMeter"><span></span></div>
            <div class="hint">Gunakan kombinasi huruf & angka. Minimal 8 karakter.</div>
            @error('password') <div class="hint" style="color:#ef4444">{{ $message }}</div> @enderror
          </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:16px">
          <a class="btn" href="{{ route('wms.oms-staff.index') }}">Batal</a>
          <button class="btn-primary" type="submit" id="submitBtn">Simpan</button>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>
<script>
  // show/hide password
  const ip = document.getElementById('password');
  const tg = document.getElementById('togglePwd');
  tg?.addEventListener('click', ()=>{
    const t = ip.type === 'password' ? 'text' : 'password';
    ip.type = t; tg.textContent = t === 'password' ? 'Lihat' : 'Sembunyi';
  });

  // strength meter
  const meter = document.getElementById('pwdMeter');
  function z(v){
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Za-z]/.test(v) && /\d/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;
    meter.classList.remove('weak','medium','strong');
    meter.classList.add(score>=3 ? 'strong' : score===2 ? 'medium' : 'weak');
  }
  ip?.addEventListener('input', e => z(e.target.value||''));
  z('');

  // prevent double submit
  const f = document.getElementById('staffForm');
  const sb = document.getElementById('submitBtn');
  f?.addEventListener('submit', ()=>{ sb.disabled = true; });
</script>
