// Admin Controller para PT:Kurage
document.addEventListener('DOMContentLoaded', () => {
  let isAuth = false;
  let parsedExcelData = [];

  // Elementos DOM Principales
  const loginSection = document.getElementById('login-section');
  const adminSection = document.getElementById('admin-section');
  const loginForm = document.getElementById('login-form');
  const loginPassword = document.getElementById('login-password');
  const loginError = document.getElementById('login-error');
  const btnLogout = document.getElementById('btn-logout');

  // Pestañas
  const navTabs = document.querySelectorAll('.nav-tab');
  const tabContents = document.querySelectorAll('.tab-content');

  // Excel & Registros
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('file-input');
  const modalImport = document.getElementById('modal-import');
  const selectColEmail = document.getElementById('select-col-email');
  const selectColCode = document.getElementById('select-col-code');
  const selectColCountry = document.getElementById('select-col-country');
  const previewTableHead = document.getElementById('preview-table-head');
  const previewTableBody = document.getElementById('preview-table-body');
  const btnConfirmImport = document.getElementById('btn-confirm-import');
  const btnCancelImport = document.getElementById('btn-cancel-import');
  const checkReplaceAll = document.getElementById('check-replace-all');
  const importSummary = document.getElementById('import-summary');

  // Tabla Registros
  const recordsTbody = document.getElementById('records-tbody');
  const searchRecords = document.getElementById('search-records');
  const filterCountry = document.getElementById('filter-country');
  const filterStatus = document.getElementById('filter-status');
  const selectPerPage = document.getElementById('select-per-page');
  const tablePaginationBar = document.getElementById('table-pagination-bar');
  const paginationInfo = document.getElementById('pagination-info');
  const paginationControls = document.getElementById('pagination-controls');
  const btnRefreshRecords = document.getElementById('btn-refresh-records');
  const btnAddRecord = document.getElementById('btn-add-record');
  const btnClearAll = document.getElementById('btn-clear-all');
  const btnExportCsv = document.getElementById('btn-export-csv');
  const checkAllRecords = document.getElementById('check-all-records');
  const btnDeleteSelected = document.getElementById('btn-delete-selected');
  const selectedCountSpan = document.getElementById('selected-count');
  const btnSendSelected = document.getElementById('btn-send-selected');
  const sendSelectedCountSpan = document.getElementById('send-selected-count');
  const btnSelectPendingPage = document.getElementById('btn-select-pending-page');
  let selectedRecordIds = new Set();
  let currentLoadedItems = [];

  // Modal Envío Masivo
  const modalBulkSend = document.getElementById('modal-bulk-send');
  const btnCloseBulkModal = document.getElementById('btn-close-bulk-modal');
  const btnCancelBulk = document.getElementById('btn-cancel-bulk');
  const btnStartBulk = document.getElementById('btn-start-bulk');
  const btnPauseBulk = document.getElementById('btn-pause-bulk');
  const bulkTotalSelected = document.getElementById('bulk-total-selected');
  const bulkValidCount = document.getElementById('bulk-valid-count');
  const bulkNocodeCount = document.getElementById('bulk-nocode-count');
  const bulkAlreadySentCount = document.getElementById('bulk-alreadysent-count');
  const bulkSkipAlreadySent = document.getElementById('bulk-skip-already-sent');
  const bulkProgressContainer = document.getElementById('bulk-progress-container');
  const bulkProgressStatus = document.getElementById('bulk-progress-status');
  const bulkProgressPercentage = document.getElementById('bulk-progress-percentage');
  const bulkProgressBar = document.getElementById('bulk-progress-bar');
  const bulkStatsCounters = document.getElementById('bulk-stats-counters');
  const bulkEta = document.getElementById('bulk-eta');
  const bulkLogContainer = document.getElementById('bulk-log-container');
  const bulkLogBox = document.getElementById('bulk-log-box');

  // Modal Registro Manual
  const modalRecord = document.getElementById('modal-record');
  const formRecord = document.getElementById('form-record');
  const recordId = document.getElementById('record-id');
  const recordEmail = document.getElementById('record-email');
  const recordCode = document.getElementById('record-code');
  const recordCountry = document.getElementById('record-country');
  const btnCancelRecord = document.getElementById('btn-cancel-record');

  // Plantilla Correo
  const mailSubject = document.getElementById('mail-subject');
  const mailFromName = document.getElementById('mail-from-name');
  const mailFromEmail = document.getElementById('mail-from-email');
  const mailTemplate = document.getElementById('mail-template');
  const btnSaveTemplate = document.getElementById('btn-save-template');
  const btnResetTemplate = document.getElementById('btn-reset-template');
  const previewIframe = document.getElementById('preview-iframe');
  const tabEditorCode = document.getElementById('tab-editor-code');
  const tabEditorPreview = document.getElementById('tab-editor-preview');
  const editorCodePane = document.getElementById('editor-code-pane');
  const editorPreviewPane = document.getElementById('editor-preview-pane');
  const testEmailInput = document.getElementById('test-email-input');
  const btnSendTest = document.getElementById('btn-send-test');
  const testEmailResult = document.getElementById('test-email-result');

  // SMTP & Seguridad
  const formSettings = document.getElementById('form-settings');
  const mailMethod = document.getElementById('mail-method');
  const smtpSettingsFields = document.getElementById('smtp-settings-fields');
  const formPassword = document.getElementById('form-password');

  // Sistema de Notificaciones Toast Flotante
  function showToast(message, type = 'info', duration = 4000) {
    let container = document.querySelector('.kurage-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'kurage-toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `kurage-toast ${type}`;
    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '❌';
    if (type === 'warning') icon = '⚠️';

    toast.innerHTML = `<span style="font-size: 16px;">${icon}</span><div style="flex:1; line-height: 1.4;">${escapeHtml(message)}</div>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px) scale(0.95)';
      setTimeout(() => toast.remove(), 260);
    }, duration);
  }

  // 1. Verificación Inicial de Autenticación
  checkAuth();

  async function checkAuth() {
    try {
      const res = await fetch('api.php?action=admin_check_auth');
      const data = await res.json();
      if (data.authenticated) {
        setLoggedIn(true);
      } else {
        setLoggedIn(false);
      }
    } catch (e) {
      setLoggedIn(false);
    }
  }

  function setLoggedIn(logged) {
    isAuth = logged;
    if (logged) {
      loginSection.style.display = 'none';
      adminSection.style.display = 'block';
      loadStats();
      loadCountries();
      updateSortHeaderUI();
      loadRecords();
      loadTemplate();
      loadSettings();
    } else {
      loginSection.style.display = 'flex';
      adminSection.style.display = 'none';
    }
  }

  // Login Form
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
        setLoggedIn(true);
      } else {
        loginError.textContent = data.error || 'Contraseña incorrecta';
        loginError.style.display = 'block';
      }
    } catch (err) {
      loginError.textContent = 'Error de conexión con el servidor.';
      loginError.style.display = 'block';
    }
  });

  btnLogout.addEventListener('click', async () => {
    await fetch('api.php?action=admin_logout', { method: 'POST' });
    setLoggedIn(false);
  });

  // Navegación de Pestañas
  navTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;
      navTabs.forEach(t => t.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));

      tab.classList.add('active');
      document.getElementById('tab-' + target).classList.add('active');

      if (target === 'template') {
        updateMailPreview();
      }
    });
  });

  // Estadísticas
  async function loadStats() {
    try {
      const res = await fetch('api.php?action=admin_get_stats');
      const data = await res.json();
      if (data.success && data.stats) {
        const setVal = (id, val) => {
          const el = document.getElementById(id);
          if (el) el.textContent = (val !== undefined && val !== null) ? val : 0;
        };
        setVal('stat-total', data.stats.total);
        setVal('stat-sent', data.stats.sent_users);
        setVal('stat-total-sends', data.stats.total_sends);
        setVal('stat-checked', data.stats.checked);
        setVal('stat-delivered', data.stats.delivered);
        setVal('stat-opened', data.stats.opened);
        setVal('stat-bounced', data.stats.bounced);
      }
    } catch (e) {}
  }

  // Cargar Registros con Paginación y Ordenamiento
  let currentPage = 1;
  let currentPerPage = 50;
  let currentSortBy = 'id';
  let currentSortDir = 'DESC';

  if (selectPerPage) {
    selectPerPage.addEventListener('change', () => {
      currentPerPage = parseInt(selectPerPage.value, 10) || 50;
      loadRecords(searchRecords.value.trim(), 1);
    });
  }

  // Click en cabeceras de columnas para ordenar
  document.querySelectorAll('.sortable-th').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.sort;
      if (!col) return;

      if (currentSortBy === col) {
        currentSortDir = currentSortDir === 'ASC' ? 'DESC' : 'ASC';
      } else {
        currentSortBy = col;
        // Por defecto: email, código, país y estado A-Z (ASC); reenvíos, fecha envío y fecha registro primero mayores/recientes (DESC)
        currentSortDir = (col === 'email' || col === 'code' || col === 'country' || col === 'delivery_status') ? 'ASC' : 'DESC';
      }

      updateSortHeaderUI();
      loadRecords(searchRecords.value.trim(), 1);
    });
  });

  function updateSortHeaderUI() {
    document.querySelectorAll('.sortable-th').forEach(th => {
      const col = th.dataset.sort;
      const icon = th.querySelector('.sort-icon');
      if (col === currentSortBy) {
        th.classList.add('active-sort');
        if (icon) icon.textContent = currentSortDir === 'ASC' ? '▲' : '▼';
      } else {
        th.classList.remove('active-sort');
        if (icon) icon.textContent = '↕';
      }
    });
  }

  async function loadRecords(search = '', page = 1) {
    currentPage = page;
    const country = filterCountry ? filterCountry.value : '';
    const status = filterStatus ? filterStatus.value : '';
    try {
      recordsTbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">Cargando registros...</td></tr>`;
      const url = `api.php?action=admin_get_records&search=${encodeURIComponent(search)}&country=${encodeURIComponent(country)}&status=${encodeURIComponent(status)}&page=${page}&per_page=${currentPerPage}&sort_by=${encodeURIComponent(currentSortBy)}&sort_dir=${encodeURIComponent(currentSortDir)}`;
      const res = await fetch(url);
      const data = await res.json();
      if (data.success) {
        renderRecords(data.data.items);
        renderPagination(data.data.total, data.data.page, data.data.per_page);
      } else {
        recordsTbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: #ef4444; padding: 30px;">${escapeHtml(data.error || 'Error al cargar registros')}</td></tr>`;
      }
    } catch (e) {
      recordsTbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: #ef4444; padding: 30px;">Error de conexión con el servidor.</td></tr>`;
    }
  }

  async function loadCountries() {
    if (!filterCountry) return;
    try {
      const res = await fetch('api.php?action=admin_get_countries');
      const data = await res.json();
      if (data.success && data.countries) {
        const currentVal = filterCountry.value;
        let totalCount = 0;
        data.countries.forEach(c => totalCount += c.count);

        let html = `<option value="">🌍 Todos los países (${totalCount})</option>`;
        data.countries.forEach(c => {
          if (c.country_name === '__empty__') {
            html += `<option value="__empty__">⚪ (Sin país) (${c.count})</option>`;
          } else {
            html += `<option value="${escapeHtml(c.country_name)}">${escapeHtml(c.country_name)} (${c.count})</option>`;
          }
        });
        filterCountry.innerHTML = html;
        if (currentVal) {
          filterCountry.value = currentVal;
        }
      }
    } catch (e) {
      // Ignorar error de red silencioso
    }
  }

  if (filterCountry) {
    filterCountry.addEventListener('change', () => {
      selectedRecordIds.clear();
      updateDeleteSelectedButton();
      loadRecords(searchRecords.value.trim(), 1);
    });
  }

  if (filterStatus) {
    filterStatus.addEventListener('change', () => {
      selectedRecordIds.clear();
      updateDeleteSelectedButton();
      loadRecords(searchRecords.value.trim(), 1);
    });
  }

  function renderDeliveryBadge(r) {
    const status = (r.delivery_status || 'pending').toLowerCase();
    const sentCount = parseInt(r.sent_count || 0, 10);

    if (status === 'opened' || status === 'clicked') {
      return `<span class="badge" title="Abierto / Leído por el destinatario" style="background: rgba(168,85,247,0.15); color: #c084fc; border: 1px solid rgba(168,85,247,0.3); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">🟣 Abierto</span>`;
    } else if (status === 'delivered') {
      return `<span class="badge" title="Entregado en la bandeja de entrada del destinatario" style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">🟢 Entregado</span>`;
    } else if (status === 'bounced') {
      const reasonAttr = r.bounce_reason ? ` - Motivo: ${escapeHtml(r.bounce_reason)}` : '';
      return `<span class="badge" title="Rebotado${reasonAttr}" style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; cursor: help;">🔴 Rebotado ℹ️</span>`;
    } else if (status === 'sent' || sentCount > 0) {
      return `<span class="badge" title="Enviado a Resend, en proceso de entrega" style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">🟡 Enviado</span>`;
    } else {
      return `<span style="color: #64748b; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">⚪ Sin enviar</span>`;
    }
  }

  function formatChileDateTime(utcStr) {
    if (!utcStr || utcStr === 'Nunca' || utcStr === '-') return '<span style="color: var(--text-muted); font-size: 11px;">Nunca</span>';

    let str = String(utcStr).trim();
    if (!str.includes('T') && str.includes(' ')) {
      str = str.replace(' ', 'T') + 'Z';
    } else if (!str.endsWith('Z') && !str.includes('+')) {
      str += 'Z';
    }

    const d = new Date(str);
    if (isNaN(d.getTime())) return utcStr;

    try {
      const formatter = new Intl.DateTimeFormat('es-CL', {
        timeZone: 'America/Santiago',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      });

      const parts = formatter.formatToParts(d);
      const get = (type) => parts.find(p => p.type === type)?.value || '00';
      const time = `${get('hour')}:${get('minute')}:${get('second')}`;
      const date = `${get('day')}/${get('month')}/${get('year')}`;

      // Formato hora fecha solicitado: "HH:mm:ss DD/MM/YYYY"
      return `<div class="cell-datetime"><span class="cell-time">${time}</span><span class="cell-date">${date}</span></div>`;
    } catch (e) {
      const offsetMs = 3 * 3600 * 1000;
      const chileDate = new Date(d.getTime() - offsetMs);
      const pad = (n) => String(n).padStart(2, '0');
      const time = `${pad(chileDate.getUTCHours())}:${pad(chileDate.getUTCMinutes())}:${pad(chileDate.getUTCSeconds())}`;
      const date = `${pad(chileDate.getUTCDate())}/${pad(chileDate.getUTCMonth() + 1)}/${chileDate.getUTCFullYear()}`;
      return `<div class="cell-datetime"><span class="cell-time">${time}</span><span class="cell-date">${date}</span></div>`;
    }
  }

  function renderRecords(items) {
    currentLoadedItems = items || [];
    if (!items || items.length === 0) {
      recordsTbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">No se encontraron registros. Sube un archivo Excel o añade uno manualmente.</td></tr>`;
      updateCheckAllStatus();
      updateDeleteSelectedButton();
      return;
    }

    recordsTbody.innerHTML = items.map(r => {
      const isChecked = selectedRecordIds.has(r.id);
      return `
      <tr class="${isChecked ? 'row-selected' : ''}">
        <td class="col-checkbox" style="text-align: center; vertical-align: middle;">
          <input type="checkbox" class="record-checkbox" value="${r.id}" ${isChecked ? 'checked' : ''} style="cursor: pointer; width: 15px; height: 15px; vertical-align: middle;">
        </td>
        <td class="col-email" style="font-weight: 500;" title="${escapeHtml(r.email)}">
          <span class="cell-truncate">${escapeHtml(r.email)}</span>
        </td>
        <td class="col-code">
          ${r.code ? `<span class="code-badge">${escapeHtml(r.code)}</span>` : '<span style="color: #64748b; font-size: 11px; font-style: italic;">Sin código</span>'}
        </td>
        <td class="col-country" style="white-space: nowrap;"><span style="font-size: 13px; color: ${r.country ? '#e2e8f0' : '#64748b'};">${escapeHtml(r.country || '-')}</span></td>
        <td class="col-status" id="delivery-status-${r.id}" style="white-space: nowrap;">${renderDeliveryBadge(r)}</td>
        <td class="col-sent" id="sent-count-${r.id}" style="white-space: nowrap; text-align: center;">${r.sent_count > 0 ? `<span style="color: #34d399; font-weight: 600;">${r.sent_count} vez/ces</span>` : '<span style="color: #64748b;">0</span>'}</td>
        <td class="col-date" id="last-sent-${r.id}" style="white-space: nowrap;">${r.last_sent_at ? formatChileDateTime(r.last_sent_at) : '<span style="color: var(--text-muted); font-size: 11px;">Nunca</span>'}</td>
        <td class="col-date" style="white-space: nowrap;">${formatChileDateTime(r.created_at)}</td>
        <td class="col-actions">
          <div class="row-actions-group">
            <button id="btn-resend-${r.id}" class="btn btn-resend" title="Reenviar correo oficial con código" onclick="window.resendSingleRecord(${r.id}, '${escapeJs(r.email)}')">✉ Reenviar</button>
            <button class="btn btn-secondary btn-icon" title="Editar" aria-label="Editar" onclick="window.editRecord(${r.id}, '${escapeJs(r.email)}', '${escapeJs(r.code || '')}', '${escapeJs(r.country || '')}')">✏️</button>
            <button class="btn btn-danger btn-icon" title="Borrar" aria-label="Borrar" onclick="window.deleteRecord(${r.id})">🗑️</button>
          </div>
        </td>
      </tr>
      `;
    }).join('');

    updateCheckAllStatus();
    updateDeleteSelectedButton();
  }

  // Delegación de eventos para checkboxes individuales
  recordsTbody.addEventListener('change', (e) => {
    if (e.target && e.target.classList.contains('record-checkbox')) {
      const id = parseInt(e.target.value, 10);
      if (e.target.checked) {
        selectedRecordIds.add(id);
        e.target.closest('tr')?.classList.add('row-selected');
      } else {
        selectedRecordIds.delete(id);
        e.target.closest('tr')?.classList.remove('row-selected');
      }
      updateCheckAllStatus();
      updateDeleteSelectedButton();
    }
  });

  // Checkbox de selección completa (cabecera)
  if (checkAllRecords) {
    checkAllRecords.addEventListener('change', () => {
      const isChecked = checkAllRecords.checked;
      currentLoadedItems.forEach(r => {
        if (isChecked) {
          selectedRecordIds.add(r.id);
        } else {
          selectedRecordIds.delete(r.id);
        }
      });
      document.querySelectorAll('.record-checkbox').forEach(cb => {
        cb.checked = isChecked;
        if (isChecked) {
          cb.closest('tr')?.classList.add('row-selected');
        } else {
          cb.closest('tr')?.classList.remove('row-selected');
        }
      });
      updateDeleteSelectedButton();
    });
  }

  function updateCheckAllStatus() {
    if (!checkAllRecords) return;
    if (!currentLoadedItems || currentLoadedItems.length === 0) {
      checkAllRecords.checked = false;
      checkAllRecords.indeterminate = false;
      return;
    }
    const pageIds = currentLoadedItems.map(r => r.id);
    const countSelectedOnPage = pageIds.filter(id => selectedRecordIds.has(id)).length;
    if (countSelectedOnPage === 0) {
      checkAllRecords.checked = false;
      checkAllRecords.indeterminate = false;
    } else if (countSelectedOnPage === pageIds.length) {
      checkAllRecords.checked = true;
      checkAllRecords.indeterminate = false;
    } else {
      checkAllRecords.checked = false;
      checkAllRecords.indeterminate = true;
    }
  }

  function updateDeleteSelectedButton() {
    const count = selectedRecordIds.size;
    if (count > 0) {
      if (selectedCountSpan) selectedCountSpan.textContent = count;
      if (btnDeleteSelected) btnDeleteSelected.style.display = 'inline-flex';

      if (sendSelectedCountSpan) sendSelectedCountSpan.textContent = count;
      if (btnSendSelected) btnSendSelected.style.display = 'inline-flex';
    } else {
      if (btnDeleteSelected) btnDeleteSelected.style.display = 'none';
      if (btnSendSelected) btnSendSelected.style.display = 'none';
    }
  }

  function renderPagination(total, page, perPage) {
    if (!tablePaginationBar) return;

    if (total <= 0) {
      tablePaginationBar.style.display = 'none';
      return;
    }

    tablePaginationBar.style.display = 'flex';
    const totalPages = Math.ceil(total / perPage);
    const startItem = (page - 1) * perPage + 1;
    const endItem = Math.min(page * perPage, total);

    paginationInfo.textContent = `Mostrando ${startItem.toLocaleString('es-CL')}-${endItem.toLocaleString('es-CL')} de ${total.toLocaleString('es-CL')} registros (Pág. ${page} de ${totalPages})`;

    if (totalPages <= 1) {
      paginationControls.innerHTML = '';
      return;
    }

    let html = '';

    // Primera página
    const firstDisabled = page <= 1 ? 'disabled' : '';
    html += `<button type="button" class="pagination-btn" ${firstDisabled} onclick="window.goToPage(1)" title="Primera página">«</button>`;

    // Anterior
    const prevDisabled = page <= 1 ? 'disabled' : '';
    html += `<button type="button" class="pagination-btn" ${prevDisabled} onclick="window.goToPage(${page - 1})" title="Página anterior">‹ Ant</button>`;

    // Números de página inteligentes (máximo 5 botones visibles)
    const maxButtons = 5;
    let startPage = Math.max(1, page - 2);
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);

    if (endPage - startPage < maxButtons - 1) {
      startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
      html += `<button type="button" class="pagination-btn" onclick="window.goToPage(1)">1</button>`;
      if (startPage > 2) {
        html += `<span class="pagination-ellipsis">...</span>`;
      }
    }

    for (let p = startPage; p <= endPage; p++) {
      const activeClass = p === page ? 'active' : '';
      html += `<button type="button" class="pagination-btn ${activeClass}" onclick="window.goToPage(${p})">${p}</button>`;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        html += `<span class="pagination-ellipsis">...</span>`;
      }
      html += `<button type="button" class="pagination-btn" onclick="window.goToPage(${totalPages})">${totalPages}</button>`;
    }

    // Siguiente
    const nextDisabled = page >= totalPages ? 'disabled' : '';
    html += `<button type="button" class="pagination-btn" ${nextDisabled} onclick="window.goToPage(${page + 1})" title="Página siguiente">Sig ›</button>`;

    // Última página
    const lastDisabled = page >= totalPages ? 'disabled' : '';
    html += `<button type="button" class="pagination-btn" ${lastDisabled} onclick="window.goToPage(${totalPages})" title="Última página">»</button>`;

    paginationControls.innerHTML = html;
  }

  window.goToPage = (page) => {
    loadRecords(searchRecords.value.trim(), page);
    // Scroll suave a la tabla
    document.querySelector('.table-responsive')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  let searchDebounceTimer = null;
  searchRecords.addEventListener('input', () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
      loadRecords(searchRecords.value.trim(), 1);
    }, 250);
  });

  btnRefreshRecords.addEventListener('click', () => {
    loadRecords(searchRecords.value.trim(), currentPage);
    loadStats();
    loadCountries();
  });

  const btnSyncResend = document.getElementById('btn-sync-resend');
  if (btnSyncResend) {
    btnSyncResend.addEventListener('click', async () => {
      const originalText = btnSyncResend.innerHTML;
      btnSyncResend.disabled = true;
      btnSyncResend.innerHTML = '🔄 Sincronizando...';

      try {
        const res = await fetch('api.php?action=admin_sync_resend', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
          showToast(data.message || 'Sincronización con Resend completada.', 'success', 5000);
          loadRecords(searchRecords.value.trim(), currentPage);
          loadStats();
        } else if (data.restricted) {
          alert(`⚠️ Aviso sobre tu API Key de Resend:\n\n${data.error}`);
        } else {
          showToast(data.error || 'Error al sincronizar con Resend.', 'error', 6000);
        }
      } catch (err) {
        showToast('Error de red al consultar Resend: ' + err.message, 'error');
      } finally {
        btnSyncResend.disabled = false;
        btnSyncResend.innerHTML = originalText;
      }
    });
  }

  btnExportCsv.addEventListener('click', () => {
    window.location.href = 'api.php?action=admin_export_csv';
  });

  // Modal Registro Manual
  btnAddRecord.addEventListener('click', () => {
    recordId.value = '';
    recordEmail.value = '';
    recordCode.value = '';
    if (recordCountry) recordCountry.value = '';
    document.getElementById('modal-record-title').textContent = 'Nuevo Registro';
    modalRecord.style.display = 'flex';
  });

  btnCancelRecord.addEventListener('click', () => {
    modalRecord.style.display = 'none';
  });

  window.editRecord = (id, email, code, country = '') => {
    recordId.value = id;
    recordEmail.value = email;
    recordCode.value = code;
    if (recordCountry) recordCountry.value = country || '';
    document.getElementById('modal-record-title').textContent = 'Editar Registro';
    modalRecord.style.display = 'flex';
  };

  window.deleteRecord = async (id) => {
    if (!confirm('¿Seguro que deseas eliminar este registro?')) return;
    const fd = new FormData();
    fd.append('id', id);
    const res = await fetch('api.php?action=admin_delete_record', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      selectedRecordIds.delete(id);
      updateDeleteSelectedButton();
      loadRecords(searchRecords.value.trim(), currentPage);
      loadStats();
    } else {
      alert(data.error || 'Error al eliminar');
    }
  };

  // Reenvío Individual desde cada fila
  window.resendSingleRecord = async (id, email) => {
    const item = currentLoadedItems.find(x => x.id === id);
    if (item && (!item.code || item.code.trim() === '' || item.code.trim() === '-')) {
      alert(`El registro ${email} no tiene código de descuento asignado (no aplica reenvío).`);
      return;
    }

    if (!confirm(`¿Deseas reenviar el correo oficial con código de descuento a ${email}?`)) {
      return;
    }

    const btn = document.getElementById(`btn-resend-${id}`);
    const originalText = btn ? btn.innerHTML : '✉ Reenviar';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = 'Enviando...';
    }

    try {
      const fd = new FormData();
      fd.append('id', id);
      fd.append('email', email);

      const res = await fetch('api.php?action=admin_resend_single', {
        method: 'POST',
        body: fd
      });
      const data = await res.json();

      if (data.success) {
        showToast(data.message || `Correo enviado exitosamente a ${email}`, 'success');

        if (item) {
          item.sent_count = (item.sent_count || 0) + 1;
          item.last_sent_at = data.record?.last_sent_at || new Date().toISOString();
        }

        const countCell = document.getElementById(`sent-count-${id}`);
        if (countCell) {
          const newCount = data.record?.sent_count || (item ? item.sent_count : 1);
          countCell.innerHTML = `<span style="color: #34d399; font-weight: 600;">${newCount} vez/ces</span>`;
        }

        const dateCell = document.getElementById(`last-sent-${id}`);
        if (dateCell && data.record?.last_sent_at) {
          dateCell.innerHTML = formatChileDateTime(data.record.last_sent_at);
        }

        const statusCell = document.getElementById(`delivery-status-${id}`);
        if (statusCell) {
          statusCell.innerHTML = `<span class="badge" title="Enviado a Resend, en proceso de entrega" style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">🟡 Enviado</span>`;
        }

        loadStats();
      } else {
        showToast(data.error || 'Error al enviar el correo.', 'error', 6000);
      }
    } catch (err) {
      showToast('Error de red al enviar el correo: ' + err.message, 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    }
  };

  if (btnDeleteSelected) {
    btnDeleteSelected.addEventListener('click', async () => {
      const count = selectedRecordIds.size;
      if (count === 0) return;

      if (!confirm(`¿Seguro que deseas eliminar los ${count} registros seleccionados? Esta acción es permanente.`)) {
        return;
      }

      btnDeleteSelected.disabled = true;
      btnDeleteSelected.textContent = 'Eliminando...';

      try {
        const res = await fetch('api.php?action=admin_delete_batch', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ids: Array.from(selectedRecordIds) })
        });
        const data = await res.json();
        btnDeleteSelected.disabled = false;
        if (data.success) {
          selectedRecordIds.clear();
          updateDeleteSelectedButton();
          loadRecords(searchRecords.value.trim(), currentPage);
          loadStats();
        } else {
          alert(data.error || 'Error al eliminar registros.');
          updateDeleteSelectedButton();
        }
      } catch (err) {
        btnDeleteSelected.disabled = false;
        alert('Error de conexión con el servidor.');
        updateDeleteSelectedButton();
      }
    });
  }

  // Botón para seleccionar automáticamente registros pendientes de la página actual
  if (btnSelectPendingPage) {
    btnSelectPendingPage.addEventListener('click', () => {
      if (!currentLoadedItems || currentLoadedItems.length === 0) {
        showToast('No hay registros cargados en esta página.', 'warning');
        return;
      }

      let count = 0;
      currentLoadedItems.forEach(r => {
        const sentCount = parseInt(r.sent_count || 0, 10);
        const hasCode = r.code && r.code.trim() !== '' && r.code.trim() !== '-';
        if (sentCount === 0 && hasCode) {
          selectedRecordIds.add(r.id);
          count++;
        }
      });

      document.querySelectorAll('.record-checkbox').forEach(cb => {
        const id = parseInt(cb.value, 10);
        if (selectedRecordIds.has(id)) {
          cb.checked = true;
          cb.closest('tr')?.classList.add('row-selected');
        }
      });

      updateCheckAllStatus();
      updateDeleteSelectedButton();

      if (count > 0) {
        showToast(`Se seleccionaron ${count} registros pendientes en esta página.`, 'success');
      } else {
        showToast('Todos los registros con código de esta página ya han recibido correo.', 'info');
      }
    });
  }

  // Controlador de Envío Masivo
  let bulkSelectedRecords = [];
  let isBulkRunning = false;
  let isBulkPaused = false;
  let bulkShouldStop = false;

  if (btnSendSelected) {
    btnSendSelected.addEventListener('click', async () => {
      const ids = Array.from(selectedRecordIds);
      if (ids.length === 0) return;

      if (!modalBulkSend) return;
      modalBulkSend.style.display = 'flex';

      if (bulkTotalSelected) bulkTotalSelected.textContent = ids.length;
      if (bulkValidCount) bulkValidCount.textContent = '...';
      if (bulkNocodeCount) bulkNocodeCount.textContent = '...';
      if (bulkAlreadySentCount) bulkAlreadySentCount.textContent = '...';

      if (bulkProgressContainer) bulkProgressContainer.style.display = 'none';
      if (bulkLogContainer) bulkLogContainer.style.display = 'none';
      if (bulkLogBox) bulkLogBox.innerHTML = '';
      if (btnStartBulk) {
        btnStartBulk.disabled = false;
        btnStartBulk.textContent = '🚀 Iniciar Envío Masivo';
        btnStartBulk.style.display = 'inline-flex';
      }
      if (btnPauseBulk) btnPauseBulk.style.display = 'none';
      if (btnCancelBulk) btnCancelBulk.textContent = 'Cerrar';

      isBulkRunning = false;
      isBulkPaused = false;
      bulkShouldStop = false;

      try {
        const res = await fetch('api.php?action=admin_get_records_by_ids', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ids: ids })
        });
        const data = await res.json();
        if (data.success && data.records) {
          bulkSelectedRecords = data.records;
          updateBulkModalCounters();
        } else {
          showToast(data.error || 'Error al obtener datos de los registros seleccionados.', 'error');
        }
      } catch (err) {
        showToast('Error de red al consultar registros.', 'error');
      }
    });
  }

  function updateBulkModalCounters() {
    const total = bulkSelectedRecords.length;
    const withCode = bulkSelectedRecords.filter(r => r.code && r.code.trim() !== '' && r.code.trim() !== '-');
    const withoutCode = total - withCode.length;
    const alreadySent = withCode.filter(r => parseInt(r.sent_count || 0, 10) > 0).length;

    if (bulkTotalSelected) bulkTotalSelected.textContent = total;
    if (bulkValidCount) bulkValidCount.textContent = withCode.length;
    if (bulkNocodeCount) bulkNocodeCount.textContent = withoutCode;
    if (bulkAlreadySentCount) bulkAlreadySentCount.textContent = alreadySent;
  }

  if (bulkSkipAlreadySent) {
    bulkSkipAlreadySent.addEventListener('change', () => {
      updateBulkModalCounters();
    });
  }

  const closeBulkModal = () => {
    if (isBulkRunning) {
      if (!confirm('Hay un proceso de envío en curso. ¿Deseas detenerlo y cerrar la ventana?')) {
        return;
      }
      bulkShouldStop = true;
    }
    if (modalBulkSend) modalBulkSend.style.display = 'none';
    isBulkRunning = false;
    isBulkPaused = false;
  };

  if (btnCloseBulkModal) btnCloseBulkModal.addEventListener('click', closeBulkModal);
  if (btnCancelBulk) btnCancelBulk.addEventListener('click', closeBulkModal);

  if (btnPauseBulk) {
    btnPauseBulk.addEventListener('click', () => {
      if (isBulkPaused) {
        isBulkPaused = false;
        btnPauseBulk.textContent = '⏸ Pausar';
        if (bulkProgressStatus) bulkProgressStatus.textContent = 'Enviando correos...';
        addBulkLog('▶ Envío reanudado por el usuario.');
      } else {
        isBulkPaused = true;
        btnPauseBulk.textContent = '▶ Reanudar';
        if (bulkProgressStatus) bulkProgressStatus.textContent = 'Envío pausado.';
        addBulkLog('⏸ Envío pausado por el usuario.');
      }
    });
  }

  function addBulkLog(text, color = '#cbd5e1') {
    if (!bulkLogBox) return;
    const div = document.createElement('div');
    const time = new Date().toLocaleTimeString('es-CL');
    div.style.color = color;
    div.textContent = `[${time}] ${text}`;
    bulkLogBox.appendChild(div);
    bulkLogBox.scrollTop = bulkLogBox.scrollHeight;
  }

  if (btnStartBulk) {
    btnStartBulk.addEventListener('click', async () => {
      if (isBulkRunning) return;

      const skipSent = bulkSkipAlreadySent ? bulkSkipAlreadySent.checked : true;
      const targets = bulkSelectedRecords.filter(r => {
        const hasCode = r.code && r.code.trim() !== '' && r.code.trim() !== '-';
        if (!hasCode) return false;
        if (skipSent && parseInt(r.sent_count || 0, 10) > 0) return false;
        return true;
      });

      if (targets.length === 0) {
        alert('No hay destinatarios pendientes con código válido para enviar según los criterios seleccionados.');
        return;
      }

      if (!confirm(`¿Iniciar envío masivo a ${targets.length} destinatarios?\n\nCadencia segura: ~85 correos/min vía Resend (respetando límite y sin saturar tu hosting).`)) {
        return;
      }

      isBulkRunning = true;
      isBulkPaused = false;
      bulkShouldStop = false;

      btnStartBulk.style.display = 'none';
      if (btnPauseBulk) {
        btnPauseBulk.style.display = 'inline-flex';
        btnPauseBulk.textContent = '⏸ Pausar';
      }
      if (btnCancelBulk) btnCancelBulk.textContent = 'Detener y Salir';

      if (bulkProgressContainer) bulkProgressContainer.style.display = 'block';
      if (bulkLogContainer) bulkLogContainer.style.display = 'block';
      if (bulkLogBox) bulkLogBox.innerHTML = '';
      addBulkLog(`🚀 Iniciando proceso para ${targets.length} destinatarios seleccionados...`, '#38bdf8');

      let successCount = 0;
      let errorCount = 0;
      const totalTargets = targets.length;

      for (let i = 0; i < totalTargets; i++) {
        if (bulkShouldStop) {
          addBulkLog('⏹ Proceso detenido por el usuario.', '#f59e0b');
          break;
        }

        while (isBulkPaused) {
          if (bulkShouldStop) break;
          await new Promise(res => setTimeout(res, 300));
        }
        if (bulkShouldStop) break;

        const recipient = targets[i];
        if (bulkProgressStatus) {
          bulkProgressStatus.textContent = `Enviando a ${recipient.email} (${i + 1} de ${totalTargets})...`;
        }

        try {
          const fd = new FormData();
          fd.append('id', recipient.id);
          fd.append('email', recipient.email);

          const res = await fetch('api.php?action=admin_resend_single', {
            method: 'POST',
            body: fd
          });
          const data = await res.json();

          if (data.success) {
            successCount++;
            recipient.sent_count = (recipient.sent_count || 0) + 1;
            addBulkLog(`[${i + 1}/${totalTargets}] ✅ ${recipient.email} (${recipient.code})`, '#34d399');

            const countCell = document.getElementById(`sent-count-${recipient.id}`);
            if (countCell) {
              countCell.innerHTML = `<span style="color: #34d399; font-weight: 600;">${recipient.sent_count} vez/ces</span>`;
            }

            const statusCell = document.getElementById(`delivery-status-${recipient.id}`);
            if (statusCell) {
              statusCell.innerHTML = `<span class="badge" title="Enviado a Resend, en proceso de entrega" style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">🟡 Enviado</span>`;
            }

            const dateCell = document.getElementById(`last-sent-${recipient.id}`);
            if (dateCell && data.record?.last_sent_at) {
              dateCell.innerHTML = formatChileDateTime(data.record.last_sent_at);
            }
          } else {
            errorCount++;
            addBulkLog(`[${i + 1}/${totalTargets}] ❌ ${recipient.email} - ${data.error || 'Error'}`, '#ef4444');
          }
        } catch (err) {
          errorCount++;
          addBulkLog(`[${i + 1}/${totalTargets}] ❌ ${recipient.email} - Error de red: ${err.message}`, '#ef4444');
        }

        const percent = Math.round(((i + 1) / totalTargets) * 100);
        if (bulkProgressBar) bulkProgressBar.style.width = `${percent}%`;
        if (bulkProgressPercentage) bulkProgressPercentage.textContent = `${percent}%`;
        if (bulkStatsCounters) {
          bulkStatsCounters.textContent = `Exitosos: ${successCount} | Errores: ${errorCount} | Restantes: ${totalTargets - (i + 1)}`;
        }

        const remaining = totalTargets - (i + 1);
        const etaSeconds = Math.round(remaining * 0.75);
        if (bulkEta) {
          if (etaSeconds > 60) {
            const mins = Math.floor(etaSeconds / 60);
            const secs = etaSeconds % 60;
            bulkEta.textContent = `Tiempo aprox: ~${mins}m ${secs}s`;
          } else {
            bulkEta.textContent = `Tiempo aprox: ~${etaSeconds}s`;
          }
        }

        if (i < totalTargets - 1) {
          await new Promise(res => setTimeout(res, 750));
        }
      }

      isBulkRunning = false;
      if (btnPauseBulk) btnPauseBulk.style.display = 'none';
      if (btnCancelBulk) btnCancelBulk.textContent = 'Cerrar';

      if (!bulkShouldStop) {
        if (bulkProgressStatus) bulkProgressStatus.textContent = '¡Envío masivo completado!';
        if (bulkEta) bulkEta.textContent = 'Finalizado';
        addBulkLog(`🎉 Proceso completado: ${successCount} correos enviados exitosamente, ${errorCount} errores.`, '#34d399');
        showToast(`Envío masivo completado. ${successCount} correos enviados con éxito.`, 'success', 5000);
      }

      updateBulkModalCounters();
      loadStats();
      loadRecords(searchRecords.value.trim(), currentPage);
    });
  }

  formRecord.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData();
    if (recordId.value) fd.append('id', recordId.value);
    fd.append('email', recordEmail.value.trim());
    fd.append('code', recordCode.value.trim());
    if (recordCountry) fd.append('country', recordCountry.value.trim());

    const res = await fetch('api.php?action=admin_save_record', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      modalRecord.style.display = 'none';
      loadRecords(searchRecords.value.trim(), currentPage);
      loadStats();
    } else {
      alert(data.error || 'Error al guardar el registro');
    }
  });

  btnClearAll.addEventListener('click', async () => {
    const confirmation = prompt('⚠️ ATENCIÓN: Esto borrará TODOS los registros de la base de datos. Para confirmar escribe BORRAR:');
    if (confirmation === 'BORRAR') {
      const res = await fetch('api.php?action=admin_clear_all', { method: 'POST' });
      const data = await res.json();
      if (data.success) {
        alert('Base de datos vaciada.');
        loadRecords();
        loadStats();
      }
    }
  });

  // ==========================================
  // Manejador de Archivos Excel & CSV (SheetJS)
  // ==========================================
  dropzone.addEventListener('click', () => fileInput.click());

  dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('dragover');
  });

  dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));

  dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
      processFile(e.dataTransfer.files[0]);
    }
  });

  fileInput.addEventListener('change', (e) => {
    if (e.target.files.length) {
      processFile(e.target.files[0]);
    }
  });

  function processFile(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        // Convertir a array de filas
        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
        if (rows.length < 1) {
          alert('El archivo no contiene filas o datos legibles.');
          return;
        }

        openImportModal(rows);
      } catch (err) {
        alert('Error al leer el archivo Excel: ' + err.message);
      }
    };
    reader.readAsArrayBuffer(file);
  }

  function openImportModal(rows) {
    parsedExcelData = rows;
    const headerRow = rows[0] || [];
    const numCols = Math.max(...rows.slice(0, 10).map(r => r.length), 2);

    selectColEmail.innerHTML = '';
    selectColCode.innerHTML = '<option value="-1">-- Sin código (dejar vacío / sin descuento) --</option>';
    if (selectColCountry) {
      selectColCountry.innerHTML = '<option value="-1">-- Ninguna / Sin país --</option>';
    }

    let detectedEmail = false;
    let detectedCode = false;

    for (let c = 0; c < numCols; c++) {
      const colName = headerRow[c] !== undefined && headerRow[c] !== '' ? String(headerRow[c]) : `Columna ${c + 1}`;
      const optEmail = new Option(colName, c);
      const optCode = new Option(colName, c);
      const optCountry = new Option(colName, c);

      // Auto-detección inteligente
      const lower = colName.toLowerCase();
      if (!detectedEmail && (lower.includes('mail') || lower.includes('correo'))) {
        optEmail.selected = true;
        detectedEmail = true;
      }
      if (!detectedCode && (lower.includes('cod') || lower.includes('cup') || lower.includes('desc') || lower.includes('clave'))) {
        optCode.selected = true;
        detectedCode = true;
      }
      if (lower.includes('pais') || lower.includes('país') || lower.includes('country') || lower.includes('nacion') || lower.includes('origen')) {
        optCountry.selected = true;
      }

      selectColEmail.add(optEmail);
      selectColCode.add(optCode);
      if (selectColCountry) {
        selectColCountry.add(optCountry);
      }
    }

    // Si no se detectó ninguna columna de código, queda seleccionado "-- Sin código (dejar vacío) --"
    if (!detectedCode) {
      selectColCode.value = '-1';
    }

    renderPreviewTable(rows);
    importSummary.textContent = `Archivo procesado: ${rows.length - 1} filas detectadas.`;
    modalImport.style.display = 'flex';
  }

  function renderPreviewTable(rows) {
    const previewRows = rows.slice(0, 6);
    if (!previewRows.length) return;

    previewTableHead.innerHTML = `<tr>${previewRows[0].map((h, i) => `<th>${escapeHtml(String(h || 'Columna ' + (i+1)))}</th>`).join('')}</tr>`;
    previewTableBody.innerHTML = previewRows.slice(1).map(row => `
      <tr>${row.map(cell => `<td>${escapeHtml(String(cell || ''))}</td>`).join('')}</tr>
    `).join('');
  }

  btnCancelImport.addEventListener('click', () => {
    modalImport.style.display = 'none';
    fileInput.value = '';
  });

  btnConfirmImport.addEventListener('click', async () => {
    const emailCol = parseInt(selectColEmail.value);
    const codeCol = parseInt(selectColCode.value);
    const countryCol = selectColCountry ? parseInt(selectColCountry.value) : -1;
    const replaceAll = checkReplaceAll.checked;

    if (codeCol >= 0 && emailCol === codeCol) {
      alert('La columna de Correo y la de Código no pueden ser la misma.');
      return;
    }

    btnConfirmImport.disabled = true;
    btnConfirmImport.textContent = 'Importando...';

    // Construir batch de filas (omitimos la primera si es cabecera)
    const batch = [];
    for (let i = 1; i < parsedExcelData.length; i++) {
      const row = parsedExcelData[i];
      const email = String(row[emailCol] || '').trim();
      const code = codeCol >= 0 ? String(row[codeCol] || '').trim() : '';
      const country = countryCol >= 0 ? String(row[countryCol] || '').trim() : '';
      if (email && email.includes('@')) {
        batch.push({ email, code, country });
      }
    }

    if (batch.length === 0) {
      alert('No se detectaron correos válidos con las columnas seleccionadas.');
      btnConfirmImport.disabled = false;
      btnConfirmImport.textContent = 'Importar Registros';
      return;
    }

    try {
      const res = await fetch('api.php?action=admin_import_batch', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rows: batch, replace: replaceAll })
      });

      const data = await res.json();
      btnConfirmImport.disabled = false;
      btnConfirmImport.textContent = 'Importar Registros';

      if (data.success) {
        alert(data.message);
        modalImport.style.display = 'none';
        fileInput.value = '';
        loadRecords();
        loadStats();
      } else {
        alert(data.error || 'Error al importar los datos.');
      }
    } catch (err) {
      btnConfirmImport.disabled = false;
      btnConfirmImport.textContent = 'Importar Registros';
      alert('Error al enviar los registros al servidor.');
    }
  });

  // ==========================================
  // Editor de Plantilla de Correo
  // ==========================================
  async function loadTemplate() {
    try {
      const res = await fetch('api.php?action=admin_get_template');
      const data = await res.json();
      if (data.success) {
        mailSubject.value = data.subject || '';
        mailFromName.value = data.from_name || '';
        mailFromEmail.value = data.from_email || '';
        mailTemplate.value = data.template || '';
        updateMailPreview();
      }
    } catch (e) {}
  }

  function updateMailPreview() {
    let html = mailTemplate.value;
    const dummyCode = 'PT35CL-EJEMPLO';
    const dummyEmail = 'ejemplo-fan@gmail.com';
    const basePath = window.location.origin + window.location.pathname.replace(/\/admin(\.php)?\/?$/, '');
    const cabeceraUrl = basePath + '/assets/cabecera-mail.png';
    const logoUrl = basePath + '/assets/logo-white-trimmed.png';
    const logoRitUrl = basePath + '/assets/logo-rit-trimmed.png';
    const siteUrl = basePath;
    const ticketsUrl = 'https://www.ritstore.agency/event-details/plastic-tree-latin-america-tour-2027-replay-chile?currency=CLP';
    const dateStr = new Date().toLocaleDateString('es-CL');

    html = html.replace(/\{CODIGO\}/g, dummyCode)
               .replace(/\{PORCENTAJE\}/g, '35')
               .replace(/\{TICKETS_URL\}/g, ticketsUrl)
               .replace(/\{CABECERA_URL\}/g, cabeceraUrl)
               .replace(/\{EMAIL\}/g, dummyEmail)
               .replace(/\{LOGO_URL\}/g, logoUrl)
               .replace(/\{LOGO_RIT_URL\}/g, logoRitUrl)
               .replace(/\{SITIO_URL\}/g, siteUrl)
               .replace(/\{INSTAGRAM_URL\}/g, 'https://www.instagram.com/pt_kurage')
               .replace(/\{IG_URL\}/g, 'https://www.instagram.com/pt_kurage')
               .replace(/\{RIT_URL\}/g, 'https://www.rit.agency/')
               .replace(/\{FECHA\}/g, dateStr);

    const doc = previewIframe.contentDocument || previewIframe.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();
  }

  btnResetTemplate.addEventListener('click', async () => {
    if (!confirm('¿Deseas restaurar la plantilla gráfica oficial del tour con la cabecera?')) return;
    try {
      const res = await fetch('api.php?action=admin_get_template&reset=1');
      const data = await res.json();
      if (data.success) {
        mailTemplate.value = data.template;
        updateMailPreview();
        alert('Plantilla restaurada con éxito.');
      }
    } catch (e) {
      alert('Error al restaurar plantilla.');
    }
  });

  mailTemplate.addEventListener('input', () => {
    if (tabEditorPreview.classList.contains('active')) {
      updateMailPreview();
    }
  });

  tabEditorCode.addEventListener('click', () => {
    tabEditorCode.classList.add('active');
    tabEditorPreview.classList.remove('active');
    editorCodePane.style.display = 'block';
    editorPreviewPane.style.display = 'none';
  });

  tabEditorPreview.addEventListener('click', () => {
    tabEditorPreview.classList.add('active');
    tabEditorCode.classList.remove('active');
    editorCodePane.style.display = 'none';
    editorPreviewPane.style.display = 'block';
    updateMailPreview();
  });

  // Botones para insertar etiquetas
  document.querySelectorAll('.btn-tag').forEach(btn => {
    btn.addEventListener('click', () => {
      const tag = btn.dataset.tag;
      insertAtCursor(mailTemplate, tag);
      updateMailPreview();
    });
  });

  function insertAtCursor(textarea, text) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const val = textarea.value;
    textarea.value = val.substring(0, start) + text + val.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
  }

  btnSaveTemplate.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('subject', mailSubject.value.trim());
    fd.append('from_name', mailFromName.value.trim());
    fd.append('from_email', mailFromEmail.value.trim());
    fd.append('template', mailTemplate.value);

    btnSaveTemplate.textContent = 'Guardando...';
    try {
      const res = await fetch('api.php?action=admin_save_template', { method: 'POST', body: fd });
      const data = await res.json();
      btnSaveTemplate.textContent = 'Guardar Plantilla';
      if (data.success) {
        alert('Plantilla guardada correctamente.');
      } else {
        alert(data.error || 'Error al guardar');
      }
    } catch (e) {
      btnSaveTemplate.textContent = 'Guardar Plantilla';
      alert('Error de red al guardar.');
    }
  });

  btnSendTest.addEventListener('click', async () => {
    const email = testEmailInput.value.trim();
    if (!email) {
      alert('Ingresa un correo de destino para la prueba.');
      return;
    }

    btnSendTest.disabled = true;
    btnSendTest.textContent = 'Enviando...';
    testEmailResult.style.display = 'none';

    const fd = new FormData();
    fd.append('test_email', email);
    fd.append('subject', mailSubject.value.trim());
    fd.append('template', mailTemplate.value);

    try {
      const res = await fetch('api.php?action=admin_test_email', { method: 'POST', body: fd });
      const data = await res.json();
      btnSendTest.disabled = false;
      btnSendTest.textContent = 'Enviar Prueba';

      testEmailResult.style.display = 'block';
      if (data.success) {
        testEmailResult.textContent = '¡Prueba enviada con éxito! Revisa tu bandeja de entrada o Spam.';
        testEmailResult.className = 'toast-msg success';
      } else {
        testEmailResult.textContent = 'Error al enviar: ' + (data.message || 'Desconocido');
        testEmailResult.className = 'toast-msg error';
      }
    } catch (e) {
      btnSendTest.disabled = false;
      btnSendTest.textContent = 'Enviar Prueba';
      testEmailResult.style.display = 'block';
      testEmailResult.textContent = 'Error de conexión con el servidor de correos.';
      testEmailResult.className = 'toast-msg error';
    }
  });

  // ==========================================
  // Configuración SMTP, Resend y Seguridad
  // ==========================================
  const resendSettingsFields = document.getElementById('resend-settings-fields');
  const resendApiKeyInput = document.getElementById('resend-api-key');

  function updateMailMethodVisibility() {
    const val = mailMethod.value;
    resendSettingsFields.style.display = (val === 'resend') ? 'block' : 'none';
    smtpSettingsFields.style.display = (val === 'smtp') ? 'block' : 'none';
  }

  mailMethod.addEventListener('change', updateMailMethodVisibility);

  async function loadSettings() {
    try {
      const res = await fetch('api.php?action=admin_get_settings');
      const data = await res.json();
      if (data.success) {
        mailMethod.value = data.mail_method || 'resend';
        if (resendApiKeyInput) resendApiKeyInput.value = data.resend_api_key || '';
        document.getElementById('smtp-host').value = data.smtp_host || '';
        document.getElementById('smtp-port').value = data.smtp_port || 465;
        document.getElementById('smtp-user').value = data.smtp_user || '';
        document.getElementById('smtp-secure').value = data.smtp_secure || 'ssl';
        updateMailMethodVisibility();
      }
    } catch (e) {}
  }

  formSettings.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(formSettings);
    const res = await fetch('api.php?action=admin_save_settings', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      alert('Configuración guardada exitosamente.');
    } else {
      alert(data.error || 'Error al guardar configuración.');
    }
  });

  formPassword.addEventListener('submit', async (e) => {
    e.preventDefault();
    const newPass = document.getElementById('new-password').value;
    const confirmPass = document.getElementById('confirm-password').value;

    if (newPass !== confirmPass) {
      alert('Las contraseñas no coinciden.');
      return;
    }

    const fd = new FormData();
    fd.append('new_password', newPass);

    const res = await fetch('api.php?action=admin_change_password', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      alert('Contraseña actualizada con éxito.');
      formPassword.reset();
    } else {
      alert(data.error || 'Error al actualizar contraseña.');
    }
  });

  // Helpers
  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function escapeJs(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
  }
});
