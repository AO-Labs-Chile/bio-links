<?php
require_once __DIR__ . '/config.php';
// Panel de administración PT:Kurage - v2.2
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración · PT:Kurage</title>
  <link rel="icon" type="image/png" href="assets/favicon-kurage.png?v=<?= time() ?>">
  <link rel="shortcut icon" href="favicon.ico?v=<?= time() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css?v=<?= time() ?>">
</head>
<body>

  <!-- PANTALLA DE LOGIN -->
  <section id="login-section" class="login-container">
    <div class="login-card">
      <img src="assets/logo-white-trimmed.png" alt="PT:Kurage" class="login-logo" style="height: 52px; width: auto; margin-bottom: 20px;">
      <h2 style="font-size: 20px; font-weight: 600; margin-bottom: 6px;">Panel de Administración</h2>
      <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">Gestión de descuentos y maqueta de correos</p>

      <form id="login-form">
        <div class="form-group" style="text-align: left;">
          <label class="form-label" for="login-password">Contraseña de Acceso</label>
          <input type="password" id="login-password" class="form-control" placeholder="••••••••" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Ingresar al Panel</button>
      </form>
      <div id="login-error" style="display: none; color: var(--danger); font-size: 13px; margin-top: 15px;"></div>
    </div>
  </section>

  <!-- PANEL DE ADMINISTRACIÓN -->
  <div id="admin-section" style="display: none;">
    
    <!-- Top Header -->
    <header class="admin-header">
      <div class="admin-header-title">
        <img src="assets/logo-white-trimmed.png" alt="PT:Kurage" style="height: 32px; width: auto;">
        <div>
          <strong style="font-size: 15px;">PT:Kurage Admin</strong>
          <span style="font-size: 12px; color: var(--text-muted); margin-left: 8px;">Plastic Tree Encuesta</span>
        </div>
      </div>
      <div style="display: flex; gap: 12px; align-items: center;">
        <a href="./" target="_blank" class="btn btn-secondary btn-sm">Ver Sitio Público ↗</a>
        <button id="btn-logout" class="btn btn-danger btn-sm">Cerrar Sesión</button>
      </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="admin-nav">
      <button class="nav-tab active" data-tab="records">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16m-9 5h6m-6 4h4"></path></svg>
        Base de Datos & Excel
      </button>
      <button class="nav-tab" data-tab="template">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        Maqueta de Correo (HTML)
      </button>
      <button class="nav-tab" data-tab="settings">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        Configuración & Envíos
      </button>
      <button class="nav-tab" data-tab="security">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        Seguridad
      </button>
    </nav>

    <!-- Main Container -->
    <main class="admin-content">

      <!-- ============================================== -->
      <!-- PESTAÑA 1: BASE DE DATOS & EXCEL               -->
      <!-- ============================================== -->
      <section id="tab-records" class="tab-content active">
        
        <!-- Stats Grid -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
          <div class="stat-card">
            <div class="stat-label">Total Registros</div>
            <div id="stat-total" class="stat-value">0</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Destinatarios Enviados</div>
            <div id="stat-sent" class="stat-value">0</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Total Envíos</div>
            <div id="stat-total-sends" class="stat-value">0</div>
          </div>
          <div class="stat-card" style="border-left: 3px solid #10b981;">
            <div class="stat-label" style="color: #34d399;">Entregados</div>
            <div id="stat-delivered" class="stat-value" style="color: #34d399;">0</div>
          </div>
          <div class="stat-card" style="border-left: 3px solid #a855f7;">
            <div class="stat-label" style="color: #c084fc;">Abiertos (Leídos)</div>
            <div id="stat-opened" class="stat-value" style="color: #c084fc;">0</div>
          </div>
          <div class="stat-card" style="border-left: 3px solid #ef4444;">
            <div class="stat-label" style="color: #f87171;">Rebotados</div>
            <div id="stat-bounced" class="stat-value" style="color: #f87171;">0</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Consultas Web</div>
            <div id="stat-checked" class="stat-value">0</div>
          </div>
        </div>

        <!-- Carga de Archivo Excel -->
        <div class="admin-card">
          <h2 class="card-title">Subir Archivo Excel o CSV</h2>
          <p class="card-subtitle">Sube tu planilla de respuestas de Google Forms con los correos y códigos de descuento asignados.</p>

          <div id="dropzone" class="dropzone">
            <svg class="dropzone-icon" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            <p style="font-weight: 500; font-size: 15px; margin-bottom: 4px;">Arrastra aquí tu archivo Excel (.xlsx, .xls) o CSV</p>
            <p style="font-size: 13px; color: var(--text-muted);">o haz clic para seleccionar desde tu computador</p>
            <input type="file" id="file-input" accept=".xlsx, .xls, .csv" style="display: none;">
          </div>
        </div>

        <!-- Tabla de Registros -->
        <div class="admin-card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
              <h2 class="card-title" style="margin-bottom: 2px;">Listado de Registros</h2>
              <p style="font-size: 13px; color: var(--text-muted);">Gestiona los correos y códigos cargados.</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
              <button id="btn-send-selected" class="btn btn-success btn-sm" style="display: none; background: #059669; border-color: #10b981; font-weight: 600; color: #fff;">
                ✉️ Enviar a seleccionados (<span id="send-selected-count">0</span>)
              </button>
              <button id="btn-delete-selected" class="btn btn-danger btn-sm" style="display: none; background: #dc2626; border-color: #ef4444; font-weight: 600;">
                🗑️ Eliminar (<span id="selected-count">0</span>)
              </button>
              <button id="btn-select-pending-page" class="btn btn-secondary btn-sm" type="button" title="Selecciona automáticamente los registros visibles que no han recibido correo y tienen código">
                ⚡ Seleccionar pendientes
              </button>
              <button id="btn-add-record" class="btn btn-primary btn-sm">+ Agregar Individual</button>
              <button id="btn-sync-resend" class="btn btn-secondary btn-sm" style="border-color: #38bdf8; color: #38bdf8; font-weight: 500;" title="Consultar a Resend para actualizar estados de entrega, lectura y rebotes">
                🔄 Sincronizar Resend
              </button>
              <button id="btn-export-csv" class="btn btn-secondary btn-sm">Exportar CSV</button>
              <button id="btn-refresh-records" class="btn btn-secondary btn-sm">Actualizar</button>
              <button id="btn-clear-all" class="btn btn-danger btn-sm">Vaciar BD</button>
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px;">
              <input type="text" id="search-records" class="form-control" placeholder="Buscar por correo o código...">
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <label for="filter-country" style="font-size: 13px; color: var(--text-muted); white-space: nowrap;">País:</label>
              <select id="filter-country" class="per-page-select" style="min-width: 160px;">
                <option value="">🌍 Todos los países</option>
              </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <label for="filter-status" style="font-size: 13px; color: var(--text-muted); white-space: nowrap;">Estado:</label>
              <select id="filter-status" class="per-page-select" style="min-width: 165px;">
                <option value="">📋 Todos los estados</option>
                <option value="pending">⚪ Sin enviar (Pendientes)</option>
                <option value="sent">🟡 Enviado (En tránsito)</option>
                <option value="delivered">🟢 Entregado</option>
                <option value="opened">🟣 Abierto (Leído)</option>
                <option value="bounced">🔴 Rebotado (Error)</option>
              </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted);">
              <span>Filas:</span>
              <select id="select-per-page" class="per-page-select">
                <option value="50" selected>50</option>
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="500">500</option>
                <option value="1000">1000 (Todos)</option>
              </select>
            </div>
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="col-checkbox" style="width: 38px; text-align: center; vertical-align: middle;">
                    <input type="checkbox" id="check-all-records" title="Seleccionar todos los de esta página" style="cursor: pointer; width: 15px; height: 15px; vertical-align: middle;">
                  </th>
                  <th class="sortable-th col-email" data-sort="email" title="Clic para ordenar por correo">
                    <span class="th-content">
                      <span>Correo</span>
                      <span class="sort-icon" id="sort-icon-email">↕</span>
                    </span>
                  </th>
                  <th class="sortable-th col-code" data-sort="code" title="Clic para ordenar por código">
                    <span class="th-content">
                      <span>Código</span>
                      <span class="sort-icon" id="sort-icon-code">↕</span>
                    </span>
                  </th>
                  <th class="sortable-th col-country" data-sort="country" title="Clic para ordenar por país">
                    <span class="th-content">
                      <span>País</span>
                      <span class="sort-icon" id="sort-icon-country">↕</span>
                    </span>
                  </th>
                  <th class="sortable-th col-status" data-sort="delivery_status" title="Clic para ordenar por estado de entrega de Resend">
                    <span class="th-content">
                      <span>Estado Entrega</span>
                      <span class="sort-icon" id="sort-icon-delivery_status">↕</span>
                    </span>
                  </th>
                  <th class="sortable-th col-sent" data-sort="sent_count" title="Clic para ordenar por cantidad de envíos" style="text-align: center;">
                    <span class="th-content" style="justify-content: center;">
                      <span>Envíos</span>
                      <span class="sort-icon" id="sort-icon-sent_count">↕</span>
                    </span>
                  </th>
                  <th class="sortable-th col-date" data-sort="last_sent_at" title="Clic para ordenar por fecha de último envío (Hora Chilena)">
                    <span class="th-content">
                      <span>Último Envío</span>
                      <span class="sort-icon" id="sort-icon-last_sent_at">↕</span>
                    </span>
                  </th>
                  <th class="sortable-th col-date" data-sort="created_at" title="Clic para ordenar por fecha de registro (Hora Chilena)">
                    <span class="th-content">
                      <span>Registro</span>
                      <span class="sort-icon" id="sort-icon-created_at">↕</span>
                    </span>
                  </th>
                  <th class="col-actions">Acciones</th>
                </tr>
              </thead>
              <tbody id="records-tbody">
                <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">Cargando registros...</td></tr>
              </tbody>
            </table>

            <!-- Barra de Paginación -->
            <div id="table-pagination-bar" class="table-pagination-bar" style="display: none;">
              <div id="pagination-info">Cargando paginación...</div>
              <div id="pagination-controls" class="pagination-controls"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ============================================== -->
      <!-- PESTAÑA 2: MAQUETA DE CORREO (HTML)             -->
      <!-- ============================================== -->
      <section id="tab-template" class="tab-content">
        <div class="admin-card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <div>
              <h2 class="card-title">Editor de Maqueta de Correo</h2>
              <p class="card-subtitle" style="margin-bottom: 0;">Diseño oficial del tour con cabecera gráfica y botones dinámicos según país.</p>
            </div>
            <div style="display: flex; gap: 8px;">
              <button id="btn-reset-template" class="btn btn-secondary btn-sm" type="button">Restaurar Plantilla Oficial</button>
              <button id="btn-save-template" class="btn btn-primary btn-sm" type="button">Guardar Plantilla</button>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Asunto del Correo (Subject)</label>
              <input type="text" id="mail-subject" class="form-control" placeholder="Plastic Tree REPLAY • Tu descuento especial">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Nombre del Remitente</label>
              <input type="text" id="mail-from-name" class="form-control" placeholder="PT:Kurage - Plastic Tree Community">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Correo Remitente (From)</label>
              <input type="email" id="mail-from-email" class="form-control" placeholder="ptkurage@japanrevolution.cl">
            </div>
          </div>

          <!-- Etiquetas dinámicas -->
          <div style="background: #111319; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; margin-bottom: 16px;">
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">Haz clic en una etiqueta para insertarla en el correo:</div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
              <button type="button" class="btn-tag" data-tag="{CODIGO}">{CODIGO} (Código ej: PT35CL-...)</button>
              <button type="button" class="btn-tag" data-tag="{PORCENTAJE}">{PORCENTAJE} (35 o 20 automático)</button>
              <button type="button" class="btn-tag" data-tag="{TICKETS_URL}">{TICKETS_URL} (Enlace Chile/México automático)</button>
              <button type="button" class="btn-tag" data-tag="{CABECERA_URL}">{CABECERA_URL} (Imagen cabecera tour)</button>
              <button type="button" class="btn-tag" data-tag="{EMAIL}">{EMAIL} (Correo del fan)</button>
              <button type="button" class="btn-tag" data-tag="{INSTAGRAM_URL}">{INSTAGRAM_URL} (Instagram @pt_kurage)</button>
              <button type="button" class="btn-tag" data-tag="{RIT_URL}">{RIT_URL} (Web RIT Agency)</button>
              <button type="button" class="btn-tag" data-tag="{SITIO_URL}">{SITIO_URL} (Web ptkurage)</button>
            </div>
          </div>

          <!-- Pestañas Editor / Vista Previa -->
          <div style="display: flex; gap: 8px; margin-bottom: 12px;">
            <button id="tab-editor-code" class="btn btn-secondary btn-sm active">Código HTML</button>
            <button id="tab-editor-preview" class="btn btn-secondary btn-sm">Vista Previa en Vivo 👁️</button>
          </div>

          <!-- Pane Código -->
          <div id="editor-code-pane">
            <textarea id="mail-template" class="form-control" rows="18" style="height: 480px;"></textarea>
          </div>

          <!-- Pane Preview -->
          <div id="editor-preview-pane" style="display: none;">
            <div class="preview-container">
              <iframe id="preview-iframe" class="preview-frame"></iframe>
            </div>
          </div>

          <!-- Prueba de Envío -->
          <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
            <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 6px;">Enviar Correo de Prueba</h3>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">Prueba cómo llega el correo a tu casilla personal (se reemplazará con un código ficticio).</p>
            
            <div style="display: flex; gap: 10px; max-width: 480px;">
              <input type="email" id="test-email-input" class="form-control" placeholder="tu@correo.com">
              <button id="btn-send-test" class="btn btn-secondary" style="white-space: nowrap;">Enviar Prueba</button>
            </div>
            <div id="test-email-result" class="toast-msg" style="max-width: 480px;"></div>
          </div>

        </div>
      </section>

      <!-- ============================================== -->
      <!-- PESTAÑA 3: CONFIGURACIÓN & ENVÍOS              -->
      <!-- ============================================== -->
      <section id="tab-settings" class="tab-content">
        <div class="admin-card">
          <h2 class="card-title">Configuración de Envío de Correos</h2>
          <p class="card-subtitle">Selecciona el proveedor para despachar los correos a los fans.</p>

          <form id="form-settings" style="max-width: 650px;">
            <div class="form-group">
              <label class="form-label">Método de Envío</label>
              <select id="mail-method" name="mail_method" class="form-control">
                <option value="resend">⚡ Resend API (Recomendado - Excelente entregabilidad, no cae a spam)</option>
                <option value="mail">PHP mail() nativo (Directo desde cPanel)</option>
                <option value="smtp">Servidor SMTP Autenticado</option>
              </select>
            </div>

            <!-- Campos Resend -->
            <div id="resend-settings-fields" style="background: #11131a; border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 18px;">
              <div class="form-group">
                <label class="form-label">Resend API Key</label>
                <input type="password" id="resend-api-key" name="resend_api_key" class="form-control" placeholder="re_...">
              </div>
              <div style="font-size: 12px; color: #94a3b8; line-height: 1.6; background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; border-left: 3px solid #10b981;">
                <strong style="color: #34d399;">✓ Conectado a Resend con japanrevolution.cl:</strong><br>
                - Tu dominio <code style="color: #60a5fa;">japanrevolution.cl</code> está verificado y activo en Resend.<br>
                - Los correos se despachan con máxima reputación de entrega a la bandeja de entrada usando <code style="color: #ffffff;">noreply@japanrevolution.cl</code> o cualquier dirección de este dominio.
              </div>
            </div>

            <!-- Campos SMTP -->
            <div id="smtp-settings-fields" style="display: none; background: #11131a; border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 18px;">
              <div class="form-group">
                <label class="form-label">Servidor SMTP (Host)</label>
                <input type="text" id="smtp-host" name="smtp_host" class="form-control" placeholder="mail.japanrevolution.cl">
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                  <label class="form-label">Puerto</label>
                  <input type="number" id="smtp-port" name="smtp_port" class="form-control" value="465">
                </div>
                <div class="form-group">
                  <label class="form-label">Seguridad</label>
                  <select id="smtp-secure" name="smtp_secure" class="form-control">
                    <option value="ssl">SSL (Recomendado para puerto 465)</option>
                    <option value="tls">STARTTLS (Para puerto 587)</option>
                    <option value="none">Ninguna</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Usuario SMTP</label>
                <input type="text" id="smtp-user" name="smtp_user" class="form-control" placeholder="usuario@japanrevolution.cl">
              </div>
              <div class="form-group">
                <label class="form-label">Contraseña SMTP</label>
                <input type="password" id="smtp-pass" name="smtp_pass" class="form-control" placeholder="Dejar en blanco para mantener la actual">
              </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Configuración</button>
          </form>

          <!-- Tarjeta de Instrucciones Webhook de Resend -->
          <div style="background: #11131a; border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-top: 24px;">
            <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 6px; color: #38bdf8; display: flex; align-items: center; gap: 8px;">
              <span>⚡ Webhook de Resend (Seguimiento de Entregas, Aperturas y Rebotes)</span>
            </h3>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
              Para que tu panel se actualice en tiempo real cuando un correo sea <strong>Entregado</strong>, <strong>Abierto (Leído)</strong> o <strong>Rebotado</strong>, copia esta URL y agrégala en tu cuenta de Resend:
            </p>
            <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px; flex-wrap: wrap;">
              <input type="text" readonly id="resend-webhook-url" class="form-control" value="https://japanrevolution.cl/ptkurage/api.php?action=resend_webhook" style="font-family: monospace; font-size: 13px; color: #34d399; background: #0a0b0e; flex: 1; min-width: 280px;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('resend-webhook-url').value); alert('URL copiada al portapapeles');">Copiar URL</button>
            </div>
            <div style="font-size: 12px; color: #94a3b8; line-height: 1.6; background: #0b0d12; padding: 12px; border-radius: 8px; border: 1px solid #1f2330;">
              <strong style="color: #cbd5e1;">Pasos para activarlo en Resend:</strong><br>
              1. Entra a <a href="https://resend.com/webhooks" target="_blank" style="color: #60a5fa; text-decoration: underline;">resend.com/webhooks</a> con tu cuenta <code>alexistello.gon@gmail.com</code>.<br>
              2. Haz clic en el botón <strong>Add Webhook</strong>.<br>
              3. En <em>Endpoint URL</em> pega la URL de arriba.<br>
              4. Marca las casillas de eventos: <strong>email.delivered</strong>, <strong>email.opened</strong>, <strong>email.clicked</strong> y <strong>email.bounced</strong>.<br>
              5. Haz clic en <strong>Add</strong>. ¡Listo! A partir de ese momento, cada evento actualizará automáticamente el estado en tu panel.
            </div>
          </div>
        </div>
      </section>

      <!-- ============================================== -->
      <!-- PESTAÑA 4: SEGURIDAD                            -->
      <!-- ============================================== -->
      <section id="tab-security" class="tab-content">
        <div class="admin-card" style="max-width: 500px;">
          <h2 class="card-title">Cambiar Contraseña de Administrador</h2>
          <p class="card-subtitle">Actualiza la clave para acceder a este panel de administración.</p>

          <form id="form-password">
            <div class="form-group">
              <label class="form-label">Nueva Contraseña</label>
              <input type="password" id="new-password" class="form-control" required minlength="6">
            </div>
            <div class="form-group">
              <label class="form-label">Confirmar Contraseña</label>
              <input type="password" id="confirm-password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
          </form>
        </div>
      </section>

    </main>
  </div>

  <!-- MODAL: MAPEADOR Y CONFIRMACIÓN DE IMPORTACIÓN EXCEL -->
  <div id="modal-import" class="modal-overlay">
    <div class="modal-card">
      <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 6px;">Asignar Columnas de Excel</h2>
      <p id="import-summary" style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">Selecciona cuáles columnas contienen los correos, los códigos y el país de procedencia.</p>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 18px;">
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">Columna Correo Electrónico</label>
          <select id="select-col-email" class="form-control"></select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">Columna Código de Descuento <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">(Opcional)</span></label>
          <select id="select-col-code" class="form-control"></select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">Columna País <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">(Opcional)</span></label>
          <select id="select-col-country" class="form-control"></select>
        </div>
      </div>

      <div style="margin-bottom: 16px;">
        <div style="font-size: 12px; font-weight: 500; color: #cbd5e1; margin-bottom: 6px;">Vista previa de las primeras filas:</div>
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
          <table class="data-table" style="font-size: 12px;">
            <thead id="preview-table-head"></thead>
            <tbody id="preview-table-body"></tbody>
          </table>
        </div>
      </div>

      <div class="form-group" style="background: #11131a; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
          <input type="checkbox" id="check-replace-all">
          <span>Reemplazar toda la base de datos (borra los registros anteriores)</span>
        </label>
        <span style="display: block; font-size: 11px; color: var(--text-muted); margin-left: 22px; margin-top: 2px;">
          Si no marcas esta casilla, los registros nuevos se agregarán y los existentes se actualizarán.
        </span>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button id="btn-cancel-import" class="btn btn-secondary">Cancelar</button>
        <button id="btn-confirm-import" class="btn btn-primary">Importar Registros</button>
      </div>
    </div>
  </div>

  <!-- MODAL: REGISTRO MANUAL (AGREGAR / EDITAR) -->
  <div id="modal-record" class="modal-overlay">
    <div class="modal-card" style="max-width: 440px;">
      <h2 id="modal-record-title" style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">Registro</h2>
      <form id="form-record">
        <input type="hidden" id="record-id">
        <div class="form-group">
          <label class="form-label">Correo Electrónico</label>
          <input type="email" id="record-email" class="form-control" required placeholder="fan@gmail.com">
        </div>
        <div class="form-group">
          <label class="form-label">Código de Descuento <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">(Opcional)</span></label>
          <input type="text" id="record-code" class="form-control" placeholder="Ej: PT35CL-XXXX (o vacío si no hubo concierto)">
        </div>
        <div class="form-group">
          <label class="form-label">País de Procedencia <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">(Opcional)</span></label>
          <input type="text" id="record-country" class="form-control" placeholder="Ej: Argentina, Perú, Colombia...">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
          <button type="button" id="btn-cancel-record" class="btn btn-secondary">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: ENVÍO MASIVO DE CORREOS -->
  <div id="modal-bulk-send" class="modal-overlay">
    <div class="modal-card" style="max-width: 620px;">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
        <div>
          <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
            <span>✉️ Envío Masivo de Correos</span>
          </h2>
          <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 0;">
            Envía el correo con código a los destinatarios seleccionados de manera segura y controlada.
          </p>
        </div>
        <button type="button" id="btn-close-bulk-modal" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 16px; line-height: 1;">&times;</button>
      </div>

      <!-- Resumen de Destinatarios -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-bottom: 16px;">
        <div style="background: #11131a; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; text-align: center;">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted);">Seleccionados</div>
          <div id="bulk-total-selected" style="font-size: 20px; font-weight: 700; color: #fff;">0</div>
        </div>
        <div style="background: #11131a; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; text-align: center;">
          <div style="font-size: 11px; text-transform: uppercase; color: #34d399;">Aptos con código</div>
          <div id="bulk-valid-count" style="font-size: 20px; font-weight: 700; color: #34d399;">0</div>
        </div>
        <div style="background: #11131a; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; text-align: center;">
          <div style="font-size: 11px; text-transform: uppercase; color: #f59e0b;">Sin código</div>
          <div id="bulk-nocode-count" style="font-size: 20px; font-weight: 700; color: #f59e0b;">0</div>
        </div>
        <div style="background: #11131a; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; text-align: center;">
          <div style="font-size: 11px; text-transform: uppercase; color: #38bdf8;">Ya enviados</div>
          <div id="bulk-alreadysent-count" style="font-size: 20px; font-weight: 700; color: #38bdf8;">0</div>
        </div>
      </div>

      <!-- Opciones de envío -->
      <div style="background: #11131a; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; margin-bottom: 16px; font-size: 13px;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 8px;">
          <input type="checkbox" id="bulk-skip-already-sent" checked style="cursor: pointer; width: 16px; height: 16px;">
          <span><strong>Omitir los que ya han recibido correo</strong> (solo enviar a pendientes con 0 reenvíos)</span>
        </label>
        <div style="color: var(--text-muted); font-size: 12px; margin-left: 24px;">
          * Los registros que no tienen código asignado serán omitidos automáticamente para evitar errores.
        </div>
        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #1e2330; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
          <span style="color: #94a3b8; font-size: 12px;">🛡️ Cadencia de seguridad Resend:</span>
          <span style="font-size: 12px; font-family: monospace; color: #38bdf8;">1 correo cada 700 ms (~85/min)</span>
        </div>
      </div>

      <!-- Barra de Progreso y Estado -->
      <div id="bulk-progress-container" style="display: none; margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 500; margin-bottom: 6px;">
          <span id="bulk-progress-status">Preparando envío...</span>
          <span id="bulk-progress-percentage" style="font-family: monospace; color: #38bdf8;">0%</span>
        </div>
        <div style="width: 100%; height: 12px; background: #0c0d12; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden; position: relative;">
          <div id="bulk-progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #059669, #10b981); transition: width 0.2s ease;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-top: 6px;">
          <span id="bulk-stats-counters">Exitosos: 0 | Errores: 0 | Omitidos: 0</span>
          <span id="bulk-eta" style="font-family: monospace;">Tiempo aprox: --</span>
        </div>
      </div>

      <!-- Log de envíos en vivo -->
      <div id="bulk-log-container" style="display: none; margin-bottom: 16px;">
        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Registro de actividad en vivo:</div>
        <div id="bulk-log-box" style="background: #090a0e; border: 1px solid var(--border-color); border-radius: 6px; padding: 10px; font-family: monospace; font-size: 11px; color: #cbd5e1; height: 140px; overflow-y: auto; line-height: 1.5;"></div>
      </div>

      <!-- Acciones del Modal -->
      <div style="display: flex; justify-content: flex-end; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button type="button" id="btn-cancel-bulk" class="btn btn-secondary">Cerrar</button>
        <button type="button" id="btn-pause-bulk" class="btn btn-secondary" style="display: none;">⏸ Pausar</button>
        <button type="button" id="btn-start-bulk" class="btn btn-primary" style="background: #059669; border-color: #10b981; font-weight: 600;">
          🚀 Iniciar Envío Masivo
        </button>
      </div>
    </div>
  </div>

  <!-- Biblioteca SheetJS local -->
  <script src="assets/xlsx.full.min.js"></script>
  <script src="assets/admin.js?v=<?= time() ?>"></script>
</body>
</html>
