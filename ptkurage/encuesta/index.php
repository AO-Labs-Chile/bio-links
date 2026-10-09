<?php
/**
 * PT:Kurage - Encuesta Discografía Plastic Tree
 * Página Pública de Votación
 */
require_once __DIR__ . '/db.php';
$maxVotes = 10;
try {
    $maxVotes = (int)\KurageEncuesta\DB::getSetting('max_votes_per_user', '10');
    if ($maxVotes <= 0) $maxVotes = 10;
} catch (\Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Votación de Canciones · Gira Plastic Tree | PT:Kurage</title>
  
  <meta name="description" content="¿Qué canciones te gustaría que estuvieran en la gira de Plastic Tree? Elige hasta 10 temas favoritos de su discografía oficial.">
  <meta property="og:title" content="Votación de Canciones · Gira Plastic Tree | PT:Kurage">
  <meta property="og:description" content="Elige hasta 10 canciones de toda la discografía de Plastic Tree para sugerir a la productora y a la banda.">
  <meta property="og:image" content="../assets/og-preview.png">
  <meta property="og:type" content="website">

  <link rel="icon" type="image/png" href="../assets/favicon-kurage.png?v=<?= time() ?>">
  <link rel="shortcut icon" href="../favicon.ico?v=<?= time() ?>">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="assets/encuesta.css?v=<?= time() ?>">
  <!-- Google Identity Services (Google Sign-In) -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <!-- YouTube IFrame Player API -->
  <script src="https://www.youtube.com/iframe_api"></script>
</head>
<body>

  <!-- HEADER -->
  <header class="encuesta-header">
    <div class="header-container">
      <a href="../" class="header-brand" title="Volver a PT:Kurage">
        <img src="../assets/logo-white-trimmed.png" alt="PT:Kurage" class="header-logo">
        <div style="line-height: 1.1;">
          <div style="font-weight: 700; font-size: 15px; letter-spacing: 0.5px;">PLASTIC TREE</div>
          <div class="header-subtitle">Tour Song Survey · Chile & México</div>
        </div>
      </a>

      <div class="header-actions">
        <a href="admin" class="admin-link" title="Acceso al panel de administración">
          <span>⚙️ Panel</span>
        </a>
      </div>
    </div>
  </header>

  <!-- AVISO DE VOTO PREVIO (SI APLICA) -->
  <div id="already-voted-notice" style="display: none; background: #064e3b; border-bottom: 1px solid #10b981; color: #a7f3d0; padding: 12px 20px; text-align: center; font-size: 13px; font-weight: 500;">
    ✓ Ya has registrado tu voto anteriormente. Tus canciones favoritas ya fueron enviadas al recuento. ¡Muchas gracias por participar!
  </div>

  <!-- HERO SECTION -->
  <section class="hero-section">
    <div class="hero-badge">🎵 Encuesta Oficial de la Comunidad</div>
    <h1 class="hero-title">¿Qué canciones te gustaría que estuvieran en la gira de Plastic Tree?</h1>
    <p class="hero-desc">
      Enviaremos las canciones más votadas como sugerencia directa a la productora, quienes las harán llegar a la banda por si existe la posibilidad de agregar algunas al setlist. ¡Vota por tus favoritas de toda su trayectoria!
    </p>

    <div class="hero-rule-card">
      <span class="hero-rule-number max-votes-placeholder"><?= $maxVotes ?></span>
      <span>Cada persona puede elegir hasta <strong class="max-votes-placeholder"><?= $maxVotes ?></strong> canciones en total entre toda la discografía.</span>
    </div>
  </section>

  <!-- SECCIÓN: RANKING EN VIVO DE LAS MÁS ELEGIDAS -->
  <section class="live-ranking-section" id="live-ranking-section">
    <div class="ranking-header-bar">
      <div class="ranking-header-titles">
        <div class="ranking-badge-live">
          <span class="live-pulse-dot"></span> RANKING EN VIVO
        </div>
        <h2 class="ranking-title">Canciones Más Votadas</h2>
        <p class="ranking-subtitle">Las canciones que lideran las preferencias de los fans para la gira</p>
      </div>

      <div class="ranking-filter-pills" id="ranking-filter-pills">
        <button type="button" class="ranking-pill-btn active" data-ranking-loc="">🌐 Todas</button>
        <button type="button" class="ranking-pill-btn" data-ranking-loc="Chile"><img src="assets/flags/cl.png" alt="Chile" class="flag-icon"> Chile</button>
        <button type="button" class="ranking-pill-btn" data-ranking-loc="México"><img src="assets/flags/mx.png" alt="México" class="flag-icon"> México</button>
      </div>
    </div>

    <div class="ranking-scroll-container">
      <div class="ranking-cards-row" id="ranking-cards-container">
        <div class="ranking-loading">Cargando canciones más votadas...</div>
      </div>
    </div>
  </section>

  <!-- BARRA DE CONTROLES: BÚSQUEDA Y PESTAÑAS -->
  <section class="controls-bar">
    <div class="search-box-wrapper">
      <span class="search-icon">🔍</span>
      <input type="text" id="search-input" class="search-input" placeholder="Buscar canción o disco en tiempo real... (ej. Sink, Tremolo, Melancholic, Puppet Show, echo)">
    </div>

    <div class="filter-tabs">
      <button type="button" class="filter-tab active" data-filter="all">
        <span>Todos los lanzamientos</span>
      </button>
      <button type="button" class="filter-tab" data-filter="studio">
        <span>📀 Álbumes de Estudio</span>
      </button>
      <button type="button" class="filter-tab" data-filter="mini">
        <span>💿 Mini Álbumes (EPs)</span>
      </button>
      <button type="button" class="filter-tab" data-filter="single">
        <span>🎵 Singles</span>
      </button>
      <button type="button" class="filter-tab" data-filter="best">
        <span>⭐ Recopilatorios / B-Sides</span>
      </button>
    </div>
  </section>

  <!-- GRILLA PRINCIPAL DE ÁLBUMES -->
  <main class="albums-grid" id="albums-container">
    <!-- Se llena dinámicamente vía encuesta.js -->
  </main>

  <!-- BARRA FLOTANTE FIJA DE PROGRESO Y ACCIONES -->
  <div class="floating-vote-bar">
    <div class="floating-bar-inner">
      <div class="progress-info-group">
        <div class="counter-text" id="selected-counter">
          Has seleccionado <span class="counter-accent">0 de 10</span> canciones
        </div>
        <div class="counter-progress-bar">
          <div class="progress-fill" id="progress-fill"></div>
        </div>
      </div>

      <div class="floating-actions">
        <button type="button" class="btn btn-secondary" id="btn-open-modal">
          👁️ Ver lista (0)
        </button>
        <button type="button" class="btn btn-submit-vote" id="btn-floating-submit" disabled>
          Elige tus temas (0/10)
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL DE CONFIRMACIÓN Y EMISIÓN DE VOTO -->
  <div class="modal-overlay" id="modal-vote">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title">Confirmar Votación</h3>
        <button type="button" class="modal-close-btn" id="btn-close-modal">✕</button>
      </div>
      
      <form id="form-submit-vote">
        <div class="modal-body">
          <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
            Revisa las canciones seleccionadas. Puedes eliminar cualquiera haciendo clic en la <strong>✕</strong> roja:
          </p>

          <div class="selected-songs-summary" id="selected-songs-summary">
            <!-- Canciones elegidas -->
          </div>

          <!-- OPCIÓN GOOGLE SIGN-IN (SI ESTÁ CONFIGURADO EN EL PANEL) -->
          <div id="google-auth-section" style="display: none; margin: 16px 0; padding: 14px; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: 10px;">
            <div style="font-size: 13px; font-weight: 600; color: #f8fafc; margin-bottom: 10px; text-align: center;">
              ✨ Conecta tu cuenta de Google para validar tu voto:
            </div>
            <div id="google-btn-container" style="display: flex; justify-content: center; min-height: 44px;"></div>
            
            <div id="google-user-badge" style="display: none; align-items: center; justify-content: space-between; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 8px; padding: 10px 14px;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <img id="google-user-avatar" src="" alt="" style="width: 34px; height: 34px; border-radius: 50%; display: none; border: 1px solid #10b981;">
                <div>
                  <div id="google-user-name" style="font-size: 13px; font-weight: 700; color: #10b981;"></div>
                  <div id="google-user-email" style="font-size: 11px; color: #94a3b8;"></div>
                </div>
              </div>
              <button type="button" id="btn-disconnect-google" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 4px 8px;">Cambiar</button>
            </div>

            <div id="google-divider" style="display: flex; align-items: center; margin-top: 14px; color: var(--text-dim); font-size: 11px; text-transform: uppercase;">
              <span style="flex: 1; height: 1px; background: var(--border-color);"></span>
              <span style="padding: 0 10px;">o escribe tu correo manualmente</span>
              <span style="flex: 1; height: 1px; background: var(--border-color);"></span>
            </div>

            <div id="google-required-msg" style="display: none; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 8px 12px; margin-top: 10px; font-size: 12px; color: #fca5a5; text-align: center;">
              🔒 Para garantizar transparencia (1 voto por persona), el inicio de sesión con Google es obligatorio.
            </div>
          </div>

          <div class="form-group" id="manual-email-group">
            <label class="form-label" for="voter-email">Tu Correo Electrónico (Gmail o personal) <span style="color: #ef4444;">*</span></label>
            <input type="email" id="voter-email" class="form-control" placeholder="ejemplo@gmail.com" required>
            <div style="font-size: 11px; color: var(--text-dim); margin-top: 5px;">
              🔒 Se utiliza únicamente para validar que cada persona vote una sola vez (sin spam).
            </div>
          </div>

          <div class="form-row-2col">
            <div class="form-group">
              <label class="form-label" for="voter-name">Tu Nombre <span style="color: #ef4444;">*</span></label>
              <input type="text" id="voter-name" class="form-control" placeholder="ej. Carlos Pérez" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="voter-nickname">Tu Apodo</label>
              <input type="text" id="voter-nickname" class="form-control" placeholder="ej. KurageFan">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">¿A cuál concierto irás? <span style="color: #ef4444;">*</span></label>
            <div class="concert-selector-grid" style="grid-template-columns: 1fr 1fr;">
              <label class="concert-radio-card">
                <input type="radio" name="concert_location" value="Chile" checked>
                <div class="concert-radio-content">
                  <span class="concert-flag"><img src="assets/flags/cl.png" alt="Chile" class="flag-icon-lg"></span>
                  <div class="concert-text">
                    <span class="concert-country">Chile</span>
                    <span class="concert-sub">Santiago</span>
                  </div>
                </div>
              </label>

              <label class="concert-radio-card">
                <input type="radio" name="concert_location" value="México">
                <div class="concert-radio-content">
                  <span class="concert-flag"><img src="assets/flags/mx.png" alt="México" class="flag-icon-lg"></span>
                  <div class="concert-text">
                    <span class="concert-country">México</span>
                    <span class="concert-sub">CDMX</span>
                  </div>
                </div>
              </label>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="btn-cancel-vote">Cerrar</button>
          <button type="submit" class="btn btn-submit-vote" id="btn-confirm-vote">
            🚀 Confirmar y Enviar Voto
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL DE ÉXITO -->
  <div class="modal-overlay" id="modal-success">
    <div class="modal-card" style="text-align: center; max-width: 500px;">
      <div class="modal-body" style="padding: 32px 24px;">
        <div style="font-size: 48px; margin-bottom: 12px;">🎉</div>
        <h3 style="font-size: 20px; font-weight: 700; color: #ffffff; margin-bottom: 10px;">¡Voto Registrado con Éxito!</h3>
        <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6; margin-bottom: 20px;">
          Tus canciones favoritas fueron contabilizadas y serán incluidas en la propuesta que se entregará a la productora. ¡Gracias por apoyar la gira de Plastic Tree!
        </p>

        <div style="background: #0d0f15; border: 1px solid var(--border-color); border-radius: 10px; padding: 14px; text-align: left; margin-bottom: 20px; max-height: 180px; overflow-y: auto;">
          <div style="font-size: 11px; font-weight: 700; color: #38bdf8; text-transform: uppercase; margin-bottom: 8px;">Tus Canciones Elegidas:</div>
          <ul id="success-songs-list" style="list-style: none; padding: 0;"></ul>
        </div>

        <button type="button" class="btn btn-primary" id="btn-close-success" style="width: 100%;">
          Entendido
        </button>
      </div>
    </div>
  </div>

  <!-- REPRODUCTOR FLOTANTE BGM (ESQUINA INFERIOR) -->
  <aside class="bgm-player" id="bgm-player" style="display: none;" aria-label="Reproductor de música Plastic Tree">
    <div class="bgm-disc" id="bgm-disc" title="Plastic Tree BGM">
      <span>💿</span>
    </div>
    <div class="bgm-track-info">
      <div class="bgm-title" id="bgm-title">Plastic Tree</div>
      <div class="bgm-artist" id="bgm-artist">BGM Playlist</div>
    </div>
    <div class="bgm-controls">
      <button type="button" class="bgm-btn" id="bgm-btn-play" title="Reproducir / Pausar">▶</button>
      <button type="button" class="bgm-btn" id="bgm-btn-next" title="Siguiente canción">⏭</button>
      <button type="button" class="bgm-btn" id="bgm-btn-mute" title="Silenciar">🔊</button>
    </div>
    <audio id="bgm-audio-el" preload="auto"></audio>
    <div id="bgm-yt-wrap" style="position: absolute; width: 1px; height: 1px; opacity: 0.01; pointer-events: none; overflow: hidden;"><div id="bgm-yt-player"></div></div>
  </aside>

  <!-- TOAST FLOTANTE -->
  <div class="toast-notice" id="toast-notice"></div>

  <!-- SCRIPTS -->
  <script src="assets/encuesta.js?v=<?= time() ?>"></script>
</body>
</html>
