@vite('resources/css/partials-alerts.css')

<div class="alerts-stack" id="alertsStack">
  @if (session('ok'))
    <div class="alert alert-ok" role="status">
      <button type="button" class="alert-close" aria-label="Tutup">✕</button>
      <div class="ttl">Berhasil</div>
      <div class="msg">{{ session('ok') }}</div>
    </div>
  @endif

  @if (session('err'))
    <div class="alert alert-error" role="alert">
      <button type="button" class="alert-close" aria-label="Tutup">✕</button>
      <div class="ttl">Gagal</div>
      <div class="msg">{!! nl2br(e(session('err'))) !!}</div>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-error" role="alert" id="firstError">
      <button type="button" class="alert-close" aria-label="Tutup">✕</button>
      <div class="ttl">Ada yang perlu dicek</div>
      <ul class="msg" style="margin:0; padding-left:18px">
        @foreach ($errors->all() as $msg)
          <li>{!! nl2br(e($msg)) !!}</li>
        @endforeach
      </ul>
    </div>
  @endif
</div>

<script>
  // Tutup alert
  document.querySelectorAll('.alert-close').forEach(btn=>{
    btn.addEventListener('click', ()=> btn.closest('.alert')?.remove());
  });

  // Auto-scroll ke error pertama & fokus input invalid
  (function(){
    const err = document.getElementById('firstError');
    if(!err) return;
    // scroll top
    err.scrollIntoView({behavior:'smooth', block:'start'});
    // fokus input .is-invalid pertama
    const bad = document.querySelector('.is-invalid');
    if(bad){ try{ bad.focus({preventScroll:true}); }catch{} }
  })();
</script>
