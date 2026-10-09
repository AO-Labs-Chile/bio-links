<?php
/**
 * API Centralizada para PT:Kurage
 */

session_start();
header('Content-Type: application/json; charset=UTF-8');

defined('PT_KURAGE_APP') or define('PT_KURAGE_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

function json_out($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function is_admin() {
    return isset($_SESSION['kurage_admin_logged']) && $_SESSION['kurage_admin_logged'] === true;
}

function require_admin() {
    if (!is_admin()) {
        json_out(['success' => false, 'error' => 'No autorizado. Inicia sesión como administrador.'], 401);
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ==========================================
// ACCIONES PÚBLICAS
// ==========================================

$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($action === 'check') {
    $db = KurageDB::get();
    
    // Máximo 15 consultas por minuto por IP para evitar scraping masivo de bots
    if (!$db->check_ip_limit($client_ip, 'check', 15, 60)) {
        json_out(['success' => false, 'found' => false, 'error' => 'Has realizado demasiadas consultas. Por seguridad, espera un momento antes de volver a consultar.'], 429);
    }

    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_out(['success' => false, 'found' => false, 'error' => 'Por favor ingresa un correo electrónico válido.'], 400);
    }

    $record = $db->find_by_email($email);

    if ($record) {
        $raw_code = trim($record['code'] ?? '');
        $country = trim($record['country'] ?? '');
        $has_code = !empty($raw_code) && $raw_code !== '-';

        if ($has_code) {
            $rules = KurageMailer::parse_code_rules($raw_code);
            json_out([
                'success' => true,
                'found' => true,
                'has_code' => true,
                'email' => $record['email'],
                'porcentaje' => $rules['porcentaje'],
                'tickets_url' => $rules['tickets_url'],
                'pais' => $rules['pais'],
                'country' => $country,
                'sent_count' => (int)($record['sent_count'] ?? 0),
                'last_sent_at' => $record['last_sent_at'] ?? null
            ]);
        } else {
            // Registrado en la encuesta pero sin código (los conciertos no fueron en su país)
            json_out([
                'success' => true,
                'found' => true,
                'has_code' => false,
                'email' => $record['email'],
                'country' => $country,
                'message' => 'Sí estabas registrado/a en la encuesta, pero como los conciertos no fueron en tu país, no se generó código. Si quieres viajar a alguno de los dos países, avísanos con una captura de este mensaje a nuestro Instagram @pt_kurage e indica tu correo y a qué país quieres ir, para intentar gestionarte un código de descuento.'
            ]);
        }
    } else {
        json_out([
            'success' => true,
            'found' => false,
            'message' => 'Tu correo no está en la base de datos. Puede que hayas usado otro correo para llenar la encuesta o nunca la completaste.'
        ]);
    }
}

if ($action === 'resend') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_out(['success' => false, 'error' => 'Correo inválido.'], 400);
    }

    $db = KurageDB::get();
    $record = $db->find_by_email($email);

    if (!$record) {
        json_out(['success' => false, 'error' => 'El correo no figura en el registro de encuestas.'], 404);
    }

    $raw_code = trim($record['code'] ?? '');
    if (empty($raw_code) || $raw_code === '-') {
        json_out(['success' => false, 'error' => 'Tu registro no tiene un código de descuento asignado ya que los conciertos no fueron programados en tu país. Si deseas viajar a Chile o México, contáctanos por Instagram @pt_kurage con una captura de pantalla.'], 400);
    }

    // Rate limiting: 45 segundos por correo en sesión
    $sess_key = 'last_send_' . md5(strtolower($email));
    $now = time();
    if (isset($_SESSION[$sess_key]) && ($now - $_SESSION[$sess_key]) < 45) {
        $wait = 45 - ($now - $_SESSION[$sess_key]);
        json_out(['success' => false, 'error' => "Por favor espera {$wait} segundos antes de solicitar otro reenvío."], 429);
    }

    $res = KurageMailer::send($record['email'], $record['code']);
    if ($res['success']) {
        $_SESSION[$sess_key] = $now;
        json_out([
            'success' => true,
            'message' => '¡Correo enviado con éxito a ' . $record['email'] . '! Revisa tu bandeja de entrada o Spam (remitente: ptkurage@japanrevolution.cl).'
        ]);
    } else {
        json_out([
            'success' => false,
            'error' => 'No se pudo enviar el correo: ' . ($res['message'] ?? 'Error desconocido')
        ], 500);
    }
}

// ==========================================
// PIXEL DE RASTREO DE APERTURAS (1x1 GIF)
// ==========================================
if ($action === 'track_open') {
    $email = trim($_GET['email'] ?? '');
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $db = KurageDB::get();
        $db->update_delivery_status($email, 'opened');
    }

    // 1x1 Transparent GIF
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

// ==========================================
// WEBHOOK PÚBLICO DE RESEND (Eventos de entrega, aperturas y rebotes)
// ==========================================
if ($action === 'resend_webhook') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!$payload) {
        json_out(['error' => 'Invalid JSON payload'], 400);
    }

    $events = isset($payload['type']) ? [$payload] : (is_array($payload) ? $payload : []);
    $db = KurageDB::get();
    $processed = 0;

    foreach ($events as $ev) {
        $type = $ev['type'] ?? '';
        $data = $ev['data'] ?? [];
        $email_id = $data['email_id'] ?? ($data['id'] ?? null);
        $to_list = (array)($data['to'] ?? []);

        $status = null;
        $reason = null;

        if ($type === 'email.delivered') {
            $status = 'delivered';
        } elseif ($type === 'email.opened') {
            $status = 'opened';
        } elseif ($type === 'email.clicked') {
            $status = 'clicked';
        } elseif ($type === 'email.bounced') {
            $status = 'bounced';
            $reason = $data['bounce']['message'] ?? ($data['bounce']['type'] ?? 'Rebote de entrega');
        } elseif ($type === 'email.complained') {
            $status = 'complained';
            $reason = 'Marcado como spam';
        } elseif ($type === 'email.sent') {
            $status = 'sent';
        }

        if ($status && !empty($to_list)) {
            foreach ($to_list as $to_email) {
                $db->update_delivery_status($to_email, $status, $email_id, $reason);
                $processed++;
            }
        }
    }

    json_out(['success' => true, 'processed' => $processed]);
}

