/**
 * PT:Kurage - JavaScript del Panel de Administración de la Encuesta
 */

(function () {
  'use strict';

  let currentTab = 'ranking';
  let cachedDashboardData = null;
  let cachedAlbums = [];

  // DOM
  const loginSection = document.getElementById('admin-login-section');
  const mainSection = document.getElementById('admin-main-section');
  const loginForm = document.getElementById('admin-login-form');
  const loginPassword = document.getElementById('admin-login-password');
  const loginError = document.getElementById('admin-login-error');
  const btnLogout = document.getElementById('btn-admin-logout');

  // Tabs
  const navTabs = document.querySelectorAll('.nav-tab');
  const tabPanes = document.querySelectorAll('.tab-pane');

  // Modales
  const modalCover = document.getElementById('modal-cover');
  const formCover = document.getElementById('form-cover');
  const coverAlbumId = document.getElementById('cover-album-id');
  const coverFile = document.getElementById('cover-file');
  const coverUrlInput = document.getElementById('cover-url');
  const coverPreview = document.getElementById('cover-preview-img');
  const btnCloseCover = document.getElementById('btn-close-cover');

  const modalAlbum = document.getElementById('modal-album');
  const formAlbum = document.getElementById('form-album');
  const btnCloseAlbum = document.getElementById('btn-close-album');
  const btnOpenNewAlbum = document.getElementById('btn-open-new-album');

  // Auth check
  async function checkAuth() {
    try {
      const res = await fetch('api.php?action=admin_check_auth');
      const data = await res.json();
      if (data.success && data.logged) {
        showMainPanel();
      } else {
        showLoginPanel();
      }
    } catch (e) {
      showLoginPanel();
    }
  }

  function showLoginPanel() {
    if (loginSection) loginSection.style.display = 'flex';
    if (mainSection) mainSection.style.display = 'none';
  }

  function showMainPanel() {
    if (loginSection) loginSection.style.display = 'none';
    if (mainSection) mainSection.style.display = 'block';
    loadDashboard();
    loadAlbums();
    loadAudioTracks();
  }

  // Login
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      loginError.style.display = 'none';

      const pwd = loginPassword.value;
      const fd = new FormData();
      fd.append('password', pwd);

      try {
        const res = await fetch('api.php?action=admin_login', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          loginPassword.value = '';
          showMainPanel();
        } else {
          loginError.textContent = data.error || 'Contraseña incorrecta';
          loginError.style.display = 'block';
        }
      } catch (err) {
        loginError.textContent = 'Error de conexión con el servidor.';
        loginError.style.display = 'block';
      }
    });
  }

  // Logout
  if (btnLogout) {
    btnLogout.addEventListener('click', async () => {
      await fetch('api.php?action=admin_logout', { method: 'POST' });
      showLoginPanel();
    });
  }

  // Pestañas
  navTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;
      navTabs.forEach(t => t.classList.remove('active'));
      tabPanes.forEach(p => p.classList.remove('active'));

      tab.classList.add('active');
      const targetPane = document.getElementById('tab-' + target);
      if (targetPane) targetPane.classList.add('active');
      currentTab = target;

      if (target === 'votes') {
        loadVotesTable();
      } else if (target === 'albums') {
        loadAlbums();
      } else if (target === 'music') {
        loadAudioTracks();
      }
    });
  });

  // Cargar Dashboard (Ranking y Métricas)
  async function loadDashboard() {
    try {
      const res = await fetch('api.php?action=admin_get_dashboard');
      const data = await res.json();
      if (data.success) {
        cachedDashboardData = data;

        // Actualizar contadores
        document.getElementById('stat-voters').textContent = data.total_voters.toLocaleString('es-CL');
        document.getElementById('stat-votes').textContent = data.total_votes.toLocaleString('es-CL');
        if (data.total_albums !== undefined && document.getElementById('stat-albums')) {
          document.getElementById('stat-albums').textContent = data.total_albums.toLocaleString('es-CL');
        }
        if (data.total_songs !== undefined && document.getElementById('stat-songs')) {
          document.getElementById('stat-songs').textContent = data.total_songs.toLocaleString('es-CL');
        }
        if (data.total_linked_songs !== undefined && document.getElementById('stat-linked-songs')) {
          document.getElementById('stat-linked-songs').textContent = data.total_linked_songs.toLocaleString('es-CL');
        }

        renderRankingTable(data.ranking, data.total_voters);
        populateSettingsForm(data.settings);
      }
    } catch (e) {
      console.error('Error al cargar dashboard:', e);
    }
  }

  // Renderizar tabla de ranking
  function renderRankingTable(ranking, totalVoters) {
    const tbody = document.getElementById('ranking-tbody');
    if (!tbody) return;

    if (!ranking || ranking.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">Aún no hay votos registrados.</td></tr>`;
      return;
    }

    tbody.innerHTML = ranking.map((item, idx) => {
      let medal = '';
      if (idx === 0) medal = '🥇 ';
      else if (idx === 1) medal = '🥈 ';
      else if (idx === 2) medal = '🥉 ';

      const percent = item.percentage || 0;

      return `
        <tr>
          <td style="font-weight: 700; color: #38bdf8; width: 60px;">${medal}#${idx + 1}</td>
          <td>
            <div style="font-weight: 600; color: #ffffff;">${escapeHtml(item.song_title)}</div>
            ${item.song_title_jp ? `<div style="font-size: 11px; color: var(--text-dim);">${escapeHtml(item.song_title_jp)}</div>` : ''}
          </td>
          <td><span style="color: #cbd5e1;">${escapeHtml(item.album_title)}</span></td>
          <td><span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted);">${escapeHtml(item.album_type)}</span></td>
          <td style="font-weight: 700; color: #34d399; text-align: center; width: 90px;">${item.votes_count}</td>
          <td style="width: 170px;">
            <div class="ranking-bar-wrapper">
              <div class="ranking-bar-track">
                <div class="ranking-bar-fill" style="width: ${percent}%;"></div>
              </div>
              <span class="ranking-percent">${percent}%</span>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // Cargar tabla de votantes
  async function loadVotesTable(page = 1) {
    const tbody = document.getElementById('votes-tbody');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 25px;">Cargando votantes...</td></tr>`;

    try {
      const res = await fetch(`api.php?action=admin_get_votes_page&page=${page}&per_page=50`);
      const data = await res.json();
      if (data.success) {
        if (!data.votes || data.votes.length === 0) {
          tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">No hay registros de votantes.</td></tr>`;
          return;
        }

        tbody.innerHTML = data.votes.map(v => {
          const songsList = (v.songs || []).map(s => {
            return `<span style="display: inline-block; background: #0b0d14; border: 1px solid #232838; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin: 2px;">${escapeHtml(s.title)}</span>`;
          }).join(' ');

          const loc = (v.concert_location || 'Chile').trim();
          let countryBadge = '';
          if (loc === 'México' || loc === 'Mexico') {
            countryBadge = '<span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;"><img src="assets/flags/mx.png" style="width: 14px; height: 10px; border-radius: 2px; object-fit: cover;" alt="MX"> México</span>';
          } else if (loc === 'Chile') {
            countryBadge = '<span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.35); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;"><img src="assets/flags/cl.png" style="width: 14px; height: 10px; border-radius: 2px; object-fit: cover;" alt="CL"> Chile</span>';
          } else {
            countryBadge = '<span class="badge" style="background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.35); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;">🌎 Ambos</span>';
          }

          const voterNickHtml = v.voter_nickname ? `<div style="font-size: 11px; color: #38bdf8;">"${escapeHtml(v.voter_nickname)}"</div>` : '';

          return `
            <tr>
              <td style="font-family: monospace; color: var(--text-dim); width: 50px;">#${v.id}</td>
              <td style="font-weight: 600; color: #ffffff;">${escapeHtml(v.voter_email)}</td>
              <td>
                <div style="font-weight: 600; color: #ffffff;">${escapeHtml(v.voter_name || '-')}</div>
                ${voterNickHtml}
              </td>
              <td>${countryBadge}</td>
              <td style="font-family: monospace; font-size: 11px; color: var(--text-muted);">${escapeHtml(v.voter_ip || '-')}</td>
              <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">${escapeHtml(v.created_at || '-')}</td>
              <td>
                <div style="max-height: 80px; overflow-y: auto;">
                  ${songsList}
                </div>
              </td>
              <td style="text-align: right; width: 60px;">
                <button class="btn btn-danger btn-icon" title="Eliminar este voto" onclick="window.deleteSingleVote(${v.id})">🗑️</button>
              </td>
            </tr>
          `;
        }).join('');
      }
    } catch (e) {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: #ef4444; padding: 25px;">Error al cargar votantes.</td></tr>`;
    }
  }

  // Eliminar voto individual
  window.deleteSingleVote = async (id) => {
    if (!confirm('¿Deseas eliminar este registro de voto?')) return;
    const fd = new FormData();
    fd.append('id', id);
    const res = await fetch('api.php?action=admin_delete_vote', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      loadVotesTable();
      loadDashboard();
    } else {
      alert(data.error || 'Error al eliminar');
    }
  };

  // Vaciar todos los votos
  const btnClearVotes = document.getElementById('btn-clear-all-votes');
  if (btnClearVotes) {
    btnClearVotes.addEventListener('click', async () => {
      if (!confirm('⚠️ ¿Estás seguro de que deseas VACIAR TODOS los votos registrados? Esta acción no se puede deshacer.')) return;
      if (!confirm('Por favor confirma una vez más: ¿Eliminar TODOS los votos de la base de datos?')) return;

      const res = await fetch('api.php?action=admin_clear_all_votes', { method: 'POST' });
      const data = await res.json();
      if (data.success) {
        alert(data.message || 'Votos borrados.');
        loadDashboard();
        loadVotesTable();
      } else {
        alert(data.error || 'Error al vaciar votos');
      }
    });
  }

  // Exportar CSV
  const btnExportCsv = document.getElementById('btn-export-csv');
  if (btnExportCsv) {
    btnExportCsv.addEventListener('click', () => {
      window.location.href = 'api.php?action=admin_export_csv';
    });
  }

  // Cargar Álbumes para Gestión
  async function loadAlbums() {
    const container = document.getElementById('albums-mgmt-container');
    if (!container) return;

    try {
      const res = await fetch('api.php?action=get_discography');
      const data = await res.json();
      if (data.success) {
        cachedAlbums = data.albums || [];
        if (document.getElementById('stat-albums')) {
          document.getElementById('stat-albums').textContent = cachedAlbums.length;
        }

        let totalSongs = 0;
        let totalLinked = 0;
        cachedAlbums.forEach(a => {
          (a.songs || []).forEach(s => {
            totalSongs++;
            if (s.preview_audio_url && s.preview_audio_url.trim() !== '') {
              totalLinked++;
            }
          });
        });
        if (document.getElementById('stat-songs')) {
          document.getElementById('stat-songs').textContent = totalSongs;
        }
        if (document.getElementById('stat-linked-songs')) {
          document.getElementById('stat-linked-songs').textContent = totalLinked;
        }

        renderAlbumsMgmt(cachedAlbums);
        populateAudioSongSelect(cachedAlbums);
      }
    } catch (e) {
      console.error(e);
    }
  }

  function renderAlbumsMgmt(albums) {
    const container = document.getElementById('albums-mgmt-container');
    if (!container) return;

    container.innerHTML = albums.map(album => {
      const songs = album.songs || [];
      const coverImg = album.cover_url ? `<img src="${escapeHtml(album.cover_url)}" alt="">` : `<span style="font-size: 22px;">💿</span>`;

      const songsHtml = songs.map(s => {
        return `
          <div class="mgmt-song-row">
            <div>
              <span style="color: var(--text-dim); font-size: 11px; margin-right: 5px;">#${s.track_num}</span>
              <span style="font-weight: 500; color: #f1f5f9;">${escapeHtml(s.title)}</span>
              ${s.title_jp ? `<span style="color: var(--text-dim); font-size: 11px; margin-left: 4px;">(${escapeHtml(s.title_jp)})</span>` : ''}
              ${s.preview_audio_url ? `<span title="${escapeHtml(s.preview_audio_url)}" style="font-size: 11px; color: #c084fc; background: rgba(168,85,247,0.15); padding: 1px 6px; border-radius: 10px; margin-left: 6px;">🎵 Audio</span>` : ''}
            </div>
            <div class="mgmt-song-actions">
              <button class="btn btn-secondary btn-icon" title="Vincular Audio / YouTube" onclick="window.openSongAudioModal(${s.id}, '${escapeJs(s.title)}', '${escapeJs(album.title)}', '${escapeJs(s.preview_audio_url || '')}')">🎵</button>
              <button class="btn btn-secondary btn-icon" title="Editar canción" onclick="window.editSong(${s.id}, '${escapeJs(s.title)}', '${escapeJs(s.title_jp || '')}')">✏️</button>
              <button class="btn btn-danger btn-icon" title="Borrar canción" onclick="window.deleteSong(${s.id})">🗑️</button>
            </div>
          </div>
        `;
      }).join('');

      return `
        <div class="album-mgmt-card" id="mgmt-card-${album.id}">
          <div class="album-mgmt-header">
            <div class="album-mgmt-cover" title="Haz clic para cambiar la foto/carátula" onclick="window.openCoverModal(${album.id}, '${escapeJs(album.title)}', '${escapeJs(album.cover_url || '')}')">
              ${coverImg}
              <div class="album-cover-overlay">Cambiar Foto</div>
            </div>
            <div style="flex: 1; min-width: 0;">
              <div style="font-weight: 700; font-size: 14px; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(album.title)}</div>
              <div style="font-size: 11px; color: var(--text-dim);">${album.release_date || ''} • <span style="text-transform: uppercase;">${album.album_type}</span></div>
            </div>
            <button class="btn btn-secondary btn-sm" title="Agregar canción a este disco" onclick="window.openAddSong(${album.id}, '${escapeJs(album.title)}')">
              + Tema
            </button>
          </div>
          <div class="album-mgmt-songs">
            ${songsHtml.length ? songsHtml : '<div style="color: var(--text-dim); font-size: 12px; padding: 10px;">Sin canciones aún.</div>'}
          </div>
        </div>
      `;
    }).join('');
  }

  // Modal para Cambiar Portada
  window.openCoverModal = (albumId, albumTitle, currentUrl) => {
    coverAlbumId.value = albumId;
    document.getElementById('modal-cover-album-title').textContent = albumTitle;
    coverUrlInput.value = currentUrl || '';
    coverFile.value = '';

    if (currentUrl) {
      coverPreview.src = currentUrl;
      coverPreview.style.display = 'block';
    } else {
      coverPreview.style.display = 'none';
    }

    modalCover.style.display = 'flex';
  };

  if (btnCloseCover) {
    btnCloseCover.addEventListener('click', () => {
      modalCover.style.display = 'none';
    });
  }

  if (coverFile) {
    coverFile.addEventListener('change', () => {
      const file = coverFile.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          coverPreview.src = e.target.result;
          coverPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }

  if (formCover) {
    formCover.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('btn-save-cover');
      btn.disabled = true;
      btn.textContent = 'Guardando...';

      const fd = new FormData(formCover);

      try {
        const res = await fetch('api.php?action=admin_upload_cover', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.success) {
          modalCover.style.display = 'none';
          loadAlbums();
        } else {
          alert(data.error || 'Error al guardar carátula');
        }
      } catch (err) {
        alert('Error de conexión');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar Foto';
      }
    });
  }

  // Gestión de Canciones: Añadir, Editar, Borrar
  window.openAddSong = async (albumId, albumTitle) => {
    const title = prompt(`Agregar nueva canción a "${albumTitle}":\n\nTítulo de la canción:`);
    if (!title || !title.trim()) return;

    const titleJp = prompt(`Título en japonés (opcional, ej. 散リユク僕ラ):`) || '';

    const fd = new FormData();
    fd.append('album_id', albumId);
    fd.append('title', title.trim());
    fd.append('title_jp', titleJp.trim());

    try {
      const res = await fetch('api.php?action=admin_add_song', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        loadAlbums();
      } else {
        alert(data.error || 'Error al agregar canción');
      }
    } catch (e) {
      alert('Error de conexión');
    }
  };

  window.editSong = async (songId, currentTitle, currentTitleJp) => {
    const newTitle = prompt('Editar título de la canción:', currentTitle);
    if (!newTitle || !newTitle.trim()) return;

    const newTitleJp = prompt('Editar título en japonés (opcional):', currentTitleJp) || '';

    const fd = new FormData();
    fd.append('song_id', songId);
    fd.append('title', newTitle.trim());
    fd.append('title_jp', newTitleJp.trim());

    try {
      const res = await fetch('api.php?action=admin_update_song', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        loadAlbums();
      } else {
        alert(data.error || 'Error al editar');
      }
    } catch (e) {
      alert('Error de conexión');
    }
  };

  window.deleteSong = async (songId) => {
    if (!confirm('¿Eliminar esta canción?')) return;
    const fd = new FormData();
    fd.append('song_id', songId);
    const res = await fetch('api.php?action=admin_delete_song', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      loadAlbums();
    } else {
      alert(data.error || 'Error al borrar');
    }
  };

  // Crear Nuevo Álbum
  if (btnOpenNewAlbum) {
    btnOpenNewAlbum.addEventListener('click', () => {
      modalAlbum.style.display = 'flex';
    });
  }

  if (btnCloseAlbum) {
    btnCloseAlbum.addEventListener('click', () => {
      modalAlbum.style.display = 'none';
    });
  }

  if (formAlbum) {
    formAlbum.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(formAlbum);
      try {
        const res = await fetch('api.php?action=admin_add_album', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          formAlbum.reset();
          modalAlbum.style.display = 'none';
          loadAlbums();
        } else {
          alert(data.error || 'Error al crear álbum');
        }
      } catch (err) {
        alert('Error de conexión');
      }
    });
  }

  // Selector de canciones vinculadas para música / previews
  function populateAudioSongSelect(albums) {
    const select = document.getElementById('audio-song-id');
    if (!select) return;
    const currentVal = select.value;
    let html = '<option value="">-- Sin vincular (Solo música de fondo aleatoria) --</option>';
    albums.forEach(album => {
      if (album.songs && album.songs.length > 0) {
        html += `<optgroup label="${escapeHtml(album.title)}">`;
        album.songs.forEach(song => {
          html += `<option value="${song.id}">${escapeHtml(song.title)}${song.title_jp ? ' (' + escapeHtml(song.title_jp) + ')' : ''}</option>`;
        });
        html += `</optgroup>`;
      }
    });
    select.innerHTML = html;
    if (currentVal) select.value = currentVal;
  }

  // Cargar Pistas de Audio
  async function loadAudioTracks() {
    const tbody = document.getElementById('audio-tracks-tbody');
    const countSpan = document.getElementById('audio-tracks-count');
    if (!tbody) return;

    try {
      const res = await fetch('api.php?action=admin_get_audio_tracks');
      const data = await res.json();
      if (data.success) {
        const tracks = data.tracks || [];
        if (countSpan) countSpan.textContent = tracks.length;

        if (tracks.length === 0) {
          tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">No hay canciones cargadas aún. Sube la primera en el formulario lateral.</td></tr>`;
          return;
        }

        tbody.innerHTML = tracks.map(t => {
          const linkedSong = t.song_title ? `<span style="color: #38bdf8; font-weight: 500;">${escapeHtml(t.song_title)}</span> <span style="font-size: 11px; color: var(--text-muted);">(${escapeHtml(t.album_title || '')})</span>` : `<span style="color: var(--text-dim); font-size: 12px;">Solo BGM Ambiental</span>`;

          return `
            <tr>
              <td style="width: 200px;">
                <audio controls preload="none" style="height: 32px; width: 190px;" src="${escapeHtml(t.file_url)}"></audio>
              </td>
              <td>
                <div style="font-weight: 600; color: #fff;">${escapeHtml(t.title || 'Sin título')}</div>
                <div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(t.artist || 'Plastic Tree')}</div>
              </td>
              <td>${linkedSong}</td>
              <td style="font-size: 11px; font-family: monospace; color: var(--text-muted); max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <a href="${escapeHtml(t.file_url)}" target="_blank" style="color: #a855f7;">${escapeHtml(t.file_url)}</a>
              </td>
              <td style="text-align: right; width: 60px;">
                <button type="button" class="btn btn-danger btn-icon" title="Eliminar pista" onclick="window.deleteAudioTrack(${t.id})">🗑️</button>
              </td>
            </tr>
          `;
        }).join('');
      }
    } catch (e) {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #ef4444; padding: 25px;">Error al cargar pistas de audio.</td></tr>`;
    }
  }

  // Subir / Guardar Pista de Audio
  const formUploadAudio = document.getElementById('form-upload-audio');
  if (formUploadAudio) {
    formUploadAudio.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('btn-submit-audio');
      btn.disabled = true;
      btn.textContent = 'Subiendo audio...';

      const fd = new FormData(formUploadAudio);
      try {
        const res = await fetch('api.php?action=admin_upload_audio', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          alert('Pista guardada exitosamente.');
          formUploadAudio.reset();
          loadAudioTracks();
        } else {
          alert(data.error || 'Error al guardar la pista');
        }
      } catch (err) {
        alert('Error de red al subir audio.');
      } finally {
        btn.disabled = false;
        btn.textContent = '🚀 Guardar y Subir Pista';
      }
    });
  }

  window.deleteAudioTrack = async (id) => {
    if (!confirm('¿Deseas eliminar esta canción del reproductor?')) return;
    const fd = new FormData();
    fd.append('id', id);
    try {
      const res = await fetch('api.php?action=admin_delete_audio', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        loadAudioTracks();
      } else {
        alert(data.error || 'Error al eliminar');
      }
    } catch (err) {
      alert('Error de red al eliminar');
    }
  };

  // Modal Audio / YouTube de Canción
  const modalSongAudio = document.getElementById('modal-song-audio');
  const formSongAudio = document.getElementById('form-song-audio');
  const songAudioId = document.getElementById('song-audio-id');
  const songAudioTitle = document.getElementById('modal-song-audio-title');
  const songAudioAlbum = document.getElementById('modal-song-audio-album');
  const songAudioUrl = document.getElementById('song-audio-url');
  const songAudioFile = document.getElementById('song-audio-file');
  const songAudioStatus = document.getElementById('song-audio-current-status');
  const songAudioCurrentUrl = document.getElementById('song-audio-current-url');
  const btnCloseSongAudio = document.getElementById('btn-close-song-audio');
  const btnDeleteSongAudio = document.getElementById('btn-delete-song-audio');

  window.openSongAudioModal = (id, title, albumTitle, currentUrl) => {
    if (!modalSongAudio) return;
    songAudioId.value = id;
    songAudioTitle.textContent = title;
    songAudioAlbum.textContent = 'Disco: ' + albumTitle;
    songAudioUrl.value = currentUrl || '';
    if (songAudioFile) songAudioFile.value = '';

    if (currentUrl) {
      songAudioStatus.style.display = 'block';
      songAudioCurrentUrl.textContent = currentUrl;
      btnDeleteSongAudio.style.display = 'inline-block';
    } else {
      songAudioStatus.style.display = 'none';
      btnDeleteSongAudio.style.display = 'none';
    }

    modalSongAudio.classList.add('active');
  };

  if (btnCloseSongAudio) {
    btnCloseSongAudio.addEventListener('click', () => {
      modalSongAudio.classList.remove('active');
    });
  }

  if (formSongAudio) {
    formSongAudio.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('btn-save-song-audio');
      btn.disabled = true;
      btn.textContent = 'Guardando...';

      const fd = new FormData(formSongAudio);
      try {
        const res = await fetch('api.php?action=admin_update_song_preview', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          alert('Audio / YouTube de canción guardado con éxito.');
          modalSongAudio.classList.remove('active');
          loadAlbums();
        } else {
          alert(data.error || 'Error al guardar');
        }
      } catch (err) {
        alert('Error de conexión');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar Preview';
      }
    });
  }

  if (btnDeleteSongAudio) {
    btnDeleteSongAudio.addEventListener('click', async () => {
      if (!confirm('¿Deseas desvincular el audio de esta canción?')) return;
      const fd = new FormData();
      fd.append('song_id', songAudioId.value);
      fd.append('preview_url', '');

      try {
        const res = await fetch('api.php?action=admin_update_song_preview', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          alert('Audio desvinculado.');
          modalSongAudio.classList.remove('active');
          loadAlbums();
        } else {
          alert(data.error || 'Error al desvincular');
        }
      } catch (err) {
        alert('Error de conexión');
      }
    });
  }

  // Configuración
  function populateSettingsForm(settings) {
    if (!settings) return;
    const form = document.getElementById('form-settings');
    if (!form) return;

    const surveyActive = document.getElementById('setting-survey-active');
    if (surveyActive) surveyActive.checked = settings.survey_active === '1';

    const maxVotes = document.getElementById('setting-max-votes');
    if (maxVotes) maxVotes.value = settings.max_votes_per_user || '10';

    const allowIp = document.getElementById('setting-allow-ip');
    if (allowIp) allowIp.checked = settings.allow_ip_limit === '1';

    const maxIp = document.getElementById('setting-max-ip');
    if (maxIp) maxIp.value = settings.max_votes_per_ip || '3';

    const googleId = document.getElementById('setting-google-id');
    if (googleId) googleId.value = settings.google_client_id || '';

    const requireGoogle = document.getElementById('setting-require-google');
    if (requireGoogle) requireGoogle.checked = settings.require_google_auth === '1';
  }

  const formSettings = document.getElementById('form-settings');
  if (formSettings) {
    formSettings.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('btn-save-settings');
      btn.disabled = true;
      btn.textContent = 'Guardando...';

      const fd = new FormData();
      fd.append('survey_active', document.getElementById('setting-survey-active').checked ? '1' : '0');
      fd.append('max_votes_per_user', document.getElementById('setting-max-votes').value);
      fd.append('allow_ip_limit', document.getElementById('setting-allow-ip').checked ? '1' : '0');
      fd.append('max_votes_per_ip', document.getElementById('setting-max-ip').value);
      fd.append('google_client_id', document.getElementById('setting-google-id').value.trim());
      const requireGoogleEl = document.getElementById('setting-require-google');
      fd.append('require_google_auth', requireGoogleEl && requireGoogleEl.checked ? '1' : '0');

      try {
        const res = await fetch('api.php?action=admin_update_settings', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          alert('Configuraciones guardadas exitosamente.');
        } else {
          alert(data.error || 'Error al guardar');
        }
      } catch (err) {
        alert('Error de red');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar Configuraciones';
      }
    });
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escapeJs(str) {
    if (!str) return '';
    return String(str)
      .replace(/\\/g, '\\\\')
      .replace(/'/g, "\\'")
      .replace(/"/g, '\\"')
      .replace(/\n/g, '\\n')
      .replace(/\r/g, '\\r');
  }

  checkAuth();
})();
