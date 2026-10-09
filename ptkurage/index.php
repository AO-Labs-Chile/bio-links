<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PT:Kurage · Beneficio Encuesta Plastic Tree</title>
  <meta name="description" content="Consulta tu código de descuento para el concierto de Plastic Tree en Chile y México para quienes completaron la encuesta oficial.">

  <!-- Open Graph / WhatsApp / Facebook Preview -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://japanrevolution.cl/ptkurage/">
  <meta property="og:title" content="PT:Kurage · Beneficio Encuesta Plastic Tree">
  <meta property="og:description" content="Consulta tu código de descuento para el concierto de Plastic Tree en Chile y México para quienes completaron la encuesta oficial.">
  <meta property="og:image" content="https://japanrevolution.cl/ptkurage/assets/og-preview.png">
  <meta property="og:image:secure_url" content="https://japanrevolution.cl/ptkurage/assets/og-preview.png">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Plastic Tree REPLAY · Descuento Exclusivo PT:Kurage">
  <meta property="og:site_name" content="PT:Kurage">

  <!-- Twitter / X Cards -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="https://japanrevolution.cl/ptkurage/">
  <meta name="twitter:title" content="PT:Kurage · Beneficio Encuesta Plastic Tree">
  <meta name="twitter:description" content="Consulta tu código de descuento para el concierto de Plastic Tree en Chile y México para quienes completaron la encuesta oficial.">
  <meta name="twitter:image" content="https://japanrevolution.cl/ptkurage/assets/og-preview.png">

  <link rel="icon" type="image/png" href="assets/favicon-kurage.png?v=<?= time() ?>">
  <link rel="shortcut icon" href="favicon.ico?v=<?= time() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
