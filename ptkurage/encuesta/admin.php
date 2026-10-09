<?php
/**
 * PT:Kurage - Encuesta Discografía Plastic Tree
 * Panel de Administración
 */
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración · Encuesta Plastic Tree | PT:Kurage</title>
  
  <link rel="icon" type="image/png" href="../assets/favicon-kurage.png?v=<?= time() ?>">
  <link rel="shortcut icon" href="../favicon.ico?v=<?= time() ?>">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="assets/admin_encuesta.css?v=<?= time() ?>">
</head>
<body>

  <!-- PANTALLA DE LOGIN -->
  <section id="admin-login-section" class="admin-login-container">
    <div class="admin-login-card">
      <img src="../assets/logo-white-trimmed.png" alt="PT:Kurage" style="height: 48px; width: auto; margin-bottom: 18px;">
      <h2 style="font-size: 19px; font-weight: 700; margin-bottom: 6px;">Gestión de Encuesta</h2>
      <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">Plastic Tree Tour · Configuración y Votos</p>

      <form id="admin-login-form">
        <div class="form-group" style="text-align: left;">
          <label class="form-label" for="admin-login-password">Contraseña de Acceso</label>
          <input type="password" id="admin-login-password" class="form-control" placeholder="••••••••" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Ingresar al Panel</button>
      </form>
      <div id="admin-login-error" style="display: none; color: var(--danger); font-size: 13px; margin-top: 15px;"></div>
    </div>
  </section>

  <!-- PANEL DE ADMINISTRACIÓN -->
  <div id="admin-main-section" style="display: none;">
    
    <!-- Top Header -->
    <header class="admin-header">
      <div class="admin-header-title">
        <img src="../assets/logo-white-trimmed.png" alt="PT:Kurage">
        <div>
          <div style="font-weight: 700; font-size: 15px;">PANEL ENCUESTA · PLASTIC TREE</div>
          <div style="font-size: 11px; color: var(--text-muted);">Administración de Discografía y Recuento de Votos</div>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 12px;">
        <a href="./" target="_blank" class="btn btn-secondary btn-sm" title="Ver la encuesta como la ven los fans">
          👁️ Ver Encuesta Pública
        </a>
        <a href="../admin" class="btn btn-secondary btn-sm" title="Ir al panel de correos y códigos">
          ✉️ Panel Correos
        </a>
        <button id="btn-admin-logout" class="btn btn-secondary btn-sm" title="Cerrar Sesión">
          Salir
        </button>
      </div>
    </header>

    <!-- Navegación de Pestañas -->
    <nav class="admin-nav">
      <button class="nav-tab active" data-tab="ranking">
        <span>📊 Resultados y Ranking</span>
      </button>
      <button class="nav-tab" data-tab="votes">
        <span>🗳️ Registro de Votantes</span>
      </button>
      <button class="nav-tab" data-tab="albums">
        <span>💿 Discos y Canciones</span>
      </button>
      <button class="nav-tab" data-tab="music">
        <span>🎵 Música & Previews</span>
      </button>
      <button class="nav-tab" data-tab="settings">
        <span>⚙️ Configuración</span>
      </button>
    </nav>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="admin-content">

      <!-- PESTAÑA 1: RANKING Y RESULTADOS -->
      <section id="tab-ranking" class="tab-pane active">
        
        <!-- Métricas Rápidas -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-label">Total Votantes Únicos</div>
            <div class="stat-value" id="stat-voters" style="color: #38bdf8;">0</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Total Votos a Canciones</div>
            <div class="stat-value" id="stat-votes" style="color: #c084fc;">0</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Lanzamientos en BD</div>
            <div class="stat-value" id="stat-albums" style="color: #34d399;">0</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Canciones Registradas</div>
            <div class="stat-value" id="stat-songs" style="color: #fbbf24;">0</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Canciones Enlazadas</div>
            <div class="stat-value" id="stat-linked-songs" style="color: #f43f5e;">0</div>
          </div>
        </div>

        <!-- Tabla de Ranking de Canciones -->
        <div class="admin-card">
          <div class="card-header-row">
            <div>
              <h2 class="card-title">Ranking de Canciones Más Votadas</h2>
              <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                Ordenadas por cantidad total de votos recibidos.
              </div>
            </div>
            <button id="btn-export-csv" class="btn btn-secondary btn-sm">
              📥 Descargar Reporte Completo (CSV / Excel)
            </button>
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Pos.</th>
                  <th>Canción</th>
                  <th>Álbum / Single</th>
                  <th>Tipo</th>
                  <th style="text-align: center;">Votos</th>
                  <th>% Votantes</th>
                </tr>
              </thead>
              <tbody id="ranking-tbody">
                <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">Cargando resultados...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- PESTAÑA 2: REGISTRO DE VOTANTES -->
      <section id="tab-votes" class="tab-pane">
        <div class="admin-card">
          <div class="card-header-row">
            <div>
              <h2 class="card-title">Listado Individual de Votos</h2>
              <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                Historial de cada persona que ha participado, con sus canciones seleccionadas.
              </div>
            </div>
            <button id="btn-clear-all-votes" class="btn btn-danger btn-sm" title="Eliminar todos los votos de prueba">
              🗑️ Vaciar Votos de Prueba
            </button>
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Correo</th>
                  <th>Nombre / Alias</th>
                  <th>Concierto</th>
                  <th>IP</th>
                  <th>Fecha</th>
                  <th>Canciones Elegidas</th>
                  <th style="text-align: right;">Acciones</th>
                </tr>
              </thead>
              <tbody id="votes-tbody">
                <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">Cargando votantes...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- PESTAÑA 3: GESTIÓN DE ÁLBUMES Y CANCIONES -->
      <section id="tab-albums" class="tab-pane">
        <div class="card-header-row" style="margin-bottom: 20px;">
          <div>
            <h2 class="card-title">Discografía de Plastic Tree</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
              Haz clic en la carátula de cualquier disco para subir o cambiar su foto. Puedes agregar o editar canciones en cada uno.
            </div>
          </div>
          <button id="btn-open-new-album" class="btn btn-primary btn-sm">
            + Crear Nuevo Álbum / Single
          </button>
        </div>

        <div class="albums-mgmt-grid" id="albums-mgmt-container">
          <!-- Tarjetas de álbumes -->
        </div>
      </section>

      <!-- PESTAÑA: MÚSICA DE FONDO Y PREVIEWS -->
      <section id="tab-music" class="tab-pane">
        <div style="display: grid; grid-template-columns: 360px 1fr; gap: 24px; align-items: start;">
          
          <!-- Formulario para agregar / subir canción -->
          <div class="admin-card">
            <h2 class="card-title" style="margin-bottom: 6px;">➕ Cargar Música / Preview</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
              Sube audios para que suenen aleatoriamente de fondo en la encuesta o vincúlalos a un tema para preview.
            </div>

            <form id="form-upload-audio" enctype="multipart/form-data">
              <div class="form-group">
                <label class="form-label" for="audio-file">Archivo de Audio (MP3, M4A, OGG, WAV)</label>
                <input type="file" id="audio-file" name="audio_file" class="form-control" accept="audio/*">
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Máximo 25MB por archivo.</div>
              </div>

              <div class="form-group">
                <label class="form-label" for="audio-url">O Enlace de YouTube / YouTube Music / MP3 (Opcional)</label>
                <input type="text" id="audio-url" name="audio_url" class="form-control" placeholder="https://www.youtube.com/watch?v=... o https://youtu.be/...">
              </div>

              <div class="form-group">
                <label class="form-label" for="audio-title">Título de la Canción</label>
                <input type="text" id="audio-title" name="title" class="form-control" placeholder="ej. Sink o Melancholic">
              </div>

              <div class="form-group">
                <label class="form-label" for="audio-artist">Artista</label>
                <input type="text" id="audio-artist" name="artist" class="form-control" value="Plastic Tree">
              </div>

              <div class="form-group">
                <label class="form-label" for="audio-song-id">Vincular a Canción de la Encuesta (Opcional)</label>
                <select id="audio-song-id" name="song_id" class="form-control" style="background: #0d0f15; color: #fff;">
                  <option value="">-- Sin vincular (Solo música de fondo aleatoria) --</option>
                  <!-- Llenado dinámicamente con las canciones de la discografía -->
                </select>
                <div style="font-size: 11px; color: #38bdf8; margin-top: 4px;">
                  💡 Si vinculas una canción, aparecerá un botón "▶ Preview" al lado de ella en la votación para que los fans puedan escucharla.
                </div>
              </div>

              <button type="submit" id="btn-submit-audio" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                🚀 Guardar y Subir Pista
              </button>
            </form>
          </div>

          <!-- Lista de canciones cargadas -->
          <div class="admin-card">
            <div class="card-header-row" style="margin-bottom: 14px;">
              <div>
                <h2 class="card-title">Canciones en Lista de Reproducción (<span id="audio-tracks-count">0</span>)</h2>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                  Estas canciones sonarán aleatoriamente al entrar a la página y rotarán al terminar cada una.
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Reproductor</th>
                    <th>Título / Artista</th>
                    <th>Canción Vinculada</th>
                    <th>Ruta / URL</th>
                    <th style="text-align: right;">Acción</th>
                  </tr>
                </thead>
                <tbody id="audio-tracks-tbody">
                  <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">No hay canciones cargadas aún. Sube la primera en el formulario lateral.</td></tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </section>

      <!-- PESTAÑA 5: CONFIGURACIÓN -->
      <section id="tab-settings" class="tab-pane">
        <div class="admin-card" style="max-width: 600px;">
          <h2 class="card-title" style="margin-bottom: 18px;">Configuración de la Encuesta</h2>
          
          <form id="form-settings">
            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #0f1118; border-radius: 8px; border: 1px solid var(--border-color);">
              <div>
                <label for="setting-survey-active" style="font-weight: 600; font-size: 14px; color: #fff; cursor: pointer;">Encuesta Activa</label>
                <div style="font-size: 12px; color: var(--text-muted);">Si la desactivas, los fans no podrán emitir nuevos votos.</div>
              </div>
              <input type="checkbox" id="setting-survey-active" style="width: 20px; height: 20px; cursor: pointer;" checked>
            </div>

            <div class="form-group">
              <label class="form-label" for="setting-max-votes">Límite de Canciones por Persona</label>
              <input type="number" id="setting-max-votes" class="form-control" min="1" max="50" value="10" required>
              <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Por defecto: 10 canciones por votante.</div>
            </div>

            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #0f1118; border-radius: 8px; border: 1px solid var(--border-color);">
              <div>
                <label for="setting-allow-ip" style="font-weight: 600; font-size: 14px; color: #fff; cursor: pointer;">Control de Límite por IP</label>
                <div style="font-size: 12px; color: var(--text-muted);">Previene bots limitando votos desde una misma conexión de internet.</div>
              </div>
              <input type="checkbox" id="setting-allow-ip" style="width: 20px; height: 20px; cursor: pointer;" checked>
            </div>

            <div class="form-group">
              <label class="form-label" for="setting-max-ip">Máximo de Votos por IP</label>
              <input type="number" id="setting-max-ip" class="form-control" min="1" max="20" value="3" required>
              <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Recomendado: 3 (permite que personas que compartan el mismo Wi-Fi en su casa puedan votar).</div>
            </div>

            <div class="form-group">
              <label class="form-label" for="setting-google-id">Google Client ID (Opcional)</label>
              <input type="text" id="setting-google-id" class="form-control" placeholder="123456789-abcdef.apps.googleusercontent.com">
              <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Opcional: Si deseas habilitar el botón oficial de Google Sign-In con tu proyecto de Google Cloud.</div>
            </div>

            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: rgba(239, 68, 68, 0.08); border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
              <div>
                <label for="setting-require-google" style="font-weight: 600; font-size: 14px; color: #fca5a5; cursor: pointer;">🔒 Exigir Google Sign-In Obligatorio</label>
                <div style="font-size: 12px; color: var(--text-muted);">Desactiva la opción de escribir un correo manual; solo se podrá votar conectando una cuenta de Google verificada.</div>
              </div>
              <input type="checkbox" id="setting-require-google" style="width: 20px; height: 20px; cursor: pointer;">
            </div>

            <button type="submit" id="btn-save-settings" class="btn btn-primary" style="margin-top: 10px;">
              Guardar Configuraciones
            </button>
          </form>
        </div>
      </section>

    </main>
  </div>

  <!-- MODAL PARA CAMBIAR FOTO / CARÁTULA DE ÁLBUM -->
  <div class="modal-overlay" id="modal-cover">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header">
        <h3 class="modal-title">Cambiar Foto del Disco</h3>
        <button type="button" class="modal-close-btn" id="btn-close-cover">✕</button>
      </div>
      
      <form id="form-cover" enctype="multipart/form-data">
        <input type="hidden" name="album_id" id="cover-album-id">
        
        <div class="modal-body">
          <div style="font-weight: 600; color: #38bdf8; margin-bottom: 14px; font-size: 15px;" id="modal-cover-album-title"></div>

          <div style="text-align: center; margin-bottom: 18px;">
            <img id="cover-preview-img" src="" alt="Vista previa" style="width: 120px; height: 120px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border-color); display: none; margin: 0 auto;">
          </div>

          <div class="form-group">
            <label class="form-label" for="cover-file">Subir imagen desde tu computador (JPG, PNG, WEBP)</label>
            <input type="file" id="cover-file" name="cover_file" class="form-control" accept="image/*">
          </div>

          <div style="text-align: center; font-size: 12px; color: var(--text-muted); margin: 10px 0;">— O ingresa una URL de imagen —</div>

          <div class="form-group">
            <label class="form-label" for="cover-url">URL de imagen web</label>
            <input type="url" id="cover-url" name="cover_url" class="form-control" placeholder="https://ejemplo.com/portada.jpg">
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" id="btn-save-cover" class="btn btn-primary">Guardar Foto</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL PARA CREAR NUEVO ÁLBUM -->
  <div class="modal-overlay" id="modal-album">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header">
        <h3 class="modal-title">Crear Nuevo Lanzamiento</h3>
        <button type="button" class="modal-close-btn" id="btn-close-album">✕</button>
      </div>

      <form id="form-album">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Título del Disco / Single *</label>
            <input type="text" name="title" class="form-control" placeholder="ej. Sublatio" required>
          </div>

          <div class="form-group">
            <label class="form-label">Título en Japonés (Opcional)</label>
            <input type="text" name="title_jp" class="form-control" placeholder="ej. サブラティオ">
          </div>

          <div class="form-group">
            <label class="form-label">Fecha de Lanzamiento (AAAA-MM-DD)</label>
            <input type="text" name="release_date" class="form-control" placeholder="2026-10-15">
          </div>

          <div class="form-group">
            <label class="form-label">Tipo de Lanzamiento</label>
            <select name="album_type" class="form-control">
              <option value="studio">Álbum de Estudio</option>
              <option value="mini">Mini Álbum (EP)</option>
              <option value="single">Single (Sencillo)</option>
              <option value="best">Recopilatorio (Best Album)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">URL de la Carátula (Opcional)</label>
            <input type="url" name="cover_url" class="form-control" placeholder="https://...">
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">+ Crear Lanzamiento</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL PARA VINCULAR AUDIO / YOUTUBE A UNA CANCIÓN -->
  <div class="modal-overlay" id="modal-song-audio">
    <div class="modal-card" style="max-width: 500px;">
      <div class="modal-header">
        <h3 class="modal-title">🎵 Audio / YouTube de Canción</h3>
        <button type="button" class="modal-close-btn" id="btn-close-song-audio">✕</button>
      </div>

      <form id="form-song-audio" enctype="multipart/form-data">
        <input type="hidden" name="song_id" id="song-audio-id">

        <div class="modal-body">
          <div style="font-weight: 700; color: #38bdf8; font-size: 15px; margin-bottom: 4px;" id="modal-song-audio-title"></div>
          <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;" id="modal-song-audio-album"></div>

          <div class="form-group">
            <label class="form-label" for="song-audio-url">Enlace de YouTube, YouTube Music o Enlace MP3</label>
            <input type="text" id="song-audio-url" name="preview_url" class="form-control" placeholder="https://www.youtube.com/watch?v=... o https://youtu.be/...">
            <div style="font-size: 11px; color: #a855f7; margin-top: 4px;">
              ✨ Puedes pegar cualquier link de YouTube. Sonará en la encuesta y servirá de preview para los votantes.
            </div>
          </div>

          <div style="text-align: center; font-size: 12px; color: var(--text-muted); margin: 12px 0;">— O sube un archivo MP3 local —</div>

          <div class="form-group">
            <label class="form-label" for="song-audio-file">Subir Archivo de Audio (MP3 / M4A / WAV)</label>
            <input type="file" id="song-audio-file" name="audio_file" class="form-control" accept="audio/*">
          </div>

          <div id="song-audio-current-status" style="display: none; background: #0b0d14; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; margin-top: 14px; font-size: 12px;">
            <div style="color: var(--text-dim); margin-bottom: 4px;">Enlace o archivo actual:</div>
            <div id="song-audio-current-url" style="color: #38bdf8; word-break: break-all; font-family: monospace;"></div>
          </div>
        </div>

        <div class="modal-footer" style="display: flex; justify-content: space-between;">
          <button type="button" id="btn-delete-song-audio" class="btn btn-danger btn-sm">Quitar Audio</button>
          <button type="submit" id="btn-save-song-audio" class="btn btn-primary">Guardar Preview</button>
        </div>
      </form>
    </div>
  </div>

  <!-- SCRIPTS -->
  <script src="assets/admin_encuesta.js?v=<?= time() ?>"></script>
</body>
</html>
