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
session_start();
require_once 'db.php';

// Verificar si Google Auth está configurado
if (!defined('GOOGLE_CLIENT_ID') || empty(GOOGLE_CLIENT_ID) || !defined('GOOGLE_CLIENT_SECRET') || empty(GOOGLE_CLIENT_SECRET)) {
    die("El inicio de sesión con Google no está configurado en este servidor.");
}

$client_id = GOOGLE_CLIENT_ID;
$client_secret = GOOGLE_CLIENT_SECRET;
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['REQUEST_URI']);
if ($path === '/' || $path === '\\') $path = '';
$redirect_uri = rtrim($protocol . $domainName . $path, '/') . '/google_auth.php';

// Si no hay código y no hay error, generar URL y redirigir
if (!isset($_GET['code']) && !isset($_GET['error'])) {
    $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'state' => bin2hex(random_bytes(16))
    ]);
    header('Location: ' . $auth_url);
    exit;
}

if (isset($_GET['error'])) {
    die("Error al autenticar con Google: " . htmlspecialchars($_GET['error']));
}

if (isset($_GET['code'])) {
    // Intercambiar código por token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code',
        'code' => $_GET['code']
    ]));
    $response = curl_exec($ch);
    curl_close($ch);
    $token_data = json_decode($response, true);

    if (isset($token_data['error'])) {
        die("Error obteniendo token: " . htmlspecialchars($token_data['error_description']));
    }

    $access_token = $token_data['access_token'];

    // Obtener info del usuario
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $response = curl_exec($ch);
    curl_close($ch);
    $user_info = json_decode($response, true);

    if (isset($user_info['error'])) {
        die("Error obteniendo información de usuario.");
    }

    $google_id = $user_info['id'];
    $email = $user_info['email'];

    // Lógica de sesión
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        // Estamos logueados, así que queremos vincular la cuenta de Google
        $stmt = $pdo->prepare("UPDATE admin SET google_id = ?, email = ? WHERE id = ?");
        $stmt->execute([$google_id, $email, $_SESSION['admin_id']]);
        header('Location: settings?msg=google_linked');
        exit;
    } else {
        // Estamos tratando de iniciar sesión
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE google_id = ? LIMIT 1");
        $stmt->execute([$google_id]);
        $admin = $stmt->fetch();

        if ($admin) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            header('Location: admin');
            exit;
        } else {
            // Intentar buscar por correo por si no estaba vinculado pero tiene el mismo correo
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            if ($admin) {
                // Auto-vincular
                $stmt = $pdo->prepare("UPDATE admin SET google_id = ? WHERE id = ?");
                $stmt->execute([$google_id, $admin['id']]);
                
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                header('Location: admin');
                exit;
            } else {
                die("Esta cuenta de Google no está vinculada a ningún administrador. Inicia sesión normalmente y ve a Ajustes para vincularla.");
            }
        }
    }
}
?>