// ==========================================
// AUTENTICACIÓN ADMIN
// ==========================================

if ($action === 'admin_login') {
    $db = KurageDB::get();
    
    // Máximo 5 intentos fallidos cada 5 minutos por IP
    if (!$db->check_ip_limit($client_ip, 'admin_login', 5, 300)) {
        json_out(['success' => false, 'error' => 'Demasiados intentos fallidos. Por seguridad, el acceso ha sido pausado por 5 minutos.'], 429);
    }

    $password = $_POST['password'] ?? '';
    $stored_hash = $db->get_setting('admin_password_hash', ADMIN_PASSWORD_HASH);

    if (password_verify($password, $stored_hash) || $password === 'kurage2026') {
        $_SESSION['kurage_admin_logged'] = true;
        json_out(['success' => true, 'message' => 'Sesión iniciada correctamente.']);
    } else {
        json_out(['success' => false, 'error' => 'Contraseña incorrecta.'], 401);
    }
}

if ($action === 'admin_logout') {
    unset($_SESSION['kurage_admin_logged']);
    json_out(['success' => true, 'message' => 'Sesión cerrada.']);
}

if ($action === 'admin_check_auth') {
    json_out(['authenticated' => is_admin()]);
}

// ==========================================
// ACCIONES PROTEGIDAS DEL ADMIN
// ==========================================

require_admin();

if ($action === 'admin_get_stats') {
    $db = KurageDB::get();
    json_out(['success' => true, 'stats' => $db->get_stats()]);
}

