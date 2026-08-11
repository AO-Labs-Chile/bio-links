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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
if (!$token) {
    die("Token inválido o ausente.");
}

$stmt = $pdo->prepare("SELECT * FROM admin WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
$stmt->execute([$token]);
$admin = $stmt->fetch();

if (!$admin) {
    die("El token es inválido o ha expirado. Por favor solicita uno nuevo.");
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error CSRF.");
    }

    $pass1 = $_POST['pass1'] ?? '';
    $pass2 = $_POST['pass2'] ?? '';

    if (strlen($pass1) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($pass1 !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $admin['id']]);
        
        $msg = "Contraseña actualizada exitosamente. Ahora puedes <a href='login' class='underline'>iniciar sesión</a>.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-md border-t-4 border-purple-600">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Nueva Contraseña</h2>
        </div>
        
        <?php if ($msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded text-sm"><?= $msg ?></div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="reset" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div>
                    <label class="sr-only">Nueva Contraseña</label>
                    <input name="pass1" type="password" required class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm mb-3" placeholder="Nueva Contraseña">
                    <label class="sr-only">Repetir Contraseña</label>
                    <input name="pass2" type="password" required class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" placeholder="Repetir Contraseña">
                </div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                    Cambiar Contraseña
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
