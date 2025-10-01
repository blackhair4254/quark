{{-- resources/views/oms-login.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login OMS</title>
  @vite('resources/css/wms-login.css')
</head>
<body>
  <div class="login-header">
    <h1>Warehouse Management System</h1>
    <a href="{{ url('/') }}" class="home-link">&lt; Home</a>
  </div>

  <div class="login-wrapper">
    <div class="login-container">
      <div class="login-box">
        <img src="{{ asset('images/quark.svg') }}" alt="Logo Q" class="logo">
        <h2>OMS Account</h2>
        <p class="subtitle">login sebagai staff gudang</p>

        @if($errors->any())
          <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('oms.login.post') }}" id="loginForm" novalidate>
          @csrf

          <input
            type="email"
            name="email"
            placeholder="email"
            value="{{ old('email') }}"
            autocomplete="username"
            class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
            required
            autofocus>

          <div class="pwd-field">
            <input
              type="password"
              name="password"
              placeholder="password"
              autocomplete="current-password"
              class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
              required>

            <button type="button" class="pwd-toggle" aria-label="Tampilkan/Sembunyikan password" aria-pressed="false">
              <!-- eye -->
              <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <!-- eye-off -->
              <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 3l18 18"/>
                <path d="M10.58 10.58A3 3 0 0 0 12 15a3 3 0 0 0 2.42-4.42"/>
                <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.7 18.7 0 0 1-4.07 5.66M6.61 6.61A18.7 18.7 0 0 0 1 12s4 8 11 8a10.94 10.94 0 0 0 2.12-.24"/>
              </svg>
            </button>
          </div>

          <button type="submit" class="submit-btn" id="submitBtn" aria-label="Masuk">
            <img src="{{ asset('images/panah.svg') }}" alt="panah" class="panah">
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // aktifkan toggle & cegah double submit
    (function(){
      const form = document.getElementById('loginForm');
      const submitBtn = document.getElementById('submitBtn');
      form?.addEventListener('submit', ()=>{ submitBtn.disabled = true; });

      document.querySelectorAll('.pwd-field').forEach(wrap=>{
        const input = wrap.querySelector('input');
        const btn = wrap.querySelector('.pwd-toggle');

        btn.addEventListener('click', ()=>{
          const reveal = input.type === 'password';
          input.type = reveal ? 'text' : 'password';
          wrap.classList.toggle('reveal', reveal);
          btn.setAttribute('aria-pressed', String(reveal));
          // jaga posisi kursor (kalau fokus)
          try{
            const pos = input.selectionStart ?? input.value.length;
            input.setSelectionRange(pos, pos);
            input.focus();
          }catch{}
        });

        // Enter di tombol jangan submit
        btn.addEventListener('keydown', e=>{ if(e.key==='Enter') e.preventDefault(); });
      });
    })();
  </script>
</body>
</html>