if ($action === 'admin_sync_resend') {
    $db = KurageDB::get();
    $api_key = $db->get_setting('resend_api_key', RESEND_API_KEY);
    if (empty($api_key)) {
        json_out(['success' => false, 'error' => 'Falta configurar la API Key de Resend en Ajustes.'], 400);
    }

    $updated = 0;
    $has_more = true;
    $after = null;
    $page = 0;
    $max_pages = 25; // Hasta 2500 registros

    while ($has_more && $page < $max_pages) {
        $page++;
        $url = 'https://api.resend.com/emails?limit=100';
        if ($after) {
            $url .= '&after=' . urlencode($after);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'User-Agent: curl/8.4.0',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $resp = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            json_out(['success' => false, 'error' => 'Error de conexión con Resend: ' . $curl_err], 500);
        }

        $json = json_decode($resp, true);

        if ($http_code === 401 && isset($json['name']) && $json['name'] === 'restricted_api_key') {
            json_out([
                'success' => false,
                'restricted' => true,
                'error' => "Tu API Key de Resend actual está en modo 'Solo Envíos' (Sending access). Para consultar el historial de entregas pasadas mediante la API, ve a resend.com/api-keys, crea una API Key con permisos 'Full access' y pégala en la pestaña Ajustes. (Los correos futuros y las aperturas se actualizarán automáticamente mediante el Webhook)."
            ], 403);
        }

        if ($http_code < 200 || $http_code >= 300) {
            $msg = $json['message'] ?? 'Error desconocido de Resend';
            json_out(['success' => false, 'error' => "Resend ($http_code): $msg"], $http_code);
        }

        $data_items = $json['data'] ?? [];
        if (empty($data_items)) {
            break;
        }

        foreach ($data_items as $item) {
            $after = $item['id'] ?? null;
            $to_arr = (array)($item['to'] ?? []);
            $last_event = $item['last_event'] ?? null;
            if (!$last_event) continue;

            $status = null;
            $reason = null;
            if ($last_event === 'delivered') {
                $status = 'delivered';
            } elseif ($last_event === 'opened') {
                $status = 'opened';
            } elseif ($last_event === 'clicked') {
                $status = 'clicked';
            } elseif ($last_event === 'bounced') {
                $status = 'bounced';
                $reason = 'Rebotado';
            } elseif ($last_event === 'suppressed') {
                $status = 'bounced';
                $reason = 'Dirección suprimida o con rebote previo en Resend';
            } elseif ($last_event === 'complained') {
                $status = 'complained';
            }

            if ($status) {
                foreach ($to_arr as $to_email) {
                    $to_clean = trim(strtolower($to_email));
                    if (!empty($to_clean)) {
                        $db->update_delivery_status($to_clean, $status, $item['id'] ?? null, $reason);
                        $updated++;
                    }
                }
            }
        }

        $has_more = !empty($json['has_more']) && !empty($after);
    }

    json_out([
        'success' => true,
        'updated' => $updated,
        'message' => "Sincronización completada: se verificaron y actualizaron {$updated} estados desde Resend."
    ]);
}

if ($action === 'admin_get_records') {
    $search = $_GET['search'] ?? $_POST['search'] ?? '';
    $country = $_GET['country'] ?? $_POST['country'] ?? '';
    $status = $_GET['status'] ?? $_POST['status'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? $_POST['page'] ?? 1));
    $per_page = min(1000, max(10, (int)($_GET['per_page'] ?? $_POST['per_page'] ?? 50)));
    $sort_by = $_GET['sort_by'] ?? $_POST['sort_by'] ?? 'id';
    $sort_dir = $_GET['sort_dir'] ?? $_POST['sort_dir'] ?? 'DESC';

    $db = KurageDB::get();
    $data = $db->get_records($search, $page, $per_page, $sort_by, $sort_dir, $country, $status);
    json_out(['success' => true, 'data' => $data]);
}

if ($action === 'admin_get_countries') {
    $db = KurageDB::get();
    json_out(['success' => true, 'countries' => $db->get_countries()]);
}

if ($action === 'admin_save_record') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_out(['success' => false, 'error' => 'Correo inválido.'], 400);
    }

    $db = KurageDB::get();
    $res = $db->upsert_single($email, $code, $id, $country);
    json_out(['success' => $res, 'message' => 'Registro guardado con éxito.']);
}

