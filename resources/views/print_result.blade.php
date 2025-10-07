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
(function () {
  const title     = @json($title ?? 'Notificación');
  const bodyText  = @json($body ?? '');
  const icon      = @json($icon ?? null);
  const timeoutMs = Number(@json($timeoutMs ?? 15000));
  const clickUrl  = @json($clickUrl ?? 'about:blank');
  const vibrate   = @json($vibrate ?? []);
  const statusEl  = document.getElementById('status');
  const openBtn   = document.getElementById('openLink');

  function showStatus(msg) { if (statusEl) statusEl.textContent = msg; }

  function safeShowOpenBtn() {
    if (openBtn) {
      openBtn.style.display = 'inline-block';
      openBtn.href = clickUrl || 'about:blank';
    }
  }

  function closePopup() {
    try { window.close(); } catch (e) {}
    // fallback para navegadores que exigen _self
    setTimeout(() => {
      try { window.open('', '_self'); window.close(); } catch (e) {}
    }, 100);
  }

  let closeTimer = null;
  function scheduleClose(ms) {
    if (closeTimer) clearTimeout(closeTimer);
    closeTimer = setTimeout(closePopup, Math.max(500, Number(ms) || 1200));
  }

  if (!('Notification' in window)) {
    showStatus('Este navegador no soporta notificaciones.');
    safeShowOpenBtn();
    scheduleClose(1200);
    return;
  }

  Notification.requestPermission().then(function (perm) {
    if (perm !== 'granted') {
      showStatus('Permiso de notificaciones denegado.');
      safeShowOpenBtn();
      scheduleClose(1200);
      return;
    }

    try {
      const n = new Notification(title, { body: bodyText, icon: icon || undefined });

      showStatus('Notificación mostrada.');

      // Vibración opcional
      if (Array.isArray(vibrate) && navigator.vibrate) {
        navigator.vibrate(vibrate);
      }

      // Click en la notificación → ir al detalle y cerrar
      n.onclick = function () {
        try { window.focus(); } catch (e) {}
        try { n.close(); } catch (e) {}
        if (clickUrl) {
          try { window.location.assign(clickUrl); } catch (e) {}
        }
        scheduleClose(300);
      };

      // Auto-cierre de la notificación y luego de la ventana
      const notifMs = Math.max(3000, Math.min(timeoutMs || 15000, 60000));
      setTimeout(() => { try { n.close(); } catch (e) {} }, notifMs);
      scheduleClose(notifMs + 300);

    } catch (e) {
      showStatus('No se pudo crear la notificación: ' + e.message);
      safeShowOpenBtn();
      scheduleClose(1200);
    }
  });
})();
</script>
</body>
</html>