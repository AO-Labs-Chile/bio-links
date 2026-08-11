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
require_once 'version.php';

// Verificar que sea admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login');
    exit;
}

$update_url = "https://raw.githubusercontent.com/AO-Labs-Chile/bio-links/main/updates/version.json";
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'do_update') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token CSRF inválido.";
    } else {
        // 1. Obtener la información de la actualización
        $json = @file_get_contents($update_url);
        if (!$json) {
            $error = "No se pudo conectar con el servidor de actualizaciones.";
        } else {
            $update_info = json_decode($json, true);
            if (!$update_info || !isset($update_info['download_url']) || !isset($update_info['version'])) {
                $error = "La respuesta del servidor de actualizaciones es inválida.";
            } else {
                // Comprobar que realmente es una versión nueva
                if (version_compare(APP_VERSION, $update_info['version'], '<')) {
                    $zip_url = $update_info['download_url'];
                    $zip_file = __DIR__ . '/update_temp.zip';
                    
                    // 2. Descargar el archivo ZIP
                    $ch = curl_init($zip_url);
                    $fp = fopen($zip_file, 'w+');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    fclose($fp);
                    
                    if ($http_code == 200 && file_exists($zip_file)) {
                        // 3. Extraer el ZIP
                        $zip = new ZipArchive;
                        if ($zip->open($zip_file) === TRUE) {
                            // Extraer archivos temporalmente o directamente
                            // Por seguridad, se extrae directamente sobre escribiendo, 
                            // asumiendo que el zip no contiene config.php o que el empaquetado de venta es seguro.
                            // Nota: Al empaquetar, NUNCA incluyas config.php en el .zip de actualización.
                            
                            $zip->extractTo(__DIR__);
                            $zip->close();
                            
                            // 4. Ejecutar db_update.php si vino incluido en la actualización (para migraciones)
                            if (file_exists(__DIR__ . '/db_update.php')) {
                                include __DIR__ . '/db_update.php';
                                @unlink(__DIR__ . '/db_update.php'); // Borrar tras ejecutar
                            }
                            
                            $success = "El sistema se ha actualizado correctamente a la versión " . htmlspecialchars($update_info['version']) . ".";
                        } else {
                            $error = "No se pudo extraer el archivo ZIP. Verifica los permisos.";
                        }
                        
                        @unlink($zip_file); // Borrar el zip temporal
                    } else {
                        $error = "Error al descargar el archivo de actualización. HTTP: " . $http_code;
                        if(file_exists($zip_file)) @unlink($zip_file);
                    }
                } else {
                    $error = "Ya tienes la última versión instalada.";
                }
            }
        }
    }
} else {
    // Si acceden directamente por GET, redirigimos o revisamos
    header('Location: admin');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizando Sistema...</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-md text-center">
        <?php if ($error): ?>
            <div class="text-red-500 mb-4">
                <i class="fas fa-exclamation-circle text-5xl mb-4"></i>
                <h2 class="text-xl font-bold">Error en la Actualización</h2>
                <p class="mt-2 text-gray-600"><?= htmlspecialchars($error) ?></p>
            </div>
            <a href="admin" class="inline-block mt-4 bg-gray-200 text-gray-800 px-6 py-2 rounded-xl hover:bg-gray-300 transition font-bold">Volver al Panel</a>
        <?php elseif ($success): ?>
            <div class="text-green-500 mb-4">
                <i class="fas fa-check-circle text-5xl mb-4"></i>
                <h2 class="text-xl font-bold">¡Actualización Exitosa!</h2>
                <p class="mt-2 text-gray-600"><?= htmlspecialchars($success) ?></p>
            </div>
            <a href="admin" class="inline-block mt-4 bg-purple-600 text-white px-6 py-2 rounded-xl hover:bg-purple-700 transition font-bold">Ir al Panel</a>
        <?php else: ?>
            <div class="text-blue-500 mb-4 animate-pulse">
                <i class="fas fa-spinner fa-spin text-5xl mb-4"></i>
                <h2 class="text-xl font-bold">Actualizando...</h2>
                <p class="mt-2 text-gray-600">Por favor, no cierres esta ventana.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