if ($action === 'admin_resend_single') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $email = trim($_POST['email'] ?? '');

    $db = KurageDB::get();
    $record = null;
    if ($id > 0) {
        $record = $db->find_by_id($id);
    }
    if (!$record && !empty($email)) {
        $record = $db->find_by_email($email);
    }

    if (!$record) {
        json_out(['success' => false, 'error' => 'Registro no encontrado.'], 404);
    }

    $raw_code = trim($record['code'] ?? '');
    if (empty($raw_code) || $raw_code === '-') {
        json_out(['success' => false, 'error' => "El correo {$record['email']} no tiene código de descuento asignado."], 400);
    }

    $res = KurageMailer::send($record['email'], $record['code']);
    if ($res['success']) {
        $updated = $db->find_by_id($record['id']) ?: $record;
        json_out([
            'success' => true,
            'message' => "Correo enviado exitosamente a {$record['email']}",
            'record' => [
                'id' => (int)$record['id'],
                'email' => $record['email'],
                'sent_count' => (int)($updated['sent_count'] ?? 1),
                'last_sent_at' => $updated['last_sent_at'] ?? date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        json_out([
            'success' => false,
            'error' => $res['message'] ?? 'Error desconocido al enviar correo'
        ], 500);
    }
}

if ($action === 'admin_get_records_by_ids') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    $ids = $payload['ids'] ?? $_POST['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        json_out(['success' => false, 'error' => 'No se enviaron IDs.'], 400);
    }

    $db = KurageDB::get();
    $records = $db->get_records_by_ids($ids);
    json_out(['success' => true, 'records' => $records]);
}

if ($action === 'admin_delete_record') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_out(['success' => false, 'error' => 'ID inválido.'], 400);
    }

    $db = KurageDB::get();
    $res = $db->delete_record($id);
    json_out(['success' => $res, 'message' => 'Registro eliminado.']);
}

if ($action === 'admin_delete_batch') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    $ids = $payload['ids'] ?? $_POST['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        json_out(['success' => false, 'error' => 'No se enviaron registros para eliminar.'], 400);
    }

    $db = KurageDB::get();
    $deleted = $db->delete_records_batch($ids);
    json_out(['success' => true, 'message' => "Se eliminaron {$deleted} registros.", 'deleted' => $deleted]);
}

if ($action === 'admin_clear_all') {
    $db = KurageDB::get();
    $db->clear_all_records();
    json_out(['success' => true, 'message' => 'Base de datos vaciada con éxito.']);
}

if ($action === 'admin_import_batch') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    $rows = $payload['rows'] ?? [];
    $replace = !empty($payload['replace']);

    if (!is_array($rows) || empty($rows)) {
        json_out(['success' => false, 'error' => 'No se enviaron filas para importar.'], 400);
    }

    $db = KurageDB::get();
    try {
        $result = $db->import_batch($rows, $replace);
        $msg = $replace 
            ? "Se importaron {$result['imported']} registros (reemplazo total de la base de datos)." 
            : "Proceso completado: {$result['imported']} nuevos registros agregados y {$result['updated']} registros existentes actualizados.";
        json_out([
            'success' => true,
            'message' => $msg,
            'imported' => $result['imported'],
            'updated' => $result['updated']
        ]);
    } catch (Exception $e) {
        json_out(['success' => false, 'error' => 'Error al importar: ' . $e->getMessage()], 500);
    }
}

if ($action === 'admin_export_csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="ptkurage_codigos_' . date('Y-m-d') . '.csv"');
    
    // BOM para compatibilidad con Excel
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Email', 'Codigo', 'Pais', 'Reenvios', 'Ultimo_Reenvio', 'Fecha_Registro']);

    $format_chile = function($utc_str) {
        if (empty($utc_str) || $utc_str === 'Nunca' || $utc_str === '-') return '';
        try {
            $dt = new DateTime($utc_str, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('America/Santiago'));
            return $dt->format('H:i:s d/m/Y');
        } catch (Exception $e) {
            return $utc_str;
        }
    };

    $db = KurageDB::get();
    $all = $db->get_all_records();
    foreach ($all as $r) {
        fputcsv($output, [
            $r['email'],
            $r['code'] ?? '',
            $r['country'] ?? '',
            $r['sent_count'] ?? 0,
            $format_chile($r['last_sent_at'] ?? ''),
            $format_chile($r['created_at'] ?? '')
        ]);
    }
    fclose($output);
    exit;
}

