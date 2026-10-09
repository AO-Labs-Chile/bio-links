/**
 * PT:Kurage - Encuesta Discografía Plastic Tree
 * Interacción pública, selección de canciones y votación
 */

(function () {
  'use strict';

  // Estado global de la aplicación
  let allAlbums = [];
  let currentFilter = 'all';
  let searchQuery = '';
  let maxVotes = 10;
  let isSurveyActive = true;
  let alreadyVoted = false;
  let googleClientId = '';
  let googleAuthInitialized = false;
  let requireGoogleAuth = false;
  let googleVoterCredential = null;

  // BGM Player state
  let playlist = [];
  let currentTrackIndex = -1;
  let isPlaying = false;
  let currentPreviewSongId = null;

  // Canciones seleccionadas: Map de songId -> { id, title, albumTitle, albumId }
  const selectedSongs = new Map();

  // Elementos DOM
  const albumsContainer = document.getElementById('albums-container');
  const searchInput = document.getElementById('search-input');
  const filterTabs = document.querySelectorAll('.filter-tab');
  const counterText = document.getElementById('selected-counter');
  const progressFill = document.getElementById('progress-fill');
  const btnOpenModal = document.getElementById('btn-open-modal');
  const btnFloatingSubmit = document.getElementById('btn-floating-submit');

  // Modales
  const modalVote = document.getElementById('modal-vote');
  const btnCloseModal = document.getElementById('btn-close-modal');
  const btnCancelVote = document.getElementById('btn-cancel-vote');
  const selectedSongsSummary = document.getElementById('selected-songs-summary');
  const formSubmitVote = document.getElementById('form-submit-vote');
  const inputVoterEmail = document.getElementById('voter-email');
  const inputVoterName = document.getElementById('voter-name');
  const inputVoterNickname = document.getElementById('voter-nickname');
  const btnConfirmVote = document.getElementById('btn-confirm-vote');

  // Modal de éxito
  const modalSuccess = document.getElementById('modal-success');
  const btnCloseSuccess = document.getElementById('btn-close-success');
  const successSongsList = document.getElementById('success-songs-list');

  // Toast flotante
  const toastEl = document.getElementById('toast-notice');

  function showToast(message, type = 'info', duration = 3200) {
    if (!toastEl) return;
    toastEl.textContent = message;
    toastEl.className = 'toast-notice show ' + type;
    setTimeout(() => {
      toastEl.classList.remove('show');
    }, duration);
  }

  // Cargar selección guardada de LocalStorage
  function loadPersistedSelection() {
    try {
      const saved = localStorage.getItem('kurage_selected_songs');
      if (saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed)) {
          parsed.slice(0, maxVotes).forEach(item => {
            if (item && item.id) {
              selectedSongs.set(parseInt(item.id, 10), item);
            }
          });
        }
      }
    } catch (e) {}
  }

  function savePersistedSelection() {
    try {
      const arr = Array.from(selectedSongs.values());
      localStorage.setItem('kurage_selected_songs', JSON.stringify(arr));
    } catch (e) {}
  }

  // Comprobar estado previo de votación
  async function checkVoteStatus() {
    try {
      const res = await fetch('api.php?action=check_status');
      const data = await res.json();
      if (data.success && data.has_voted) {
        alreadyVoted = true;
        renderVotedBanner();
      }
    } catch (e) {}
  }

  function renderVotedBanner() {
    const banner = document.getElementById('already-voted-notice');
    if (banner) {
      banner.style.display = 'block';
    }
    if (btnFloatingSubmit) {
      btnFloatingSubmit.disabled = true;
      btnFloatingSubmit.textContent = '✓ Voto Ya Registrado';
    }
  }

  // Cargar Discografía desde la API
  async function loadDiscography() {
    try {
      albumsContainer.innerHTML = `
        <div class="loading-box" style="grid-column: 1 / -1;">
          <div class="spinner"></div>
          <p>Cargando discografía oficial de Plastic Tree...</p>
        </div>
      `;

      const res = await fetch('api.php?action=get_discography');
      const data = await res.json();

      if (data.success) {
        allAlbums = data.albums || [];
        maxVotes = data.max_votes || 10;
        isSurveyActive = data.survey_active !== false;
        googleClientId = (data.google_client_id || '').trim();
        requireGoogleAuth = data.require_google_auth === true || data.require_google_auth === '1';

        document.querySelectorAll('.max-votes-placeholder, .hero-rule-number').forEach(el => {
          el.textContent = maxVotes;
        });

        loadPersistedSelection();
        updateSelectionUI();
        renderAlbums();
        loadLiveRanking();

        if (googleClientId) {
          initGoogleAuth();
        }

        initBgmPlayer(data.playlist || []);
      } else {
        albumsContainer.innerHTML = `
          <div style="grid-column: 1 / -1; text-align: center; color: #ef4444; padding: 40px;">
            <p>Error al cargar la discografía: ${data.error || 'Intenta nuevamente más tarde'}</p>
          </div>
        `;
      }
    } catch (e) {
      albumsContainer.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; color: #ef4444; padding: 40px;">
          <p>Error de conexión al cargar las canciones. Por favor refresca la página.</p>
        </div>
      `;
    }
  }

  // ==========================================
  // REPRODUCTOR BGM & PREVIEWS (HYBRID HTML5 + YOUTUBE)
  // ==========================================
  const bgmPlayerEl = document.getElementById('bgm-player');
  const bgmAudioEl = document.getElementById('bgm-audio-el');
  const bgmTitleEl = document.getElementById('bgm-title');
  const bgmArtistEl = document.getElementById('bgm-artist');
  const bgmBtnPlay = document.getElementById('bgm-btn-play');
  const bgmBtnNext = document.getElementById('bgm-btn-next');
  const bgmBtnMute = document.getElementById('bgm-btn-mute');

  let currentSourceType = 'html5'; // 'html5' | 'youtube'
  let ytPlayer = null;
  let isYtReady = false;
  let isMuted = false;

  function extractYouTubeId(url) {
    if (!url) return null;
    const regExp = /(?:youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/|music\.youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/;
    const match = url.match(regExp);
    return match ? match[1] : null;
  }

  function initYouTubePlayer() {
    if (window.YT && window.YT.Player) {
      createYtPlayer();
    } else {
      window.onYouTubeIframeAPIReady = function () {
        createYtPlayer();
      };
      const checkYt = setInterval(() => {
        if (window.YT && window.YT.Player) {
          clearInterval(checkYt);
          createYtPlayer();
        }
      }, 300);
      setTimeout(() => clearInterval(checkYt), 12000);
    }
  }

  function createYtPlayer() {
    if (ytPlayer || !document.getElementById('bgm-yt-player')) return;
    try {
      ytPlayer = new YT.Player('bgm-yt-player', {
        height: '1',
        width: '1',
        playerVars: {
          autoplay: 0,
          controls: 0,
          disablekb: 1,
          fs: 0,
          rel: 0,
          modestbranding: 1
        },
        events: {
          onReady: onYtPlayerReady,
          onStateChange: onYtPlayerStateChange
        }
      });
    } catch (e) {
      console.error('Error creando YT Player:', e);
    }
  }

  function onYtPlayerReady() {
    isYtReady = true;
    if (isMuted && ytPlayer && ytPlayer.mute) {
      ytPlayer.mute();
    }
  }

  function onYtPlayerStateChange(event) {
    if (!event || !window.YT) return;
    if (event.data === YT.PlayerState.PLAYING) {
      isPlaying = true;
      if (bgmPlayerEl) bgmPlayerEl.classList.add('is-playing');
      if (bgmBtnPlay) bgmBtnPlay.textContent = '⏸';
      updatePreviewButtonsState();
    } else if (event.data === YT.PlayerState.PAUSED) {
      isPlaying = false;
      if (bgmPlayerEl) bgmPlayerEl.classList.remove('is-playing');
      if (bgmBtnPlay) bgmBtnPlay.textContent = '▶';
      updatePreviewButtonsState();
    } else if (event.data === YT.PlayerState.ENDED) {
      playNextRandomTrack();
    }
  }

  function initBgmPlayer(tracks) {
    playlist = tracks || [];
    initYouTubePlayer();

    if (!bgmPlayerEl || !bgmAudioEl) return;

    if (playlist.length === 0) {
      bgmPlayerEl.style.display = 'none';
      return;
    }

    bgmPlayerEl.style.display = 'flex';
    // Escoger pista aleatoria al iniciar
    currentTrackIndex = Math.floor(Math.random() * playlist.length);
    setTrack(currentTrackIndex, false);

    // Autoplay suave en la primera interacción del usuario con la página
    const onFirstUserAction = () => {
      if (!isPlaying && playlist.length > 0) {
        playTrack();
      }
      window.removeEventListener('click', onFirstUserAction);
      window.removeEventListener('keydown', onFirstUserAction);
      window.removeEventListener('touchstart', onFirstUserAction);
    };
    window.addEventListener('click', onFirstUserAction, { once: true });
    window.addEventListener('keydown', onFirstUserAction, { once: true });
    window.addEventListener('touchstart', onFirstUserAction, { once: true });

    // Cuando termina la canción en audio html5, reproducir otra aleatoria
    bgmAudioEl.addEventListener('ended', () => {
      playNextRandomTrack();
    });

    bgmAudioEl.addEventListener('play', () => {
      isPlaying = true;
      bgmPlayerEl.classList.add('is-playing');
      if (bgmBtnPlay) bgmBtnPlay.textContent = '⏸';
      updatePreviewButtonsState();
    });

    bgmAudioEl.addEventListener('pause', () => {
      isPlaying = false;
      bgmPlayerEl.classList.remove('is-playing');
      if (bgmBtnPlay) bgmBtnPlay.textContent = '▶';
      updatePreviewButtonsState();
    });

    if (bgmBtnPlay) {
      bgmBtnPlay.addEventListener('click', (e) => {
        e.stopPropagation();
        togglePlay();
      });
    }

    if (bgmBtnNext) {
      bgmBtnNext.addEventListener('click', (e) => {
        e.stopPropagation();
        playNextRandomTrack();
      });
    }

    if (bgmBtnMute) {
      bgmBtnMute.addEventListener('click', (e) => {
        e.stopPropagation();
        isMuted = !isMuted;
        bgmAudioEl.muted = isMuted;
        if (ytPlayer) {
          try {
            if (isMuted && ytPlayer.mute) ytPlayer.mute();
            if (!isMuted && ytPlayer.unMute) ytPlayer.unMute();
          } catch (err) {}
        }
        bgmBtnMute.textContent = isMuted ? '🔇' : '🔊';
        bgmBtnMute.title = isMuted ? 'Activar sonido' : 'Silenciar';
      });
    }
  }

  function setTrack(index, autoplay = false) {
    if (!playlist[index]) return;
    currentTrackIndex = index;
    const track = playlist[index];
    currentPreviewSongId = track.song_id ? parseInt(track.song_id, 10) : null;

    if (bgmTitleEl) bgmTitleEl.textContent = track.title || 'Plastic Tree';
    if (bgmArtistEl) bgmArtistEl.textContent = track.artist || 'Plastic Tree';

    const ytId = extractYouTubeId(track.file_url);

    if (ytId) {
      currentSourceType = 'youtube';
      bgmAudioEl.pause();
      if (autoplay) {
        playYouTube(ytId);
      }
    } else {
      currentSourceType = 'html5';
      if (ytPlayer && ytPlayer.stopVideo) {
        try { ytPlayer.stopVideo(); } catch (e) {}
      }
      bgmAudioEl.src = track.file_url;
      if (autoplay) {
        playTrack();
      }
    }
  }

  function playYouTube(videoId) {
    if (isYtReady && ytPlayer && ytPlayer.loadVideoById) {
      try {
        ytPlayer.loadVideoById(videoId);
        ytPlayer.playVideo();
        isPlaying = true;
      } catch (e) {
        console.log('Error reproduciendo YouTube:', e);
      }
    } else {
      const waitYt = setInterval(() => {
        if (isYtReady && ytPlayer && ytPlayer.loadVideoById) {
          clearInterval(waitYt);
          try {
            ytPlayer.loadVideoById(videoId);
            ytPlayer.playVideo();
            isPlaying = true;
          } catch (e) {}
        }
      }, 200);
      setTimeout(() => clearInterval(waitYt), 8000);
    }
  }

  function playTrack() {
    if (currentSourceType === 'youtube') {
      if (ytPlayer && ytPlayer.playVideo) {
        try { ytPlayer.playVideo(); } catch (e) {}
      }
    } else {
      if (!bgmAudioEl.src) return;
      bgmAudioEl.play().then(() => {
        isPlaying = true;
      }).catch(err => {
        console.log('Autoplay requiere interacción del usuario');
      });
    }
  }

  function togglePlay() {
    if (currentSourceType === 'youtube') {
      if (ytPlayer && ytPlayer.getPlayerState && window.YT) {
        const state = ytPlayer.getPlayerState();
        if (state === YT.PlayerState.PLAYING) {
          ytPlayer.pauseVideo();
        } else {
          ytPlayer.playVideo();
        }
      } else if (playlist[currentTrackIndex]) {
        setTrack(currentTrackIndex, true);
      }
    } else {
      if (!bgmAudioEl.src && playlist.length > 0) {
        setTrack(currentTrackIndex >= 0 ? currentTrackIndex : 0, true);
        return;
      }
      if (bgmAudioEl.paused) {
        playTrack();
      } else {
        bgmAudioEl.pause();
      }
    }
  }

  function playNextRandomTrack() {
    if (playlist.length === 0) return;
    if (playlist.length === 1) {
      setTrack(0, true);
      return;
    }
    let nextIndex = currentTrackIndex;
    while (nextIndex === currentTrackIndex) {
      nextIndex = Math.floor(Math.random() * playlist.length);
    }
    setTrack(nextIndex, true);
  }

  // Reproducir un preview específico de una canción desde la lista
  window.playPreviewAudio = function (url, title, songId, e) {
    if (e) e.stopPropagation();

    if (currentPreviewSongId === songId && isPlaying) {
      if (currentSourceType === 'youtube' && ytPlayer && ytPlayer.pauseVideo) {
        ytPlayer.pauseVideo();
      } else if (bgmAudioEl) {
        bgmAudioEl.pause();
      }
      return;
    }

    currentPreviewSongId = songId;
    if (bgmPlayerEl) bgmPlayerEl.style.display = 'flex';
    if (bgmTitleEl) bgmTitleEl.textContent = title;
    if (bgmArtistEl) bgmArtistEl.textContent = 'Preview';

    const ytId = extractYouTubeId(url);
    if (ytId) {
      currentSourceType = 'youtube';
      if (bgmAudioEl) bgmAudioEl.pause();
      playYouTube(ytId);
    } else {
      currentSourceType = 'html5';
      if (ytPlayer && ytPlayer.stopVideo) {
        try { ytPlayer.stopVideo(); } catch (err) {}
      }
      if (bgmAudioEl) {
        bgmAudioEl.src = url;
        playTrack();
      }
    }

    showToast(`▶ Escuchando: "${title}"`, 'info', 2500);
  };

  function updatePreviewButtonsState() {
    document.querySelectorAll('.btn-song-preview').forEach(btn => {
      const btnSongId = parseInt(btn.dataset.previewSongId, 10);
      if (btnSongId === currentPreviewSongId && isPlaying) {
        btn.classList.add('playing');
        btn.innerHTML = '⏸ Pausar';
      } else {
        btn.classList.remove('playing');
        btn.innerHTML = '▶ Escuchar';
      }
    });
  }

  // ==========================================
  // GOOGLE IDENTITY SERVICES (GOOGLE SIGN-IN)
  // ==========================================

  function initGoogleAuth() {
    if (!googleClientId) return;

    const authSection = document.getElementById('google-auth-section');
    if (authSection) {
      authSection.style.display = 'block';
    }

    const checkGsi = setInterval(() => {
      if (window.google && window.google.accounts && window.google.accounts.id) {
        clearInterval(checkGsi);
        if (googleAuthInitialized) return;
        googleAuthInitialized = true;

        try {
          google.accounts.id.initialize({
            client_id: googleClientId,
            callback: handleGoogleCredentialResponse,
            auto_select: false
          });

          const container = document.getElementById('google-btn-container');
          if (container) {
            google.accounts.id.renderButton(container, {
              theme: 'filled_black',
              size: 'large',
              text: 'continue_with',
              shape: 'pill',
              width: 280
            });
          }
        } catch (err) {
          console.error('Error al inicializar Google Sign-In:', err);
        }
      }
    }, 200);

    setTimeout(() => clearInterval(checkGsi), 10000);
  }

  function decodeJwtPayload(token) {
    try {
      const base64Url = token.split('.')[1];
      const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
      const jsonPayload = decodeURIComponent(atob(base64).split('').map(function (c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
      }).join(''));
      return JSON.parse(jsonPayload);
    } catch (e) {
      return null;
    }
  }

  function handleGoogleCredentialResponse(response) {
    if (!response || !response.credential) return;

    const payload = decodeJwtPayload(response.credential);
    if (!payload || !payload.email) {
      alert('No se pudo verificar la cuenta de Google. Puedes ingresar tu correo manualmente.');
      return;
    }

    googleVoterCredential = response.credential;

    if (inputVoterEmail) {
      inputVoterEmail.value = payload.email;
      inputVoterEmail.readOnly = true;
      inputVoterEmail.style.background = '#064e3b';
      inputVoterEmail.style.borderColor = '#10b981';
      inputVoterEmail.style.color = '#a7f3d0';
    }

    if (inputVoterName && (!inputVoterName.value || !inputVoterName.value.trim())) {
      inputVoterName.value = payload.name || '';
    }

    const btnContainer = document.getElementById('google-btn-container');
    const badge = document.getElementById('google-user-badge');
    const userName = document.getElementById('google-user-name');
    const userEmail = document.getElementById('google-user-email');
    const userAvatar = document.getElementById('google-user-avatar');
    const divider = document.getElementById('google-divider');

    if (btnContainer) btnContainer.style.display = 'none';
    if (divider) divider.style.display = 'none';

    if (userName) userName.textContent = payload.name || 'Usuario de Google';
    if (userEmail) userEmail.textContent = payload.email;
    if (userAvatar && payload.picture) {
      userAvatar.src = payload.picture;
      userAvatar.style.display = 'block';
    }
    if (badge) badge.style.display = 'flex';

    showToast(`✓ Conectado como ${payload.email}`, 'success');
  }

  // Listener para cambiar cuenta de Google
  const btnDisconnectGoogle = document.getElementById('btn-disconnect-google');
  if (btnDisconnectGoogle) {
    btnDisconnectGoogle.addEventListener('click', () => {
      googleVoterCredential = null;
      if (inputVoterEmail) {
        inputVoterEmail.value = '';
        inputVoterEmail.readOnly = false;
        inputVoterEmail.style.background = '';
        inputVoterEmail.style.borderColor = '';
        inputVoterEmail.style.color = '';
      }

      const btnContainer = document.getElementById('google-btn-container');
      const badge = document.getElementById('google-user-badge');
      const divider = document.getElementById('google-divider');
      const manualGroup = document.getElementById('manual-email-group');

      if (badge) badge.style.display = 'none';
      if (btnContainer) btnContainer.style.display = 'flex';

      if (!requireGoogleAuth) {
        if (divider) divider.style.display = 'flex';
        if (manualGroup) manualGroup.style.display = 'block';
      } else {
        if (divider) divider.style.display = 'none';
        if (manualGroup) manualGroup.style.display = 'none';
      }
    });
  }

  // Renderizar los álbumes y sus canciones
  function renderAlbums() {
    let filtered = allAlbums;

    // Filtro por tipo de lanzamiento
    if (currentFilter !== 'all') {
      filtered = filtered.filter(a => a.album_type === currentFilter);
    }

    // Filtro por término de búsqueda
    const q = searchQuery.toLowerCase().trim();
    if (q) {
      filtered = filtered.map(a => {
        const albumMatches = a.title.toLowerCase().includes(q) || (a.title_jp && a.title_jp.toLowerCase().includes(q));
        const matchedSongs = (a.songs || []).filter(s => {
          return s.title.toLowerCase().includes(q) || (s.title_jp && s.title_jp.toLowerCase().includes(q));
        });

        if (albumMatches || matchedSongs.length > 0) {
          return {
            ...a,
            songs: albumMatches ? a.songs : matchedSongs
          };
        }
        return null;
      }).filter(Boolean);
    }

    if (filtered.length === 0) {
      albumsContainer.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 50px 20px;">
          <p style="font-size: 16px; margin-bottom: 8px;">No se encontraron canciones o discos con "<strong>${escapeHtml(searchQuery)}</strong>"</p>
          <p style="font-size: 13px; color: var(--text-dim);">Prueba buscando con otro nombre o selecciona la pestaña "Todos".</p>
        </div>
      `;
      return;
    }

    albumsContainer.innerHTML = filtered.map(album => {
      const typeBadgeClass = 'badge-' + (album.album_type || 'studio');
      const typeNames = {
        'studio': 'Álbum Estudio',
        'mini': 'Mini Álbum (EP)',
        'single': 'Single',
        'best': 'Recopilatorio'
      };
      const typeLabel = typeNames[album.album_type] || album.album_type;

      // Cover o fallback
      let coverHtml = '';
      if (album.cover_url && album.cover_url.trim() !== '') {
        coverHtml = `<img src="${escapeHtml(album.cover_url)}" alt="${escapeHtml(album.title)}" class="album-cover-img" loading="lazy" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'album-cover-placeholder\'>💿</div>';">`;
      } else {
        coverHtml = `<div class="album-cover-placeholder">💿</div>`;
      }

      // Canciones del álbum
      const songsListHtml = (album.songs || []).map(song => {
        const isSelected = selectedSongs.has(song.id);
        const hasPreview = song.preview_audio_url && song.preview_audio_url.trim() !== '';
        const previewBtnHtml = hasPreview ? `
          <button type="button" class="btn-song-preview ${currentPreviewSongId === song.id && isPlaying ? 'playing' : ''}" data-preview-song-id="${song.id}" onclick="window.playPreviewAudio('${escapeJs(song.preview_audio_url)}', '${escapeJs(song.title)}', ${song.id}, event)">
            ${currentPreviewSongId === song.id && isPlaying ? '⏸ Pausar' : '▶ Escuchar'}
          </button>
        ` : '';

        return `
          <div class="song-row ${isSelected ? 'selected' : ''}" data-song-id="${song.id}" data-song-title="${escapeHtml(song.title)}" data-album-title="${escapeHtml(album.title)}" data-album-id="${album.id}">
            <div class="song-left">
              <div class="song-checkbox">
                <span class="song-checkbox-icon">✓</span>
              </div>
              <div class="song-details">
                <div class="song-title">
                  <span>${escapeHtml(song.title)}</span>
                  ${previewBtnHtml}
                </div>
                ${song.title_jp ? `<div class="song-title-jp">${escapeHtml(song.title_jp)}</div>` : ''}
              </div>
            </div>
            <div class="song-track-num">#${song.track_num || ''}</div>
          </div>
        `;
      }).join('');

      return `
        <div class="album-card" id="album-card-${album.id}">
          <div class="album-header">
            <div class="album-cover-box">
              ${coverHtml}
            </div>
            <div class="album-info">
              <h3 class="album-title" title="${escapeHtml(album.title)}">${escapeHtml(album.title)}</h3>
              ${album.title_jp ? `<div class="album-title-jp" title="${escapeHtml(album.title_jp)}">${escapeHtml(album.title_jp)}</div>` : ''}
              <div class="album-meta-row">
                <span class="album-type-badge ${typeBadgeClass}">${typeLabel}</span>
                <span class="album-date">${formatReleaseYear(album.release_date)}</span>
                <span style="color: var(--text-dim);">• ${album.songs ? album.songs.length : 0} temas</span>
              </div>
            </div>
          </div>
          <div class="album-songs-list">
            ${songsListHtml}
          </div>
        </div>
      `;
    }).join('');

    attachSongRowListeners();
  }

  function formatReleaseYear(dateStr) {
    if (!dateStr) return '';
    return dateStr.substring(0, 4);
  }

  function attachSongRowListeners() {
    document.querySelectorAll('.song-row').forEach(row => {
      row.addEventListener('click', (e) => {
        const songId = parseInt(row.dataset.songId, 10);
        const songTitle = row.dataset.songTitle;
        const albumTitle = row.dataset.albumTitle;
        const albumId = parseInt(row.dataset.albumId, 10);

        toggleSongSelection(songId, songTitle, albumTitle, albumId);
      });
    });
  }

  function toggleSongSelection(songId, songTitle, albumTitle, albumId) {
    if (alreadyVoted) {
      showToast('Ya has emitido tu voto anteriormente.', 'warning');
      return;
    }

    if (selectedSongs.has(songId)) {
      selectedSongs.delete(songId);
      showToast(`Quitada: "${songTitle}"`, 'info', 1800);
    } else {
      if (selectedSongs.size >= maxVotes) {
        showToast(`¡Límite alcanzado! Ya tienes ${maxVotes} canciones seleccionadas. Desmarca una para cambiarla.`, 'warning', 4000);
        return;
      }
      selectedSongs.set(songId, {
        id: songId,
        title: songTitle,
        albumTitle: albumTitle,
        albumId: albumId
      });
      showToast(`Agregada: "${songTitle}" (${selectedSongs.size}/${maxVotes})`, 'success', 2000);
    }

    savePersistedSelection();
    updateSelectionUI();
    updateSongRowClasses(songId);
  }

  function updateSongRowClasses(songId) {
    const isSelected = selectedSongs.has(songId);
    document.querySelectorAll(`.song-row[data-song-id="${songId}"]`).forEach(el => {
      if (isSelected) {
        el.classList.add('selected');
      } else {
        el.classList.remove('selected');
      }
    });
  }

  // Actualizar UI del contador y barra fija
  function updateSelectionUI() {
    const count = selectedSongs.size;
    if (counterText) {
      counterText.innerHTML = `Has seleccionado <span class="counter-accent">${count} de ${maxVotes}</span> canciones`;
    }

    const percentage = Math.min(100, Math.round((count / maxVotes) * 100));
    if (progressFill) {
      progressFill.style.width = percentage + '%';
    }

    if (btnOpenModal) {
      btnOpenModal.innerHTML = `👁️ Ver lista (${count})`;
    }

    if (btnFloatingSubmit) {
      if (count > 0 && !alreadyVoted) {
        btnFloatingSubmit.disabled = false;
        btnFloatingSubmit.innerHTML = `✨ Votar (${count})`;
      } else {
        btnFloatingSubmit.disabled = true;
        btnFloatingSubmit.innerHTML = alreadyVoted ? '✓ Ya Votaste' : `Elige tus temas (${count}/${maxVotes})`;
      }
    }

    updateRankingButtons();
  }

  // Abrir Modal de Confirmación
  function openVoteModal() {
    if (alreadyVoted) {
      showToast('Ya has registrado tu voto anteriormente.', 'warning');
      return;
    }

    if (selectedSongs.size === 0) {
      showToast('Por favor selecciona al menos una canción de la lista.', 'warning');
      return;
    }

    const requiredMsg = document.getElementById('google-required-msg');
    const divider = document.getElementById('google-divider');
    const manualGroup = document.getElementById('manual-email-group');

    if (requireGoogleAuth) {
      if (requiredMsg) requiredMsg.style.display = 'block';
      if (divider) divider.style.display = 'none';
      if (manualGroup) manualGroup.style.display = 'none';
    } else {
      if (requiredMsg) requiredMsg.style.display = 'none';
      if (!googleVoterCredential && divider) divider.style.display = 'flex';
      if (manualGroup) manualGroup.style.display = 'block';
    }

    renderModalSummary();
    modalVote.classList.add('active');
  }

  function closeVoteModal() {
    modalVote.classList.remove('active');
  }

  function renderModalSummary() {
    const items = Array.from(selectedSongs.values());
    if (items.length === 0) {
      selectedSongsSummary.innerHTML = `<div style="text-align: center; color: var(--text-muted); padding: 15px;">No hay canciones seleccionadas todavía.</div>`;
      return;
    }

    selectedSongsSummary.innerHTML = items.map((item, idx) => {
      return `
        <div class="summary-song-item" id="summary-item-${item.id}">
          <div style="min-width: 0; flex: 1;">
            <span style="color: #38bdf8; font-weight: 700; margin-right: 6px;">#${idx + 1}</span>
            <span class="summary-song-name">${escapeHtml(item.title)}</span>
            <span class="summary-album-tag">(${escapeHtml(item.albumTitle)})</span>
          </div>
          <button type="button" class="btn-remove-song" title="Quitar de mi lista" data-remove-id="${item.id}">✕</button>
        </div>
      `;
    }).join('');

    // Listener para quitar desde el modal
    selectedSongsSummary.querySelectorAll('.btn-remove-song').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.dataset.removeId, 10);
        selectedSongs.delete(id);
        savePersistedSelection();
        updateSelectionUI();
        updateSongRowClasses(id);
        renderModalSummary();

        if (selectedSongs.size === 0) {
          closeVoteModal();
        }
      });
    });
  }

  // Enviar el Voto a la API
  formSubmitVote.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (requireGoogleAuth && !googleVoterCredential) {
      alert('Para garantizar la validez de la votación (1 voto por persona), es obligatorio iniciar sesión con Google.');
      return;
    }

    const email = inputVoterEmail.value.trim();
    const name = inputVoterName ? inputVoterName.value.trim() : '';
    const nickname = inputVoterNickname ? inputVoterNickname.value.trim() : '';

    if (!email) {
      alert('Por favor ingresa o conecta tu correo electrónico.');
      if (inputVoterEmail) inputVoterEmail.focus();
      return;
    }

    if (!name) {
      alert('Por favor ingresa tu nombre.');
      if (inputVoterName) inputVoterName.focus();
      return;
    }

    const songIds = Array.from(selectedSongs.keys());
    if (songIds.length === 0) {
      alert('Debes seleccionar al menos una canción.');
      return;
    }

    btnConfirmVote.disabled = true;
    btnConfirmVote.textContent = 'Enviando tu voto...';

    try {
      const token = localStorage.getItem('kurage_voter_token') || '';
      const concertLocationEl = document.querySelector('input[name="concert_location"]:checked');
      const concertLocation = concertLocationEl ? concertLocationEl.value : 'Chile';

      const res = await fetch('api.php?action=submit_vote', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          email: email,
          name: name,
          nickname: nickname,
          concert_location: concertLocation,
          songs: songIds,
          token: token,
          google_credential: googleVoterCredential
        })
      });

      const data = await res.json();

      if (data.success) {
        alreadyVoted = true;
        if (data.token) {
          localStorage.setItem('kurage_voter_token', data.token);
        }

        closeVoteModal();
        openSuccessModal(Array.from(selectedSongs.values()));
        renderVotedBanner();
        loadLiveRanking(currentRankingLoc);
      } else {
        alert(data.error || 'Ocurrió un error al procesar tu voto.');
      }
    } catch (err) {
      alert('Error de red al enviar el voto. Por favor verifica tu conexión.');
    } finally {
      btnConfirmVote.disabled = false;
      btnConfirmVote.textContent = '🚀 Confirmar y Enviar Voto';
    }
  });

  // Modal de éxito
  function openSuccessModal(votedSongs) {
    if (successSongsList) {
      successSongsList.innerHTML = votedSongs.map((s, idx) => {
        return `<li style="padding: 4px 0; font-size: 13px; color: #e2e8f0;"><strong>#${idx + 1}</strong> ${escapeHtml(s.title)} <span style="color: var(--text-dim); font-size: 11px;">(${escapeHtml(s.albumTitle)})</span></li>`;
      }).join('');
    }
    modalSuccess.classList.add('active');
  }

  if (btnCloseSuccess) {
    btnCloseSuccess.addEventListener('click', () => {
      modalSuccess.classList.remove('active');
    });
  }

  // Filtros por pestaña
  filterTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      filterTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      currentFilter = tab.dataset.filter;
      renderAlbums();
    });
  });

  // Buscador en vivo con debounce
  let searchTimer = null;
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        searchQuery = searchInput.value;
        renderAlbums();
      }, 180);
    });
  }

  // Listeners de modales
  if (btnOpenModal) btnOpenModal.addEventListener('click', openVoteModal);
  if (btnFloatingSubmit) btnFloatingSubmit.addEventListener('click', openVoteModal);
  if (btnCloseModal) btnCloseModal.addEventListener('click', closeVoteModal);
  if (btnCancelVote) btnCancelVote.addEventListener('click', closeVoteModal);

  // Cerrar al clickear fuera del modal
  window.addEventListener('click', (e) => {
    if (e.target === modalVote) closeVoteModal();
    if (e.target === modalSuccess) modalSuccess.classList.remove('active');
  });

  // ==========================================
  // RANKING EN VIVO (CANCIONES MÁS VOTADAS)
  // ==========================================
  let currentRankingLoc = '';
  const rankingCardsContainer = document.getElementById('ranking-cards-container');
  const rankingFilterPills = document.querySelectorAll('#ranking-filter-pills .ranking-pill-btn');

  rankingFilterPills.forEach(pill => {
    pill.addEventListener('click', () => {
      rankingFilterPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      currentRankingLoc = pill.dataset.rankingLoc || '';
      loadLiveRanking(currentRankingLoc);
    });
  });

  async function loadLiveRanking(loc = '') {
    if (!rankingCardsContainer) return;

    try {
      const res = await fetch(`api.php?action=get_ranking_preview&location=${encodeURIComponent(loc)}`);
      const data = await res.json();

      if (data.success && data.top_songs && data.top_songs.length > 0) {
        rankingCardsContainer.innerHTML = data.top_songs.map((song, idx) => {
          const pos = idx + 1;
          const posClass = pos <= 3 ? `pos-${pos}` : '';
          const posLabel = pos === 1 ? '🥇' : (pos === 2 ? '🥈' : (pos === 3 ? '🥉' : `#${pos}`));
          const isSelected = selectedSongs.has(song.song_id);

          const coverImg = song.cover_url ? 
            `<img src="${escapeHtml(song.cover_url)}" alt="" class="ranking-card-cover" onerror="this.src='assets/favicon-kurage.png'">` :
            `<div class="ranking-card-cover" style="display:flex;align-items:center;justify-content:center;font-size:16px;">💿</div>`;

          const hasPreview = song.preview_audio_url && song.preview_audio_url.trim() !== '';
          const previewBtn = hasPreview ? `
            <button type="button" class="btn-song-preview ${currentPreviewSongId === song.song_id && isPlaying ? 'playing' : ''}" 
                    style="margin: 0; padding: 2px 7px; font-size: 10px;"
                    title="Escuchar tema"
                    onclick="window.playPreviewAudio('${escapeJs(song.preview_audio_url)}', '${escapeJs(song.song_title)}', ${song.song_id}, event)">
              ${currentPreviewSongId === song.song_id && isPlaying ? '⏸' : '▶'}
            </button>
          ` : '';

          return `
            <div class="ranking-card" data-ranking-song-id="${song.song_id}">
              <div class="ranking-card-top">
                <div class="ranking-card-pos ${posClass}">${posLabel}</div>
                ${coverImg}
                <div class="ranking-card-info">
                  <div class="ranking-card-title" title="${escapeHtml(song.song_title)}">${escapeHtml(song.song_title)}</div>
                  <div class="ranking-card-album" title="${escapeHtml(song.album_title)}">${escapeHtml(song.album_title)}</div>
                </div>
              </div>
              <div class="ranking-card-bottom">
                <span class="ranking-card-votes">🔥 ${song.votes_count} ${song.votes_count === 1 ? 'voto' : 'votos'}</span>
                <div class="ranking-card-actions">
                  ${previewBtn}
                  <button type="button" class="ranking-card-btn-add ${isSelected ? 'selected' : ''}" 
                          data-song-id="${song.song_id}" 
                          data-song-title="${escapeHtml(song.song_title)}"
                          data-album-title="${escapeHtml(song.album_title)}"
                          data-album-id="${song.album_id}">
                    ${isSelected ? '✓ Elegida' : '+ Elegir'}
                  </button>
                </div>
              </div>
            </div>
          `;
        }).join('');

        // Listeners para los botones "+ Elegir" dentro del ranking
        rankingCardsContainer.querySelectorAll('.ranking-card-btn-add').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const sid = parseInt(btn.dataset.songId, 10);
            const stitle = btn.dataset.songTitle;
            const atitle = btn.dataset.albumTitle;
            const aid = parseInt(btn.dataset.albumId, 10);
            toggleSongSelection(sid, stitle, atitle, aid);
            updateRankingButtons();
          });
        });
      } else {
        rankingCardsContainer.innerHTML = `
          <div class="ranking-empty">
            ${loc ? `Aún no hay votos registrados para ${loc}. ¡Sé la primera persona en votar!` : 'Aún no hay votos registrados. ¡Sé de las primeras personas en elegir tus temas favoritos!'}
          </div>
        `;
      }
    } catch (e) {
      rankingCardsContainer.innerHTML = `<div class="ranking-empty">No se pudo cargar el ranking en vivo.</div>`;
    }
  }

  function updateRankingButtons() {
    if (!rankingCardsContainer) return;
    rankingCardsContainer.querySelectorAll('.ranking-card-btn-add').forEach(btn => {
      const sid = parseInt(btn.dataset.songId, 10);
      if (selectedSongs.has(sid)) {
        btn.classList.add('selected');
        btn.textContent = '✓ Elegida';
      } else {
        btn.classList.remove('selected');
        btn.textContent = '+ Elegir';
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

  // Inicializar
  checkVoteStatus();
  loadDiscography();

})();
