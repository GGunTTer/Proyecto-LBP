<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $title ?? 'Resultado' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    html,body{height:100%;margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
    .wrap{min-height:100%;display:flex;align-items:center;justify-content:center;padding:24px}
    .card{max-width:520px;width:100%;border:1px solid #e5e7eb;border-radius:16px;padding:20px;box-shadow:0 10px 25px rgba(0,0,0,.08)}
    .ok{color:#065f46;background:#ecfdf5;border-color:#a7f3d0}
    .err{color:#7f1d1d;background:#fef2f2;border-color:#fecaca}
    .muted{color:#6b7280;font-size:14px;margin-top:8px}
    .btn{margin-top:14px;display:inline-block;padding:10px 14px;border-radius:10px;border:1px solid #e5e7eb;text-decoration:none}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card {{ ($success ?? false) ? 'ok' : 'err' }}">
    <h2 style="margin:0 0 6px 0">{{ $title ?? '' }}</h2>
    <p style="margin:0">{{ $body ?? '' }}</p>
    <p class="muted" id="status">Preparando notificación…</p>
    <a class="btn" id="openLink" href="{{ $clickUrl ?? 'about:blank' }}" target="_self" style="display:none">Abrir detalle</a>
  </div>
</div>

<script>
(function(){
  const title     = @json($title ?? 'Notificación');
  const body      = {!! json_encode($body ?? '') !!};
  const icon      = @json($icon ?? null);
  const timeoutMs = Number(@json($timeoutMs ?? 15000));
  const clickUrl  = @json($clickUrl ?? 'about:blank');
  const vibrate   = @json($vibrate ?? []);
  const statusEl  = document.getElementById('status');
  const openBtn   = document.getElementById('openLink');

  function showStatus(msg){ if(statusEl) statusEl.textContent = msg; }

  
  function closePopup() {
    try { window.close(); } catch(e) {}
    setTimeout(() => {
      try { window.open('', '_self'); window.close(); } catch(e) {}
    }, 100);
  }

  
  let closeTimer = null;
  function scheduleClose(ms){
    if (closeTimer) clearTimeout(closeTimer);
    closeTimer = setTimeout(closePopup, Math.max(500, ms)); 
  }

  if (!('Notification' in window)) {
    showStatus('Este navegador no soporta notificaciones. Cerrando…');
    scheduleClose(1200);
    return;
  }

  Notification.requestPermission().then(function (perm) {
    if (perm !== 'granted') {
      showStatus('Permiso denegado. Cerrando…');
      scheduleClose(1200);
      return;
    }

    try {
      const n = new Notification(title, { body: body, icon: icon });
      showStatus('Notificación mostrada.');

      if (Array.isArray(vibrate) && navigator.vibrate) {
        navigator.vibrate(vibrate);
      }

     
      n.onclick = function () {
        try { window.location.href = clickUrl; } catch(e) {}
        try { n.close(); } catch(e) {}
        scheduleClose(200); 
      };

      
      const notifAuto = Math.max(3000, Math.min(Number(timeoutMs || 15000), 60000));
      setTimeout(() => { try { n.close(); } catch(e) {} }, notifAuto);

      
      scheduleClose(Number(displayMs || 4000));

    } catch (e) {
      showStatus('No se pudo crear la notificación. Cerrando…');
      scheduleClose(1200);
    }
  });
})();
</script>
</body>
</html>