</head>
<body>

  <div class="container">
    <!-- Header -->
    <header class="header">
      <div class="header-logos" style="display: flex; align-items: center; justify-content: center; gap: 16px; margin: 0 auto 18px;">
        <img src="assets/logo-white-trimmed.png" alt="PT:Kurage - Plastic Tree Fan Community" class="logo-kurage" width="82" height="52" style="height: 52px; width: auto; max-width: 100px; max-height: 52px; object-fit: contain; display: block;">
        <span class="logo-separator" style="font-size: 18px; color: #ffffff; font-weight: 300; line-height: 1; user-select: none;">✕</span>
        <img src="assets/logo-rit-trimmed.png" alt="RIT Agency" class="logo-rit" width="52" height="52" style="height: 52px; width: 52px; max-height: 52px; max-width: 52px; object-fit: contain; display: block; border-radius: 50%;">
      </div>
      <div>
        <span class="badge-event">Plastic Tree · Chile & México</span>
      </div>
      <h1 class="title">Consulta tu Descuento</h1>
      <p class="subtitle">Beneficio exclusivo para los fans que participaron en la encuesta oficial de la comunidad.</p>
    </header>

    <!-- Card Principal -->
    <main class="card">
      
      <!-- Sección Formulario Inicial -->
      <section id="section-form">
        <form id="search-form" autocomplete="off">
          <div class="form-group">
            <label for="email-input" class="form-label">Ingresa tu correo electrónico</label>
            <div class="input-wrapper">
              <span class="input-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
              </span>
              <input 
                type="email" 
                id="email-input" 
                class="form-input" 
                placeholder="ejemplo@correo.com" 
                required 
                autofocus
              >
            </div>
          </div>

          <button type="submit" id="btn-submit" class="btn btn-primary">
            <span id="btn-submit-text">Consultar Beneficio</span>
          </button>
        </form>

        <!-- Aviso sobre el remitente y contactos seguros -->
        <div style="margin-top: 22px; padding: 14px 16px; background: rgba(59, 130, 246, 0.07); border: 1px solid rgba(59, 130, 246, 0.22); border-radius: 10px; text-align: left; font-size: 13px; line-height: 1.55; color: #cbd5e1;">
          <div style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #60a5fa; margin-bottom: 5px; font-size: 13.5px;">
            <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Información importante sobre el correo</span>
          </div>
          El correo con tu código te llegará desde <strong style="color: #ffffff; font-family: monospace; background: rgba(255, 255, 255, 0.1); padding: 2px 6px; border-radius: 4px;"><!--email_off-->ptkurage@japanrevolution.cl<!--/email_off--></strong>. Te sugerimos agregar esta dirección a tus contactos de correo y revisar siempre tu carpeta de <em>Spam</em> o <em>Promociones</em> en caso de no verlo en tu bandeja principal.
        </div>
      </section>

      <!-- Sección Resultado: ENCONTRADO -->
      <section id="section-success" class="result-section">
        <div class="success-banner" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; margin-bottom: 20px;">
          <svg width="26" height="26" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink: 0;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
          </svg>
          <div style="text-align: left;">
            <div style="font-weight: 700; color: #10b981; font-size: 16px;">¡Tu correo sí está en nuestra base de datos!</div>
            <div style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
              Asociado a: <strong id="display-email" style="color: #ffffff;"></strong>
            </div>
          </div>
        </div>

        <div class="security-card" style="background: rgba(255, 255, 255, 0.03); border: 1px dashed rgba(255, 255, 255, 0.18); border-radius: 12px; padding: 22px 20px; text-align: center; margin-bottom: 22px;">
          <div style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%; background: rgba(59, 130, 246, 0.12); color: #60a5fa; margin-bottom: 12px;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
          </div>
          <div style="font-weight: 600; font-size: 15px; color: #ffffff; margin-bottom: 6px;">
            Código protegido por seguridad
          </div>
          <p style="font-size: 13px; color: var(--text-muted); line-height: 1.55; margin: 0 0 14px;">
            Por motivos de seguridad, tu código de descuento no se muestra en este buscador y únicamente se entrega a través de tu correo electrónico. Presiona el botón a continuación para recibirlo en tu casilla.
          </p>
          <div id="display-discount-details" style="font-size: 13px; color: #34d399; font-weight: 500; background: rgba(16, 185, 129, 0.08); border-radius: 6px; padding: 8px 14px; display: inline-block;">
          </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
          <button type="button" id="btn-resend" class="btn btn-primary" style="padding: 14px 20px; font-weight: 700; font-size: 15px;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span id="btn-resend-text">Reenviar código a mi correo</span>
          </button>

          <p style="font-size: 12.5px; color: var(--text-muted); margin: 0; line-height: 1.5; text-align: center;">
            El correo llegará desde <strong style="color: #ffffff; font-family: monospace;"><!--email_off-->ptkurage@japanrevolution.cl<!--/email_off--></strong>. Si no lo ves en unos minutos, revisa en <em>Spam / No deseados</em> o añade nuestro correo a tus contactos.
          </p>

          <a id="btn-tickets-link" href="#" target="_blank" class="btn btn-secondary" style="font-weight: 600; text-decoration: none; justify-content: center;">
            <span>Comprar Entradas en RIT (<span id="display-pais-btn"></span>) ↗</span>
          </a>

          <button type="button" id="btn-reset" class="btn btn-outline" style="font-size: 13px; padding: 10px; margin-top: 4px;">
            Consultar otro correo
          </button>
        </div>

        <div id="resend-toast" class="toast-msg"></div>
      </section>

      <!-- Sección Resultado: REGISTRADO SIN CÓDIGO (CONCIERTO NO FUE EN SU PAÍS) -->
      <section id="section-no-code" class="result-section">
        <div class="info-banner" style="background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; margin-bottom: 20px;">
          <svg width="26" height="26" fill="none" stroke="#60a5fa" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink: 0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div style="text-align: left;">
            <div style="font-weight: 700; color: #60a5fa; font-size: 16px;">¡Registro verificado en la encuesta!</div>
            <div style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
              Correo: <strong id="display-no-code-email" style="color: #ffffff;"></strong>
              <span id="display-no-code-country" style="display: none; margin-left: 6px; background: rgba(255,255,255,0.1); padding: 2px 7px; border-radius: 4px; font-size: 11px; color: #e2e8f0;"></span>
            </div>
          </div>
        </div>

        <div class="notice-card" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 12px; padding: 22px 20px; text-align: left; margin-bottom: 22px; font-size: 14px; line-height: 1.6; color: #e2e8f0;">
          <p style="margin: 0 0 12px; font-size: 15px;">
            Sí estabas registrado/a en la encuesta, pero como los conciertos no fueron en tu país, <strong>no se generó código de descuento</strong>.
          </p>
          <p style="margin: 0 0 16px; color: var(--text-muted); font-size: 13.5px;">
            Si quieres viajar a alguno de los dos países (Chile o México), avísanos enviando una captura de este mensaje a nuestro Instagram 
            <a href="https://www.instagram.com/pt_kurage" target="_blank" rel="noopener noreferrer" style="color: #38bdf8; font-weight: 600; text-decoration: underline;">@pt_kurage</a> 
            indicando tu correo y a qué país quieres ir, para intentar gestionarte un código de descuento.
          </p>
          <div style="text-align: center; margin-top: 14px;">
            <a href="https://www.instagram.com/pt_kurage" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-decoration: none; padding: 13px 20px; font-weight: 700; background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); border: none;">
              <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
              <span>Contactar en Instagram @pt_kurage ↗</span>
            </a>
          </div>
        </div>

        <button type="button" id="btn-reset-no-code" class="btn btn-outline" style="font-size: 13px; padding: 10px;">
          Consultar otro correo
        </button>
      </section>

      <!-- Sección Resultado: NO ENCONTRADO -->
      <section id="section-not-found" class="result-section">
        <div class="not-found-card">
          <div class="not-found-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <h3 class="not-found-title">Correo no encontrado</h3>
          <p class="not-found-desc">
            Tu correo no está en la base de datos. Puede que hayas usado otro correo para llenar la encuesta o nunca la completaste.
          </p>

          <div class="tips-box">
            <strong>¿Qué puedes hacer?</strong>
            <ul>
              <li>Revisa si completaste el formulario con otra cuenta de Google/Gmail.</li>
              <li>Comprueba que no haya errores de escritura al ingresar tu correo.</li>
            </ul>
          </div>
        </div>

        <button type="button" id="btn-reset-not-found" class="btn btn-primary">
          Intentar con otro correo
        </button>
      </section>

    </main>

    <!-- Footer -->
    <footer class="footer">
      <p><a href="https://www.instagram.com/pt_kurage" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">PT:Kurage — Plastic Tree Fan Community</a></p>
      <p style="margin-top: 4px;">En colaboración con <a href="https://www.rit.agency/" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">R.I.T</a></p>
      <div class="footer-admin">
        <a href="admin.php">Panel de Administración</a>
      </div>
    </footer>
  </div>

  <script src="assets/app.js?v=<?= time() ?>"></script>
</body>
</html>
