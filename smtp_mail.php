<?php
/**
 * Bio Links Script
 * 
 * @author    Seto Design
 * @copyright 2026 Seto Design
 * @version   1.0.0
 * 
 * Este software es distribuido bajo la licencia de Codester.
 * Prohibida su reventa directa sin autorizacion.
 */
function send_smtp_email($to, $subject, $message) {
    // Si no está configurado SMTP, fallar silenciosamente o devolver false
    if (!defined('SMTP_HOST') || empty(SMTP_HOST)) {
        return false;
    }
    
    $host = SMTP_HOST;
    $port = defined('SMTP_PORT') && !empty(SMTP_PORT) ? (int)SMTP_PORT : 465;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    
    $timeout = 10;
    
    $socket = fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$socket) return false;
    
    function read_smtp_response($socket) {
        $res = '';
        while ($str = fgets($socket, 515)) {
            $res .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $res;
    }

    read_smtp_response($socket);
    
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    fputs($socket, "EHLO $domain\r\n");
    read_smtp_response($socket);
    
    fputs($socket, "AUTH LOGIN\r\n");
    read_smtp_response($socket);
    
    fputs($socket, base64_encode($user) . "\r\n");
    read_smtp_response($socket);
    
    fputs($socket, base64_encode($pass) . "\r\n");
    $auth_res = read_smtp_response($socket);
    
    if (strpos($auth_res, '235') === false) {
        fclose($socket);
        return false;
    }
    
    fputs($socket, "MAIL FROM: <$user>\r\n");
    read_smtp_response($socket);
    
    fputs($socket, "RCPT TO: <$to>\r\n");
    read_smtp_response($socket);
    
    fputs($socket, "DATA\r\n");
    read_smtp_response($socket);
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: Links Admin <$user>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "Date: " . date("r") . "\r\n";
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $headers .= "Message-ID: <" . uniqid() . "@$domain>\r\n";
    
    $data = $headers . "\r\n" . $message . "\r\n.\r\n";
    fputs($socket, $data);
    $send_res = read_smtp_response($socket);
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    // Si el servidor acepta el mensaje, devuelve 250
    if (strpos($send_res, '250') !== false) {
        return true;
    }
    return false;
}
?>
