<?php
/**
 * Motor de envío de correos HTML para PT:Kurage
 * Soporta SMTP autenticado y función mail() nativa
 */

defined('PT_KURAGE_APP') or define('PT_KURAGE_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class KurageMailer {

    public static function get_template() {
        $db = KurageDB::get();
        $custom_tpl = $db->get_setting('email_template');
        if (!empty($custom_tpl)) {
            return $custom_tpl;
        }

        $default_file = __DIR__ . '/template_default.html';
        if (file_exists($default_file)) {
            return file_get_contents($default_file);
        }

        return "<p>Hola! Tu codigo de descuento es: <strong>{CODIGO}</strong></p>";
    }

    public static function parse_code_rules($code) {
        $c = strtoupper(trim($code));

        // Porcentaje:
        // Si empieza con PT35 -> 35
        // Si empieza con PT20 -> 20
        $porcentaje = '35';
        if (strpos($c, 'PT20') === 0) {
            $porcentaje = '20';
        } elseif (strpos($c, 'PT35') === 0) {
            $porcentaje = '35';
        } elseif (strpos($c, '20') !== false) {
            $porcentaje = '20';
        }

        // Tickets URL según país (CL o MX):
        $chile_url = 'https://www.ritstore.agency/event-details/plastic-tree-latin-america-tour-2027-replay-chile?currency=CLP';
        $mexico_url = 'https://www.ritstore.agency/event-details/plastic-tree-latin-america-tour-2027-replay-mexico?currency=CLP';

        $tickets_url = $chile_url;
        $pais = 'Chile';

        if (strpos($c, 'MX') !== false) {
            $tickets_url = $mexico_url;
            $pais = 'México';
        } elseif (strpos($c, 'CL') !== false) {
            $tickets_url = $chile_url;
            $pais = 'Chile';
        }

        return [
            'porcentaje' => $porcentaje,
            'tickets_url' => $tickets_url,
            'pais' => $pais
        ];
    }

    public static function render_template($template_html, $email, $code) {
        $rules = self::parse_code_rules($code);

        $cabecera_url = BASE_URL . '/assets/cabecera-mail.png';
        $logo_url = BASE_URL . '/assets/logo-white-trimmed.png';
        $logo_rit_url = BASE_URL . '/assets/logo-rit-trimmed.png';
        $site_url = BASE_URL;
        $fecha = date('d/m/Y');

        $replacements = [
            '{CODIGO}' => htmlspecialchars($code),
            '{EMAIL}' => htmlspecialchars($email),
            '{PORCENTAJE}' => $rules['porcentaje'],
            '{TICKETS_URL}' => htmlspecialchars($rules['tickets_url']),
            '{PAIS}' => $rules['pais'],
            '{CABECERA_URL}' => $cabecera_url,
            '{LOGO_URL}' => $logo_url,
            '{LOGO_RIT_URL}' => $logo_rit_url,
            '{SITIO_URL}' => $site_url,
            '{INSTAGRAM_URL}' => 'https://www.instagram.com/pt_kurage',
            '{IG_URL}' => 'https://www.instagram.com/pt_kurage',
            '{RIT_URL}' => 'https://www.rit.agency/',
            '{FECHA}' => $fecha
        ];

        $rendered = str_replace(array_keys($replacements), array_values($replacements), $template_html);

        // Pixel de rastreo de apertura nativo de PT:Kurage
        $tracking_url = BASE_URL . '/api.php?action=track_open&email=' . urlencode($email);
        $pixel_tag = '<img src="' . htmlspecialchars($tracking_url) . '" width="1" height="1" style="display:none;width:1px;height:1px;border:0;opacity:0;" alt="" />';
        if (stripos($rendered, '</body>') !== false) {
            $rendered = str_ireplace('</body>', $pixel_tag . '</body>', $rendered);
        } else {
            $rendered .= $pixel_tag;
        }

        return $rendered;
    }

    public static function send($to_email, $code, $subject_override = null, $body_override = null) {
        $db = KurageDB::get();

        $from_name = $db->get_setting('mail_from_name', MAIL_FROM_NAME);
        $from_email = $db->get_setting('mail_from_email', MAIL_FROM_EMAIL);
        $subject = $subject_override ?: $db->get_setting('mail_subject', MAIL_SUBJECT);
        
        $template = $body_override ?: self::get_template();
        $html_body = self::render_template($template, $to_email, $code);

        $method = $db->get_setting('mail_method', MAIL_METHOD);

        if ($method === 'resend') {
            $res = self::send_resend($to_email, $subject, $html_body, $from_name, $from_email);
            if ($res['success']) {
                $db->record_sent($to_email, $res['id'] ?? null);
            }
            return $res;
        } elseif ($method === 'smtp') {
            $res = self::send_smtp($to_email, $subject, $html_body, $from_name, $from_email);
            if ($res['success']) {
                $db->record_sent($to_email);
                return $res;
            }
            // Si SMTP falló, intentar fallback a mail() nativo
            $res_mail = self::send_native_mail($to_email, $subject, $html_body, $from_name, $from_email);
            if ($res_mail['success']) {
                $db->record_sent($to_email);
                return ['success' => true, 'message' => 'Enviado mediante mail() (SMTP falló: ' . $res['message'] . ')'];
            }
            return $res; // Devuelve el error original de SMTP
        } else {
            $res = self::send_native_mail($to_email, $subject, $html_body, $from_name, $from_email);
            if ($res['success']) {
                $db->record_sent($to_email);
            }
            return $res;
        }
    }

    public static function send_resend($to, $subject, $message, $from_name, $from_email) {
        $db = KurageDB::get();
        $api_key = $db->get_setting('resend_api_key', RESEND_API_KEY);
        if (empty($api_key)) {
            return ['success' => false, 'message' => 'Falta configurar la API Key de Resend'];
        }

        $from = !empty($from_name) ? "{$from_name} <{$from_email}>" : $from_email;

        $payload = json_encode([
            'from' => $from,
            'to' => [$to],
            'subject' => $subject,
            'html' => $message
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            return ['success' => false, 'message' => 'Error de conexión con Resend: ' . $curl_err];
        }

        $res = json_decode($response, true);
        if ($http_code >= 200 && $http_code < 300) {
            $email_id = $res['id'] ?? null;
            return ['success' => true, 'message' => 'Correo enviado exitosamente vía Resend', 'id' => $email_id];
        } else {
            $msg = $res['message'] ?? ($res['error'] ?? 'Error desconocido');
            return ['success' => false, 'message' => "Resend ($http_code): $msg"];
        }
    }

    public static function send_native_mail($to, $subject, $message, $from_name, $from_email) {
        $encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $encoded_from = "=?UTF-8?B?" . base64_encode($from_name) . "?= <$from_email>";

        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "From: $encoded_from";
        $headers[] = "Reply-To: $from_email";
        $headers[] = "X-Mailer: PHP/" . phpversion();

        $headers_str = implode("\r\n", $headers);

        $sent = @mail($to, $encoded_subject, $message, $headers_str);
        if ($sent) {
            return ['success' => true, 'message' => 'Correo enviado correctamente vía mail()'];
        } else {
            $err = error_get_last();
            $msg = $err ? $err['message'] : 'No se pudo enviar el correo mediante mail(). Verifica la configuración del servidor.';
            return ['success' => false, 'message' => $msg];
        }
    }

    public static function send_smtp($to, $subject, $message, $from_name, $from_email) {
        $db = KurageDB::get();
        $host = $db->get_setting('smtp_host', SMTP_HOST);
        $port = (int)$db->get_setting('smtp_port', SMTP_PORT);
        $user = $db->get_setting('smtp_user', SMTP_USER);
        $pass = $db->get_setting('smtp_pass', SMTP_PASS);
        $secure = $db->get_setting('smtp_secure', SMTP_SECURE); // 'ssl', 'tls', 'none'

        if (empty($host) || empty($user)) {
            return ['success' => false, 'message' => 'Datos SMTP incompletos (Servidor o Usuario vacío)'];
        }

        $prefix = '';
        if ($secure === 'ssl' && strpos($host, 'ssl://') === false) {
            $prefix = 'ssl://';
        }

        $timeout = 12;
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            return ['success' => false, 'message' => "Error de conexión SMTP: $errstr ($errno)"];
        }

        $read = function($sock) {
            $res = '';
            while ($str = fgets($sock, 515)) {
                $res .= $str;
                if (substr($str, 3, 1) == ' ') break;
            }
            return $res;
        };

        $banner = $read($socket);

        $domain = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']) : 'localhost';
        fputs($socket, "EHLO $domain\r\n");
        $ehlo_res = $read($socket);

        // STARTTLS si es puerto 587 o seguro tls
        if ($secure === 'tls' || ($port == 587 && strpos($ehlo_res, 'STARTTLS') !== false)) {
            fputs($socket, "STARTTLS\r\n");
            $tls_res = $read($socket);
            if (strpos($tls_res, '220') !== false) {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fputs($socket, "EHLO $domain\r\n");
                $read($socket);
            }
        }

        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        $auth_prompt = $read($socket);

        fputs($socket, base64_encode($user) . "\r\n");
        $read($socket);

        fputs($socket, base64_encode($pass) . "\r\n");
        $auth_res = $read($socket);

        if (strpos($auth_res, '235') === false) {
            fclose($socket);
            return ['success' => false, 'message' => "Fallo de autenticación SMTP: " . trim($auth_res)];
        }

        fputs($socket, "MAIL FROM: <$from_email>\r\n");
        $from_res = $read($socket);
        if (strpos($from_res, '250') === false) {
            fclose($socket);
            return ['success' => false, 'message' => "Error en MAIL FROM: " . trim($from_res)];
        }

        fputs($socket, "RCPT TO: <$to>\r\n");
        $to_res = $read($socket);
        if (strpos($to_res, '250') === false && strpos($to_res, '251') === false) {
            fclose($socket);
            return ['success' => false, 'message' => "Error en RCPT TO: " . trim($to_res)];
        }

        fputs($socket, "DATA\r\n");
        $data_prompt = $read($socket);

        $encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $encoded_from = "=?UTF-8?B?" . base64_encode($from_name) . "?= <$from_email>";

        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "From: $encoded_from";
        $headers[] = "To: $to";
        $headers[] = "Subject: $encoded_subject";
        $headers[] = "Date: " . date("r");
        $headers[] = "Message-ID: <" . uniqid() . "@$domain>";

        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $message . "\r\n.\r\n";
        fputs($socket, $payload);
        $send_res = $read($socket);

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        if (strpos($send_res, '250') !== false) {
            return ['success' => true, 'message' => 'Correo enviado correctamente por SMTP'];
        } else {
            return ['success' => false, 'message' => 'Fallo al entregar el mensaje SMTP: ' . trim($send_res)];
        }
    }
}