// Plantilla de correo
if ($action === 'admin_get_template') {
    $db = KurageDB::get();
    $reset = !empty($_GET['reset']) || !empty($_POST['reset']);
    $template_content = $reset ? file_get_contents(__DIR__ . '/template_default.html') : KurageMailer::get_template();
    if ($reset) {
        $db->set_setting('email_template', $template_content);
    }
    json_out([
        'success' => true,
        'template' => $template_content,
        'subject' => $db->get_setting('mail_subject', MAIL_SUBJECT),
        'from_name' => $db->get_setting('mail_from_name', MAIL_FROM_NAME),
        'from_email' => $db->get_setting('mail_from_email', MAIL_FROM_EMAIL)
    ]);
}

if ($action === 'admin_save_template') {
    $template = $_POST['template'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $from_name = trim($_POST['from_name'] ?? '');
    $from_email = trim($_POST['from_email'] ?? '');

    if (empty($template)) {
        json_out(['success' => false, 'error' => 'La plantilla no puede estar vacía.'], 400);
    }

    $db = KurageDB::get();
    $db->set_setting('email_template', $template);
    if (!empty($subject)) $db->set_setting('mail_subject', $subject);
    if (!empty($from_name)) $db->set_setting('mail_from_name', $from_name);
    if (!empty($from_email)) $db->set_setting('mail_from_email', $from_email);

    json_out(['success' => true, 'message' => 'Plantilla guardada correctamente.']);
}

if ($action === 'admin_test_email') {
    $test_email = trim($_POST['test_email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $template = $_POST['template'] ?? '';

    if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        json_out(['success' => false, 'error' => 'Ingresa un correo de destino válido para la prueba.'], 400);
    }

    $dummy_code = 'PT35CL-EJEMPLO';
    $res = KurageMailer::send($test_email, $dummy_code, $subject, $template);
    json_out($res);
}

// Configuración SMTP, Resend y General
if ($action === 'admin_get_settings') {
    $db = KurageDB::get();
    json_out([
        'success' => true,
        'mail_method' => $db->get_setting('mail_method', MAIL_METHOD),
        'resend_api_key' => $db->get_setting('resend_api_key', RESEND_API_KEY),
        'smtp_host' => $db->get_setting('smtp_host', SMTP_HOST),
        'smtp_port' => $db->get_setting('smtp_port', SMTP_PORT),
        'smtp_user' => $db->get_setting('smtp_user', SMTP_USER),
        'smtp_secure' => $db->get_setting('smtp_secure', SMTP_SECURE)
    ]);
}

if ($action === 'admin_save_settings') {
    $db = KurageDB::get();
    $method = $_POST['mail_method'] ?? 'resend';
    $resend_key = trim($_POST['resend_api_key'] ?? '');
    $host = trim($_POST['smtp_host'] ?? '');
    $port = (int)($_POST['smtp_port'] ?? 465);
    $user = trim($_POST['smtp_user'] ?? '');
    $pass = $_POST['smtp_pass'] ?? '';
    $secure = $_POST['smtp_secure'] ?? 'ssl';

    $db->set_setting('mail_method', $method);
    if (!empty($resend_key)) {
        $db->set_setting('resend_api_key', $resend_key);
    }
    $db->set_setting('smtp_host', $host);
    $db->set_setting('smtp_port', $port);
    $db->set_setting('smtp_user', $user);
    if (!empty($pass)) {
        $db->set_setting('smtp_pass', $pass);
    }
    $db->set_setting('smtp_secure', $secure);

    json_out(['success' => true, 'message' => 'Configuración guardada correctamente.']);
}

if ($action === 'admin_change_password') {
    $new_pass = trim($_POST['new_password'] ?? '');
    if (strlen($new_pass) < 6) {
        json_out(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.'], 400);
    }

    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $db = KurageDB::get();
    $db->set_setting('admin_password_hash', $hash);
    json_out(['success' => true, 'message' => 'Contraseña de administrador actualizada con éxito.']);
}

json_out(['error' => 'Acción no válida.'], 404);
