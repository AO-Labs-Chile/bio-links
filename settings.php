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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = '';

// Auto-migration for SEO columns
try {
    $pdo->exec("ALTER TABLE config ADD COLUMN seo_titulo VARCHAR(255) DEFAULT ''");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE config ADD COLUMN seo_descripcion TEXT");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE config ADD COLUMN seo_imagen VARCHAR(255) DEFAULT ''");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE config ADD COLUMN analytics_id VARCHAR(50) DEFAULT ''");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE config ADD COLUMN facebook_pixel VARCHAR(50) DEFAULT ''");
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error de seguridad (CSRF). Por favor recarga la página.");
    }
    function handleUpload($fileInputName) {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES[$fileInputName]['tmp_name'];
            $name = basename($_FILES[$fileInputName]['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0755, true);
                }
                $newName = uniqid() . '.' . $ext;
                $destination = 'uploads/' . $newName;
                if (move_uploaded_file($tmp_name, $destination)) {
                    return $destination;
                }
            }
        }
        return null;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_config') {
        $nombre_sitio = $_POST['nombre_sitio'] ?? '';
        $biografia = $_POST['biografia'] ?? '';
        
        $color_fondo = $_POST['color_fondo'] ?? '#4c1d95';
        $color_fondo_secundario = $_POST['color_fondo_secundario'] ?? '';
        $angulo_gradiente = $_POST['angulo_gradiente'] ?? 180;
        
        $color_boton = $_POST['color_boton'] ?? '#4ade80';
        $color_boton_texto = $_POST['color_boton_texto'] ?? '#111827';
        $color_texto = $_POST['color_texto'] ?? '#f3f4f6';
        $patron_fondo = $_POST['patron_fondo'] ?? 'ninguno';

        $fuente_texto = $_POST['fuente_texto'] ?? 'Inter';
        $estilo_boton = $_POST['estilo_boton'] ?? 'solid';
        $redondeo_boton = $_POST['redondeo_boton'] ?? 'rounded-xl';
        $texto_footer = $_POST['texto_footer'] ?? '';
        $tipo_gradiente = $_POST['tipo_gradiente'] ?? 'lineal';
        $idioma = $_POST['idioma'] ?? 'es';

        $url_logo = $_POST['current_logo'] ?? '';
        $uploadedLogo = handleUpload('logo_file');
        if ($uploadedLogo) {
            $url_logo = $uploadedLogo;
        }

        $url_fondo = $_POST['current_fondo'] ?? '';
        if (isset($_POST['remove_fondo']) && $_POST['remove_fondo'] == '1') {
            $url_fondo = '';
        } else {
            $uploadedFondo = handleUpload('fondo_file');
            if ($uploadedFondo) {
                $url_fondo = $uploadedFondo;
            }
        }

        try {
            // Intento asegurar que la columna existe
            $pdo->exec("ALTER TABLE config ADD COLUMN IF NOT EXISTS tipo_gradiente VARCHAR(30) DEFAULT 'lineal_vertical'");
            $pdo->exec("ALTER TABLE config ADD COLUMN IF NOT EXISTS sombra_boton VARCHAR(20) DEFAULT 'shadow-none'");
            $pdo->exec("ALTER TABLE config ADD COLUMN IF NOT EXISTS forma_imagen VARCHAR(20) DEFAULT 'rounded-lg'");
            $pdo->exec("ALTER TABLE config ADD COLUMN IF NOT EXISTS texto_footer TEXT");
            $pdo->exec("ALTER TABLE config ADD COLUMN IF NOT EXISTS idioma VARCHAR(10) DEFAULT 'es'");
            
            $stmt = $pdo->prepare("UPDATE config SET nombre_sitio = ?, biografia = ?, url_logo = ?, color_fondo = ?, color_fondo_secundario = ?, color_boton = ?, color_boton_texto = ?, color_texto = ?, patron_fondo = ?, fuente_texto = ?, estilo_boton = ?, redondeo_boton = ?, url_fondo = ?, texto_footer = ?, tipo_gradiente = ?, sombra_boton = ?, forma_imagen = ?, idioma = ?");
            if ($stmt->execute([$nombre_sitio, $biografia, $url_logo, $color_fondo, $color_fondo_secundario, $color_boton, $color_boton_texto, $color_texto, $patron_fondo, $fuente_texto, $estilo_boton, $redondeo_boton, $url_fondo, $texto_footer, $tipo_gradiente, $_POST['sombra_boton'] ?? 'shadow-none', $_POST['forma_imagen'] ?? 'rounded-lg', $idioma])) {
                $mensaje = "Diseño y Perfil actualizados correctamente.";
            }
        } catch (Exception $e) {
            $mensaje = "Error al guardar: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_account') {
        $new_usuario = trim($_POST['usuario'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $pass_actual = $_POST['pass_actual'] ?? '';
        $pass_nueva = $_POST['pass_nueva'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin_data = $stmt->fetch();
        
        if ($new_usuario !== '' && $new_usuario !== $admin_data['usuario']) {
            $stmt = $pdo->prepare("UPDATE admin SET usuario = ? WHERE id = ?");
            $stmt->execute([$new_usuario, $_SESSION['admin_id']]);
            $mensaje = "Usuario actualizado. ";
        }

        if ($new_email !== $admin_data['email']) {
            $stmt = $pdo->prepare("UPDATE admin SET email = ? WHERE id = ?");
            $stmt->execute([$new_email, $_SESSION['admin_id']]);
            $mensaje .= "Correo actualizado. ";
        }
        
        if (!empty($pass_actual) && !empty($pass_nueva)) {
            if (password_verify($pass_actual, $admin_data['password_hash'])) {
                if (strlen($pass_nueva) >= 6) {
                    $new_hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admin SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$new_hash, $_SESSION['admin_id']]);
                    $mensaje .= "Contraseña actualizada correctamente.";
                } else {
                    $mensaje .= "La nueva contraseña debe tener al menos 6 caracteres.";
                }
            } else {
                $mensaje .= "La contraseña actual es incorrecta.";
            }
        }
        
        if (empty($mensaje)) {
            $mensaje = "No se realizaron cambios en la cuenta.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_integrations') {
        $config_file = __DIR__ . '/config.php';
        if (file_exists($config_file) && is_writable($config_file)) {
            $content = file_get_contents($config_file);
            $updates = [
                'SMTP_HOST' => $_POST['smtp_host'] ?? '',
                'SMTP_PORT' => $_POST['smtp_port'] ?? '',
                'SMTP_USER' => $_POST['smtp_user'] ?? '',
                'SMTP_PASS' => $_POST['smtp_pass'] ?? '',
                'GOOGLE_CLIENT_ID' => $_POST['google_client_id'] ?? '',
                'GOOGLE_CLIENT_SECRET' => $_POST['google_client_secret'] ?? '',
            ];
            foreach ($updates as $key => $val) {
                if (preg_match("/define\s*\(\s*['\"]{$key}['\"]\s*,\s*.*?\)\s*;/i", $content)) {
                    $content = preg_replace("/define\s*\(\s*['\"]{$key}['\"]\s*,\s*.*?\)\s*;/i", "define('$key', " . var_export($val, true) . ");", $content);
                } else {
                    if (strpos($content, '?>') !== false) {
                        $content = str_replace('?>', "define('$key', " . var_export($val, true) . ");\n?>", $content);
                    } else {
                        $content .= "\ndefine('$key', " . var_export($val, true) . ");\n";
                    }
                }
            }
            file_put_contents($config_file, $content);
            header("Location: settings?msg=integrations_updated");
            exit;
        } else {
            $mensaje = "Error: config.php no tiene permisos de escritura.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_seo') {
        $seo_titulo = $_POST['seo_titulo'] ?? '';
        $seo_descripcion = $_POST['seo_descripcion'] ?? '';
        
        $seo_imagen = $_POST['current_seo_imagen'] ?? '';
        $uploadedSeoImg = handleUpload('seo_imagen_file');
        if ($uploadedSeoImg) {
            $seo_imagen = $uploadedSeoImg;
        }

        $analytics_id = $_POST['analytics_id'] ?? '';
        $facebook_pixel = $_POST['facebook_pixel'] ?? '';

        try {
            $stmt = $pdo->prepare("UPDATE config SET seo_titulo = ?, seo_descripcion = ?, seo_imagen = ?, analytics_id = ?, facebook_pixel = ?");
            if ($stmt->execute([$seo_titulo, $seo_descripcion, $seo_imagen, $analytics_id, $facebook_pixel])) {
                $mensaje = "SEO y Analíticas actualizados correctamente.";
            }
        } catch (Exception $e) {
            $mensaje = "Error al guardar SEO: " . $e->getMessage();
        }
    }
}

$stmtConfig = $pdo->query("SELECT * FROM config LIMIT 1");
$config = $stmtConfig->fetch();
if (!$config) {
    $config = [
        'nombre_sitio' => '', 'biografia' => '', 'url_logo' => '',
        'color_fondo' => '#4c1d95', 'color_fondo_secundario' => '', 'angulo_gradiente' => 180,
        'color_texto' => '#f3f4f6', 'color_boton' => '#4ade80', 'color_boton_texto' => '#111827', 'patron_fondo' => 'ninguno',
        'fuente_texto' => 'Inter', 'estilo_boton' => 'solid', 'redondeo_boton' => 'rounded-xl', 'url_fondo' => '', 'texto_footer' => '', 'tipo_gradiente' => 'lineal'
    ];
}

$stmt = $pdo->prepare("SELECT usuario, email, google_id FROM admin WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin_info = $stmt->fetch();
$current_usuario = $admin_info['usuario'] ?? '';
$current_email = $admin_info['email'] ?? '';
$is_google_linked = !empty($admin_info['google_id']);

if (isset($_GET['msg']) && $_GET['msg'] == 'google_linked') {
    $mensaje = "Cuenta de Google vinculada exitosamente.";
} elseif (isset($_GET['msg']) && $_GET['msg'] == 'google_unlinked') {
    $mensaje = "Cuenta de Google desvinculada.";
} elseif (isset($_GET['msg']) && $_GET['msg'] == 'integrations_updated') {
    $mensaje = "Integraciones (SMTP/Google) actualizadas correctamente.";
}

if (isset($_GET['action']) && $_GET['action'] == 'unlink_google') {
    $stmt = $pdo->prepare("UPDATE admin SET google_id = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    header('Location: settings?msg=google_unlinked');
    exit;
}

$fuentes_disponibles = ['Inter', 'Roboto', 'Montserrat', 'Poppins', 'Playfair Display', 'Oswald', 'Lora', 'Quicksand'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Diseño</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .visual-radio:checked + label { border-color: #111827; border-width: 2px; }
        .font-Inter { font-family: 'Inter', sans-serif; }
        .font-Roboto { font-family: 'Roboto', sans-serif; }
        .font-Montserrat { font-family: 'Montserrat', sans-serif; }
        .font-Poppins { font-family: 'Poppins', sans-serif; }
        .font-Playfair { font-family: 'Playfair Display', serif; }
    </style>
<link rel="icon" href="<?= htmlspecialchars($config['url_logo'] ?? 'favicon.ico') ?>">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: {} }
        }
        // Dark mode initialization
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }

        function openMobilePreview() {
            document.getElementById('mobilePreviewModal').classList.remove('hidden');
        }
        function closeMobilePreview() {
            document.getElementById('mobilePreviewModal').classList.add('hidden');
        }
        function reloadPreview() {
            const mIframe = document.getElementById('mobilePreviewIframe');
            if (mIframe) mIframe.src = mIframe.src;
        }
    </script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 dark:bg-gray-900 text-gray-800 dark:text-gray-100 dark:text-gray-100 font-sans h-screen flex flex-col overflow-hidden transition-colors duration-300">

    

    <div class="flex-1 flex overflow-hidden w-full relative">
        <!-- Panel Izquierdo (Sidebar Escritorio) -->
        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 dark:border-gray-700 transition-colors duration-300 z-10">
            <div class="p-6 pb-2">
                <h1 class="font-bold text-2xl text-gray-800 dark:text-gray-100 dark:text-white flex items-center gap-2">
                    <i class="fas fa-link text-purple-600 dark:text-purple-400"></i> Admin
                </h1>
            </div>
            
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="admin" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors font-bold text-gray-600 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-list"></i> <?= __('nav_links') ?>
                </a>
                <a href="settings" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                    <i class="fas fa-paint-roller"></i> <?= __('nav_design') ?>
                </a>
                <a href="index" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors font-bold">
                    <i class="fas fa-external-link-alt"></i> <?= __('nav_view_site') ?>
                </a>
                <a href="?check_update=1" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors font-bold mt-4">
                    <i class="fas fa-sync-alt"></i> Actualizaciones
                </a>
            </nav>
            
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 space-y-2">
                <button onclick="toggleDarkMode()" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors font-bold text-left">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:inline text-yellow-400"></i> 
                    <span class="dark:hidden">Modo Oscuro</span>
                    <span class="hidden dark:inline">Modo Claro</span>
                </button>
                <a href="logout" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors font-bold">
                    <i class="fas fa-sign-out-alt"></i> <?= __('nav_logout') ?>
                </a>
            </div>
        </aside>

        <!-- Panel Central (Edición) -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6 pb-24 lg:pb-6 bg-gray-50 dark:bg-gray-900 dark:bg-gray-900 transition-colors duration-300 relative">

            <?php if ($mensaje): ?>
                <div class="bg-black text-white p-3 rounded-xl mb-6 text-center font-bold shadow-lg animate-pulse" id="msgAlert">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
                <script>setTimeout(()=>document.getElementById('msgAlert').style.display='none', 3000);</script>
            <?php endif; ?>

            <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 max-w-3xl mx-auto">
                <form action="settings" method="POST" enctype="multipart/form-data" class="space-y-10">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="update_config">
                    
                    <!-- PERFIL -->
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-gray-100 text-xl mb-4 border-b pb-2"><i class="fas fa-user-circle text-purple-500 mr-2"></i> Perfil</h3>
                        <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                            <div class="flex flex-col items-center gap-2 shrink-0">
                                <input type="hidden" name="current_logo" value="<?= htmlspecialchars($config['url_logo'] ?? '') ?>">
                                <div class="w-28 h-28 rounded-full bg-gray-200 overflow-hidden border-4 border-white shadow-lg relative group cursor-pointer" onclick="document.getElementById('avatar-file-input').click()">
                                    <?php if (!empty($config['url_logo'])): ?>
                                        <img src="<?= htmlspecialchars($config['url_logo']) ?>" id="avatar-img-preview" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <img src="" id="avatar-img-preview" class="hidden w-full h-full object-cover">
                                        <div class="w-full h-full flex items-center justify-center text-gray-400"><i class="fas fa-user text-4xl"></i></div>
                                    <?php endif; ?>
                                    <div class="absolute inset-0 bg-black/50 hidden group-hover:flex items-center justify-center text-white text-sm font-bold text-center p-2">Cambiar Foto</div>
                                </div>
                                <input type="file" id="avatar-file-input" accept="image/*" class="hidden" onchange="openCropperModal(this.files[0], 0, 'avatar')">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Avatar / Favicon</span>
                            </div>
                            
                            <div class="flex-1 w-full space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Título del Perfil</label>
                                    <input type="text" name="nombre_sitio" value="<?= htmlspecialchars($config['nombre_sitio']) ?>" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border focus:ring-purple-500 bg-gray-50 dark:bg-gray-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Biografía</label>
                                    <textarea name="biografia" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border focus:ring-purple-500 bg-gray-50 dark:bg-gray-900"><?= htmlspecialchars($config['biografia']) ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Texto del Footer (Copyright)</label>
                                    <textarea name="texto_footer" rows="2" placeholder="Ej: © 2026 Todos los derechos reservados" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border focus:ring-purple-500 bg-gray-50 dark:bg-gray-900"><?= htmlspecialchars($config['texto_footer'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FUENTES -->
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-gray-100 text-xl mb-4 border-b pb-2"><i class="fas fa-font text-purple-500 mr-2"></i> Tipografía</h3>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Fuente Global del Sitio</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <?php foreach($fuentes_disponibles as $fuente): ?>
                                <div>
                                    <input type="radio" name="fuente_texto" value="<?= $fuente ?>" id="f_<?= $fuente ?>" class="hidden visual-radio" <?= ($config['fuente_texto'] == $fuente) ? 'checked' : '' ?>>
                                    <label for="f_<?= $fuente ?>" class="block border border-gray-200 dark:border-gray-700 rounded-xl p-3 text-center cursor-pointer hover:bg-gray-50 dark:bg-gray-900 transition bg-white dark:bg-gray-800 text-lg">
                                        <span style="font-family: '<?= $fuente ?>', sans-serif;"><?= $fuente ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Las fuentes se cargarán automáticamente desde Google Fonts.</p>
                    </div>

                    <!-- BOTONES -->
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-gray-100 text-xl mb-4 border-b pb-2"><i class="fas fa-layer-group text-purple-500 mr-2"></i> Diseño de Botones</h3>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Estilo del Botón</label>
                            <div class="grid grid-cols-3 gap-4">
                                <!-- Solid -->
                                <div>
                                    <input type="radio" name="estilo_boton" value="solid" id="btn_solid" class="hidden visual-radio" <?= ($config['estilo_boton'] == 'solid') ? 'checked' : '' ?>>
                                    <label for="btn_solid" class="block border border-gray-200 dark:border-gray-700 rounded-xl p-4 cursor-pointer hover:bg-gray-50 dark:bg-gray-900 transition bg-white dark:bg-gray-800 flex flex-col items-center">
                                        <div class="w-24 h-8 bg-gray-300 rounded mb-2"></div>
                                        <span class="text-sm font-bold text-gray-600 dark:text-gray-400">Sólido</span>
                                    </label>
                                </div>
                                <!-- Glass -->
                                <div>
                                    <input type="radio" name="estilo_boton" value="glass" id="btn_glass" class="hidden visual-radio" <?= ($config['estilo_boton'] == 'glass') ? 'checked' : '' ?>>
                                    <label for="btn_glass" class="block border border-gray-200 dark:border-gray-700 rounded-xl p-4 cursor-pointer hover:bg-gray-50 dark:bg-gray-900 transition bg-gray-50 dark:bg-gray-900 flex flex-col items-center relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-br from-purple-100 to-blue-100 opacity-50 pointer-events-none"></div>
                                        <div class="w-24 h-8 bg-white/40 backdrop-blur-sm border border-white/50 rounded mb-2 z-10 shadow-sm"></div>
                                        <span class="text-sm font-bold text-gray-600 dark:text-gray-400 z-10">Glass</span>
                                    </label>
                                </div>
                                <!-- Outline -->
                                <div>
                                    <input type="radio" name="estilo_boton" value="outline" id="btn_outline" class="hidden visual-radio" <?= ($config['estilo_boton'] == 'outline') ? 'checked' : '' ?>>
                                    <label for="btn_outline" class="block border border-gray-200 dark:border-gray-700 rounded-xl p-4 cursor-pointer hover:bg-gray-50 dark:bg-gray-900 transition bg-white dark:bg-gray-800 flex flex-col items-center">
                                        <div class="w-24 h-8 border-2 border-gray-400 bg-transparent rounded mb-2"></div>
                                        <span class="text-sm font-bold text-gray-600 dark:text-gray-400">Outline</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Redondez de las Esquinas</label>
                            <div class="grid grid-cols-4 gap-3">
                                <div>
                                    <input type="radio" name="redondeo_boton" value="rounded-none" id="rad_square" class="hidden visual-radio" <?= ($config['redondeo_boton'] == 'rounded-none') ? 'checked' : '' ?>>
                                    <label for="rad_square" class="block border border-gray-200 dark:border-gray-700 rounded-xl p-3 cursor-pointer hover:bg-gray-50 dark:bg-gray-900 transition bg-white dark:bg-gray-800 flex flex-col items-center">
                                        <div class="w-16 h-8 border-t-2 border-l-2 border-gray-800 rounded-none mb-1"></div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Cuadrado</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" name="redondeo_boton" value="rounded-md" id="rad_round" class="hidden visual-radio" <?= ($config['redondeo_boton'] == 'rounded-md') ? 'checked' : '' ?>>
                                    <label for="rad_round" class="block border border-gray-200 dark:border-gray-700 rounded-xl p-3 cursor-pointer hover:bg-gray-50 dark:bg-gray-900 transition bg-white dark:bg-gray-800 flex flex-col items-center">
                                        <div class="w-16 h-8 border-t-2 border-l-2 border-gray-800 rounded-tl-md mb-1"></div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Redondo</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" name="redondeo_boton" value="rounded-2xl" id="rad_rounder" class="hidden visual-radio" <?= ($config['redondeo_boton'] == 'rounded-2xl') ? 'checked' : '' ?>>
                                    <label for="rad_rounder" class="block border border-gray-200 dark:border-gray-700 rounded-xl p-3 cursor-pointer hover:bg-gray-50 dark:bg-gray-900 transition bg-white dark:bg-gray-800 flex flex-col items-center">
                                        <div class="w-16 h-8 border-t-2 border-l-2 border-gray-800 rounded-tl-2xl mb-1"></div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Muy Redondo</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" name="redondeo_boton" value="rounded-full" id="rad_full" class="hidden visual-radio" <?= ($config['redondeo_boton'] == 'rounded-full') ? 'checked' : '' ?>>
                                    <label for="rad_full" class="block border border-gray-200 dark:border-gray-700 rounded-xl p-3 cursor-pointer hover:bg-gray-50 dark:bg-gray-900 transition bg-white dark:bg-gray-800 flex flex-col items-center">
                                        <div class="w-16 h-8 border-t-2 border-l-2 border-gray-800 rounded-tl-full mb-1"></div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Píldora</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Sombra de Botones</label>
                                <select name="sombra_boton" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-white dark:bg-gray-800 focus:ring-purple-500 border font-bold text-sm">
                                    <option value="shadow-none" <?= ($config['sombra_boton'] ?? 'shadow-none') == 'shadow-none' ? 'selected' : '' ?>>Sin Sombra</option>
                                    <option value="shadow-md" <?= ($config['sombra_boton'] ?? 'shadow-none') == 'shadow-md' ? 'selected' : '' ?>>Sombra Suave</option>
                                    <option value="shadow-lg" <?= ($config['sombra_boton'] ?? 'shadow-none') == 'shadow-lg' ? 'selected' : '' ?>>Sombra Media</option>
                                    <option value="shadow-2xl" <?= ($config['sombra_boton'] ?? 'shadow-none') == 'shadow-2xl' ? 'selected' : '' ?>>Sombra Fuerte</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Forma de Imágenes (Miniaturas)</label>
                                <select name="forma_imagen" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-white dark:bg-gray-800 focus:ring-purple-500 border font-bold text-sm">
                                    <option value="rounded-none" <?= ($config['forma_imagen'] ?? 'rounded-lg') == 'rounded-none' ? 'selected' : '' ?>>Cuadrada</option>
                                    <option value="rounded-lg" <?= ($config['forma_imagen'] ?? 'rounded-lg') == 'rounded-lg' ? 'selected' : '' ?>>Redondeada</option>
                                    <option value="rounded-full" <?= ($config['forma_imagen'] ?? 'rounded-lg') == 'rounded-full' ? 'selected' : '' ?>>Circular</option>
                                </select>
                            </div>
                            
                        </div>
                    </div>

                    <!-- FONDOS Y COLORES -->
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-gray-100 text-xl mb-4 border-b pb-2"><i class="fas fa-palette text-purple-500 mr-2"></i> Fondo y Colores</h3>
                        
                        <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-2xl bg-gray-50 dark:bg-gray-900">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Imagen de Fondo (Opcional)</label>
                            <input type="hidden" name="current_fondo" value="<?= htmlspecialchars($config['url_fondo'] ?? '') ?>">
                            
                            <?php if (!empty($config['url_fondo'])): ?>
                                <div class="relative w-full h-32 rounded-xl overflow-hidden mb-3 border border-gray-300 dark:border-gray-600">
                                    <img src="<?= htmlspecialchars($config['url_fondo']) ?>" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                        <label class="flex items-center space-x-2 text-white font-bold cursor-pointer">
                                            <input type="checkbox" name="remove_fondo" value="1" class="w-5 h-5">
                                            <span>Quitar esta imagen al guardar</span>
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <label class="flex flex-col items-center justify-center w-full h-16 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer bg-white hover:bg-gray-50 dark:bg-gray-900 transition">
                                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 font-bold"><i class="fas fa-upload"></i> Subir Nueva Imagen de Fondo</div>
                                <input type="file" name="fondo_file" accept="image/*" class="hidden">
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">Si subes una imagen, reemplazará a los colores de fondo (Gradientes).</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-900 p-6 rounded-2xl border border-gray-100 mb-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Color de Fondo 1 (Principal)</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="color_fondo" value="<?= htmlspecialchars($config['color_fondo']) ?>" class="h-12 w-20 rounded cursor-pointer border-0 p-0 shadow-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Color de Fondo 2 (Opcional)</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="color_fondo_secundario" value="<?= htmlspecialchars($config['color_fondo_secundario']) ?>" class="h-12 w-20 rounded cursor-pointer border-0 p-0 shadow-sm">
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Tipo de Gradiente</label>
                                <select name="tipo_gradiente" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-white dark:bg-gray-800 focus:ring-purple-500 border font-bold text-sm">
                                    <option value="lineal_vertical" <?= ($config['tipo_gradiente'] ?? 'lineal_vertical') == 'lineal_vertical' ? 'selected' : '' ?>>Lineal Vertical</option>
                                    <option value="lineal_horizontal" <?= ($config['tipo_gradiente'] ?? 'lineal_vertical') == 'lineal_horizontal' ? 'selected' : '' ?>>Lineal Horizontal</option>
                                    <option value="lineal_diagonal" <?= ($config['tipo_gradiente'] ?? 'lineal_vertical') == 'lineal_diagonal' ? 'selected' : '' ?>>Lineal Diagonal</option>
                                    <option value="radial" <?= ($config['tipo_gradiente'] ?? 'lineal_vertical') == 'radial' ? 'selected' : '' ?>>Radial (Circular)</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Idioma del Sistema</label>
                                <select name="idioma" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-white dark:bg-gray-800 focus:ring-purple-500 border font-bold text-sm">
                                    <option value="es" <?= ($config['idioma'] ?? 'es') == 'es' ? 'selected' : '' ?>>Español (Base)</option>
                                    <option value="en" <?= ($config['idioma'] ?? 'es') == 'en' ? 'selected' : '' ?>>Inglés</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-2 mb-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Color de Botones</label>
                                <input type="color" name="color_boton" value="<?= htmlspecialchars($config['color_boton']) ?>" class="h-12 w-full rounded cursor-pointer border-0 p-0 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Texto de Botones</label>
                                <input type="color" name="color_boton_texto" value="<?= htmlspecialchars($config['color_boton_texto']) ?>" class="h-12 w-full rounded cursor-pointer border-0 p-0 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Textos Generales</label>
                                <input type="color" name="color_texto" value="<?= htmlspecialchars($config['color_texto']) ?>" class="h-12 w-full rounded cursor-pointer border-0 p-0 shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Patrón Decorativo (Textura)</label>
                            <select name="patron_fondo" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-white dark:bg-gray-800 focus:ring-purple-500 border font-bold text-sm">
                                <option value="ninguno" <?= $config['patron_fondo'] == 'ninguno' ? 'selected' : '' ?>>Ninguno (Liso)</option>
                                <option value="puntos" <?= $config['patron_fondo'] == 'puntos' ? 'selected' : '' ?>>Puntos (Polka)</option>
                                <option value="cuadricula" <?= $config['patron_fondo'] == 'cuadricula' ? 'selected' : '' ?>>Cuadrícula Mágica</option>
                                <option value="topografia" <?= $config['patron_fondo'] == 'topografia' ? 'selected' : '' ?>>Topografía Circular</option>
                                <option value="rayas_diagonales" <?= $config['patron_fondo'] == 'rayas_diagonales' ? 'selected' : '' ?>>Rayas Diagonales</option>
                                <option value="rayas_horizontales" <?= ($config['patron_fondo'] ?? '') == 'rayas_horizontales' ? 'selected' : '' ?>>Rayas Horizontales</option>
                                <option value="rayas_verticales" <?= ($config['patron_fondo'] ?? '') == 'rayas_verticales' ? 'selected' : '' ?>>Rayas Verticales</option>
                                <option value="zigzag" <?= ($config['patron_fondo'] ?? '') == 'zigzag' ? 'selected' : '' ?>>Zigzag</option>
                                <option value="cuadros" <?= ($config['patron_fondo'] ?? '') == 'cuadros' ? 'selected' : '' ?>>Cuadros Ajedrez</option>
                                <option value="cruz" <?= $config['patron_fondo'] == 'cruz' ? 'selected' : '' ?>>Cruces</option>
                                <option value="papel_cuadriculado" <?= $config['patron_fondo'] == 'papel_cuadriculado' ? 'selected' : '' ?>>Papel Fino</option>
                                <option value="ondas" <?= $config['patron_fondo'] == 'ondas' ? 'selected' : '' ?>>Ondas Suaves</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="pt-8 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="w-full bg-purple-600 text-white font-bold py-4 rounded-full hover:bg-purple-700 transition shadow-lg text-lg">Guardar Configuración</button>
                    </div>
                </form>
            </div>

            <!-- SECCIÓN: CUENTA Y SEGURIDAD -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 max-w-3xl mx-auto mt-8">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-xl">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Cuenta y Seguridad</h2>
                </div>

                <form action="settings" method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="update_account">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nombre de Usuario (Para iniciar sesión)</label>
                            <input type="text" name="usuario" value="<?= htmlspecialchars($current_usuario) ?>" placeholder="tu_usuario" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white dark:bg-gray-800 focus:ring-purple-500 border transition">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Correo Electrónico (Para recuperar contraseña o iniciar sesión)</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($current_email) ?>" placeholder="ejemplo@correo.com" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white dark:bg-gray-800 focus:ring-purple-500 border transition">
                        </div>
                        
                        <div class="md:col-span-2 pt-4 border-t border-gray-100">
                            <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-4">Cambiar Contraseña</h3>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Contraseña Actual</label>
                            <input type="password" name="pass_actual" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white dark:bg-gray-800 focus:ring-purple-500 border transition">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Requerida solo si deseas cambiarla</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nueva Contraseña</label>
                            <input type="password" name="pass_nueva" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white dark:bg-gray-800 focus:ring-purple-500 border transition">
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-gray-800 text-white font-bold py-3 rounded-xl hover:bg-black transition shadow-md">Actualizar Cuenta</button>
                    </div>
                </form>
            </div>

            <!-- SECCIÓN: INTEGRACIONES -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 max-w-3xl mx-auto mt-8 mb-8">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl">
                        <i class="fas fa-plug"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Integraciones</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configura SMTP para recuperación de contraseñas y Google Auth para iniciar sesión.</p>
                    </div>
                </div>

                <form action="settings" method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="update_integrations">

                    <!-- SMTP -->
                    <div class="border-b border-gray-100 pb-6">
                        <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"><i class="fas fa-envelope text-gray-400"></i> Servidor SMTP</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Host SMTP</label>
                                <input type="text" name="smtp_host" value="<?= defined('SMTP_HOST') ? htmlspecialchars(SMTP_HOST) : '' ?>" placeholder="smtp.gmail.com" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-blue-500 border transition">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Puerto SMTP</label>
                                <input type="text" name="smtp_port" value="<?= defined('SMTP_PORT') ? htmlspecialchars(SMTP_PORT) : '' ?>" placeholder="587 o 465" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-blue-500 border transition">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Usuario SMTP</label>
                                <input type="text" name="smtp_user" value="<?= defined('SMTP_USER') ? htmlspecialchars(SMTP_USER) : '' ?>" placeholder="tu_correo@gmail.com" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-blue-500 border transition">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Contraseña SMTP</label>
                                <input type="password" name="smtp_pass" value="<?= defined('SMTP_PASS') ? htmlspecialchars(SMTP_PASS) : '' ?>" placeholder="Contraseña de aplicación" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-blue-500 border transition">
                            </div>
                        </div>
                    </div>

                    <!-- Google Auth -->
                    <div class="pt-2">
                        <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"><img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-4 h-4"> Google Authentication</h3>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Client ID</label>
                                <input type="text" name="google_client_id" value="<?= defined('GOOGLE_CLIENT_ID') ? htmlspecialchars(GOOGLE_CLIENT_ID) : '' ?>" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-blue-500 border transition">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Client Secret</label>
                                <input type="password" name="google_client_secret" value="<?= defined('GOOGLE_CLIENT_SECRET') ? htmlspecialchars(GOOGLE_CLIENT_SECRET) : '' ?>" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-blue-500 border transition">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition shadow-md">Guardar Integraciones</button>
                    </div>
                </form>
            </div>

            <!-- SECCIÓN: SEO Y ANALÍTICAS -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 max-w-3xl mx-auto mt-8 mb-12">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-xl">
                        <i class="fas fa-search"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">SEO & Analíticas</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configura cómo se ve tu sitio al compartirlo (Open Graph) y tus herramientas de medición.</p>
                    </div>
                </div>

                <form action="settings" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="update_seo">

                    <!-- SEO Open Graph -->
                    <div class="border-b border-gray-100 pb-6">
                        <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"><i class="fas fa-share-alt text-gray-400"></i> Open Graph (Redes Sociales)</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Título SEO</label>
                                <input type="text" name="seo_titulo" value="<?= htmlspecialchars($config['seo_titulo'] ?? '') ?>" placeholder="Mi increíble Bio Links" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-green-500 border transition">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Descripción SEO</label>
                                <textarea name="seo_descripcion" rows="2" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-green-500 border transition" placeholder="Una breve descripción para los buscadores..."><?= htmlspecialchars($config['seo_descripcion'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Imagen al Compartir (OG Image)</label>
                                <input type="hidden" name="current_seo_imagen" value="<?= htmlspecialchars($config['seo_imagen'] ?? '') ?>">
                                <?php if (!empty($config['seo_imagen'])): ?>
                                    <div class="mb-3">
                                        <img src="<?= htmlspecialchars($config['seo_imagen']) ?>" class="h-32 rounded-xl object-cover border border-gray-200 dark:border-gray-700">
                                    </div>
                                <?php endif; ?>
                                <label class="flex flex-col items-center justify-center w-full h-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 transition">
                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><i class="fas fa-upload"></i> Subir Nueva Imagen OG</div>
                                    <input type="file" name="seo_imagen_file" accept="image/*" class="hidden">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics -->
                    <div class="pt-2">
                        <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"><i class="fas fa-chart-line text-gray-400"></i> Seguimiento</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Google Analytics ID</label>
                                <input type="text" name="analytics_id" value="<?= htmlspecialchars($config['analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-green-500 border transition">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Facebook Pixel ID</label>
                                <input type="text" name="facebook_pixel" value="<?= htmlspecialchars($config['facebook_pixel'] ?? '') ?>" placeholder="123456789012345" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900 focus:bg-white focus:ring-green-500 border transition">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-700 transition shadow-md">Guardar SEO y Analíticas</button>
                    </div>
                </form>
            </div>
        </main>

        <!-- Panel Derecho (Vista Previa Móvil) -->
        <aside class="hidden lg:flex w-[400px] xl:w-[450px] border-l border-gray-200 dark:border-gray-700 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 dark:bg-gray-800 items-center justify-center p-4 transition-colors duration-300 z-10">
            <div class="relative w-[320px] h-[650px] bg-black rounded-[40px] border-[8px] border-black shadow-2xl overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-6 bg-black z-50 rounded-b-xl w-32 mx-auto"></div>
                <iframe src="index" id="previewIframe" class="w-full h-full bg-white border-0" title="Live Preview"></iframe>
            </div>
        </aside>
    </div>
    
    <script>
        const form = document.querySelector('form');
        form.addEventListener('input', updatePreview);
        form.addEventListener('change', updatePreview);

        function updatePreview() {
            const iframe = document.getElementById('previewIframe');
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            if (!doc || !doc.head) return;
            
            let styleTag = doc.getElementById('livePreviewStyles');
            if (!styleTag) {
                styleTag = doc.createElement('style');
                styleTag.id = 'livePreviewStyles';
                doc.head.appendChild(styleTag);
            }
            
            const formData = new FormData(form);
            
            const font = formData.get('fuente_texto');
            const bgColor = formData.get('color_fondo');
            const bgSec = formData.get('color_fondo_secundario');
            const angle = formData.get('angulo_gradiente');
            const textColor = formData.get('color_texto');
            const gradType = formData.get('tipo_gradiente');
            
            const btnColor = formData.get('color_boton');
            const btnText = formData.get('color_boton_texto');
            const btnStyle = formData.get('estilo_boton');
            const btnRadius = formData.get('redondeo_boton');
            const btnShadow = formData.get('sombra_boton') || 'shadow-none';
            const imgShape = formData.get('forma_imagen') || 'rounded-lg';
            
            let bgRule = `background-color: ${bgColor} !important;`;
            if (bgSec) {
                if (gradType === 'radial') {
                    bgRule = `background: radial-gradient(circle, ${bgColor}, ${bgSec}) !important; min-height: 100vh;`;
                } else if (gradType === 'lineal_horizontal') {
                    bgRule = `background: linear-gradient(90deg, ${bgColor}, ${bgSec}) !important; min-height: 100vh;`;
                } else if (gradType === 'lineal_diagonal') {
                    bgRule = `background: linear-gradient(135deg, ${bgColor}, ${bgSec}) !important; min-height: 100vh;`;
                } else {
                    bgRule = `background: linear-gradient(180deg, ${bgColor}, ${bgSec}) !important; min-height: 100vh;`;
                }
            }
            
            let btnRule = "";
            if (btnStyle === 'solid') {
                btnRule = `background-color: ${btnColor} !important; color: ${btnText} !important; border: 2px solid ${btnColor} !important;`;
            } else if (btnStyle === 'outline') {
                btnRule = `background-color: transparent !important; border: 2px solid ${btnColor} !important; color: ${btnColor} !important;`;
            } else if (btnStyle === 'glass') {
                btnRule = `background-color: ${btnColor}30 !important; backdrop-filter: blur(16px) !important; border: 1px solid rgba(255,255,255,0.4) !important; color: ${btnText} !important; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1) !important;`;
            }
            
            const radiusMap = {
                'rounded-none': '0px',
                'rounded-md': '0.375rem',
                'rounded-2xl': '1rem',
                'rounded-full': '9999px'
            };
            const r = radiusMap[btnRadius] || '1rem';
            
            // Map shadow classes to actual CSS box-shadow
            let shadowCss = 'none';
            if (btnShadow === 'shadow-md') shadowCss = '0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06)';
            if (btnShadow === 'shadow-lg') shadowCss = '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05)';
            if (btnShadow === 'shadow-2xl') shadowCss = '0 25px 50px -12px rgba(0,0,0,0.25)';
            
            // Map image shape
            let imgRadius = '0.5rem';
            if (imgShape === 'rounded-full') imgRadius = '9999px';
            if (imgShape === 'rounded-none') imgRadius = '0px';
            
            const footerText = formData.get('texto_footer') || '';
            const footerHtmlText = footerText.replace(/\n/g, '<br>');
            let footerHtml = '';
            if (footerText) {
                footerHtml = `<footer style="margin-top: 2rem; padding-bottom: 2rem; text-align: center; opacity: 0.7; font-size: 0.8rem; font-weight: 600;">${footerHtmlText}</footer>`;
            }
            
            let footerEl = doc.getElementById('livePreviewFooter');
            if (footerEl) {
                footerEl.innerHTML = footerHtmlText;
            } else if (footerText) {
                const f = doc.createElement('footer');
                f.id = 'livePreviewFooter';
                f.style.cssText = "margin-top: 2rem; padding-bottom: 2rem; text-align: center; opacity: 0.7; font-size: 0.8rem; font-weight: 600;";
                f.innerHTML = footerHtmlText;
                doc.body.appendChild(f);
            }
            
            const patType = formData.get('patron_fondo');
            let patRule = "";
            if (patType === 'puntos') {
                patRule = "background-image: radial-gradient(rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 20px 20px;";
            } else if (patType === 'cuadricula') {
                patRule = "background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 30px 30px;";
            } else if (patType === 'topografia') {
                patRule = "background-image: repeating-radial-gradient(circle at 0 0, transparent 0, rgba(255,255,255,0.03) 10px, transparent 11px, transparent 20px);";
            } else if (patType === 'rayas_diagonales') {
                patRule = "background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.05) 10px, rgba(255,255,255,0.05) 20px);";
            } else if (patType === 'rayas_horizontales') {
                patRule = "background-image: repeating-linear-gradient(0deg, transparent, transparent 10px, rgba(255,255,255,0.05) 10px, rgba(255,255,255,0.05) 20px);";
            } else if (patType === 'rayas_verticales') {
                patRule = "background-image: repeating-linear-gradient(90deg, transparent, transparent 10px, rgba(255,255,255,0.05) 10px, rgba(255,255,255,0.05) 20px);";
            } else if (patType === 'zigzag') {
                patRule = "background-image: linear-gradient(135deg, rgba(255,255,255,0.05) 25%, transparent 25%), linear-gradient(225deg, rgba(255,255,255,0.05) 25%, transparent 25%), linear-gradient(45deg, rgba(255,255,255,0.05) 25%, transparent 25%), linear-gradient(315deg, rgba(255,255,255,0.05) 25%, transparent 25%); background-position: 15px 0, 15px 0, 0 0, 0 0; background-size: 30px 30px;";
            } else if (patType === 'cuadros') {
                patRule = "background-image: conic-gradient(rgba(255,255,255,0.05) 90deg, transparent 90deg 180deg, rgba(255,255,255,0.05) 180deg 270deg, transparent 270deg); background-size: 40px 40px;";
            } else if (patType === 'cruz') {
                patRule = "background-image: linear-gradient(90deg, transparent 22px, rgba(255,255,255,0.1) 22px, rgba(255,255,255,0.1) 28px, transparent 28px), linear-gradient(0deg, transparent 22px, rgba(255,255,255,0.1) 22px, rgba(255,255,255,0.1) 28px, transparent 28px); background-size: 50px 50px;";
            } else if (patType === 'papel_cuadriculado') {
                patRule = "background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 15px 15px;";
            } else if (patType === 'ondas') {
                patRule = "background-image: radial-gradient(circle at 100% 50%, transparent 20%, rgba(255,255,255,0.05) 21%, rgba(255,255,255,0.05) 34%, transparent 35%, transparent), radial-gradient(circle at 0% 50%, transparent 20%, rgba(255,255,255,0.05) 21%, rgba(255,255,255,0.05) 34%, transparent 35%, transparent); background-size: 40px 40px;";
            }

            const css = `
                @import url('https://fonts.googleapis.com/css2?family=${font.replace(/ /g, '+')}:wght@400;600;700&display=swap');
                body { font-family: '${font}', sans-serif !important; color: ${textColor} !important; ${bgRule} }
                .link-card { ${btnRule} border-radius: ${r} !important; box-shadow: ${shadowCss} !important; }
                .link-card img { border-radius: ${imgRadius} !important; }
                .pattern-overlay { ${patRule} }
            `;
            
            styleTag.innerHTML = css;
        }
    </script>

    <!-- Mobile Bottom Nav -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 flex justify-around p-2 z-40 transition-colors duration-300 pb-safe">
        <a href="admin" class="flex flex-col items-center p-2 text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-purple-600">
            <i class="fas fa-list text-xl mb-1"></i>
            <span class="text-[10px] font-bold">Links</span>
        </a>
        <a href="settings" class="flex flex-col items-center p-2 text-purple-600 dark:text-purple-400">
            <i class="fas fa-paint-roller text-xl mb-1"></i>
            <span class="text-[10px] font-bold">Diseño</span>
        </a>
        <button onclick="toggleDarkMode()" class="flex flex-col items-center p-2 text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-purple-600">
            <i class="fas fa-moon dark:hidden text-xl mb-1"></i>
            <i class="fas fa-sun hidden dark:inline text-yellow-400 text-xl mb-1"></i>
            <span class="text-[10px] font-bold dark:hidden">Oscuro</span>
            <span class="text-[10px] font-bold hidden dark:inline">Claro</span>
        </button>
        <button onclick="openMobilePreview()" class="flex flex-col items-center p-2 text-blue-500 hover:text-blue-700">
            <i class="fas fa-mobile-alt text-xl mb-1"></i>
            <span class="text-[10px] font-bold">Preview</span>
        </button>
    </nav>

    <!-- Mobile Preview Modal -->
    <div id="mobilePreviewModal" class="hidden fixed inset-0 z-[60] bg-gray-900 flex flex-col transition-colors duration-300">
        <div class="flex justify-between items-center p-4 text-white bg-gray-900">
            <span class="font-bold flex items-center gap-2"><i class="fas fa-eye text-purple-400"></i> Vista Previa</span>
            <button onclick="closeMobilePreview()" class="text-xl px-2 py-1 bg-white/10 rounded hover:bg-white/20"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-1 bg-black rounded-t-[40px] mt-2 relative overflow-hidden">
            <iframe src="index" id="mobilePreviewIframe" class="absolute inset-0 w-full h-full border-0 bg-white" title="Live Preview"></iframe>
        </div>
    </div>


    <!-- Cropper Modal -->
    <div id="cropperModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-[70] backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl relative flex flex-col max-h-[90vh]">
            <div class="flex-shrink-0 flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Ajustar Imagen</h2>
                <button type="button" onclick="closeCropperModal()" class="text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 text-3xl leading-none">&times;</button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6 flex flex-col items-center">
                <div class="w-full h-64 md:h-96 bg-black relative flex items-center justify-center rounded-xl overflow-hidden mb-6">
                    <img id="cropperImage" src="" class="max-w-full max-h-full">
                </div>
                
                <div class="flex flex-wrap justify-center gap-3 w-full border-b border-gray-100 dark:border-gray-700 pb-6 mb-6">
                    <button type="button" onclick="setCropperRatio(NaN)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center gap-2">
                        <i class="far fa-square"></i> Free
                    </button>
                    <button type="button" onclick="setCropperRatio(16/9)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center gap-2">
                        <i class="far fa-image"></i> Rectangle
                    </button>
                    <button type="button" onclick="setCropperRatio(1)" class="px-4 py-2 bg-purple-100 text-purple-700 font-bold rounded-xl hover:bg-purple-200 transition flex items-center gap-2" id="btnCropSquare">
                        <i class="fas fa-square"></i> Square
                    </button>
                </div>
                
                <input type="hidden" id="cropperTargetId" value="">
                <input type="hidden" id="cropperTargetType" value="link">
                
                <button type="button" onclick="applyCrop()" id="btnApplyCrop" class="w-full bg-purple-600 text-white font-bold py-4 rounded-xl shadow-lg hover:bg-purple-700 transition flex justify-center items-center gap-2">
                    <i class="fas fa-check"></i> Aplicar y Guardar
                </button>
            </div>
        </div>
    </div>

    <script>
        let cropper = null;
        
        function openCropperModal(file, targetId, type = 'link') {
            const reader = new FileReader();
            reader.onload = function(e) {
                const image = document.getElementById('cropperImage');
                image.src = e.target.result;
                
                document.getElementById('cropperTargetId').value = targetId;
                document.getElementById('cropperTargetType').value = type;
                
                document.getElementById('cropperModal').classList.remove('hidden');
                
                if (cropper) cropper.destroy();
                
                cropper = new Cropper(image, {
                    aspectRatio: 1, // Default square
                    viewMode: 2,
                    background: false,
                    autoCropArea: 1
                });
            }
            reader.readAsDataURL(file);
        }
        
        function closeCropperModal() {
            document.getElementById('cropperModal').classList.add('hidden');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            document.getElementById('cropperImage').src = '';
        }
        
        function setCropperRatio(ratio) {
            if (cropper) {
                cropper.setAspectRatio(ratio);
            }
        }
        
        function applyCrop() {
            if (!cropper) return;
            
            const btn = document.getElementById('btnApplyCrop');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            btn.disabled = true;
            
            cropper.getCroppedCanvas({
                width: 400, // Max width to save bandwidth
                height: 400 * (cropper.options.aspectRatio || 1)
            }).toBlob((blob) => {
                const reader = new FileReader();
                reader.readAsDataURL(blob);
                reader.onloadend = function() {
                    const base64data = reader.result;
                    const id = document.getElementById('cropperTargetId').value;
                    const type = document.getElementById('cropperTargetType').value;
                    
                    const formData = new FormData();
                    formData.append('action', 'upload_thumbnail_ajax');
                    formData.append('csrf_token', '<?= $_SESSION["csrf_token"] ?? "" ?>');
                    formData.append('id', id);
                    formData.append('type', type);
                    formData.append('image', base64data);
                    
                    fetch('admin', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update UI instantly
                            if (type === 'link') {
                                const thumbImg = document.getElementById('thumb-img-' + id);
                                if (thumbImg) {
                                    thumbImg.src = data.url;
                                }
                            } else {
                                const avatarImg = document.getElementById('avatar-img-preview');
                                if (avatarImg) {
                                    avatarImg.src = data.url;
                                }
                            }
                            closeCropperModal();
                            reloadPreview();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        alert('Error uploading image');
                        console.error(error);
                    })
                    .finally(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
                }
            });
        }
    </script>

</body>
</html>
