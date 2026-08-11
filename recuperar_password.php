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
require_once 'smtp_mail.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error CSRF.");
    }

    $email = $_POST['email'] ?? '';
    
    if ($email) {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $pdo->prepare("UPDATE admin SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $admin['id']]);
            
            // Determinar la URL base dinámicamente
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $domain = $_SERVER['HTTP_HOST'];
            $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $base_url = $protocol . "://" . $domain . $path;
            
            $reset_link = $base_url . "/reset.php?token=" . urlencode($token);
            $cuerpo = "<h3>Recuperación de Contraseña</h3><p>Hola, solicitaste restablecer tu contraseña para Links.</p><p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p><p><a href='$reset_link'>$reset_link</a></p><p>Este enlace expira en 1 hora.</p>";
            
            if (send_smtp_email($email, "Recuperar Contraseña", $cuerpo)) {
                $msg = 'Se ha enviado un correo con las instrucciones.';
            } else {
                $error = 'Hubo un error al enviar el correo. Revisa la configuración del servidor.';
            }
        } else {
            $error = 'Ese correo no está registrado en el sistema. Asegúrate de haberlo guardado en la sección de Cuenta y Seguridad primero.';
        }
    } else {
        $error = 'Por favor, ingresa tu correo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-md border-t-4 border-purple-600">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Recuperar Contraseña</h2>
        </div>
        
        <?php if ($msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded text-sm"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="recuperar_password" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div>
                <label for="email" class="sr-only">Correo Electrónico</label>
                <input id="email" name="email" type="email" required class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" placeholder="Correo Electrónico asociado">
            </div>
            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                Enviar enlace
            </button>
            <div class="text-center mt-4">
                <a href="login" class="text-sm text-purple-600 hover:text-purple-500">Volver al Login</a>
            </div>
        </form>
    </div>
</body>
</html>
