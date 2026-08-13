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

if (isset($_GET['check_update'])) {
    unset($_SESSION['last_update_check']);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Auto-migration
try {
    $pdo->exec("ALTER TABLE config ADD COLUMN idioma VARCHAR(10) DEFAULT 'es'");
} catch (Exception $e) {} // Ignore if column already exists

try {
    $pdo->exec("ALTER TABLE links ADD COLUMN btn_texto VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {} // Ignore if column already exists

try {
    $pdo->exec("ALTER TABLE links ADD COLUMN btn_icono VARCHAR(50) DEFAULT NULL");
} catch (Exception $e) {} // Ignore if column already exists

try {
    $pdo->exec("ALTER TABLE links MODIFY COLUMN tipo ENUM('link', 'header', 'expandable', 'folder', 'concert', 'social', 'event') DEFAULT 'link'");
} catch (Exception $e) {} // Ignore if already updated

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-migration
try {
    $pdo->exec("ALTER TABLE links ADD COLUMN IF NOT EXISTS parent_id INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE links MODIFY tipo ENUM('link', 'header', 'expandable', 'folder', 'concert', 'social') DEFAULT 'link'");
} catch (Exception $e) {}

require_once 'version.php';

// Comprobar actualizaciones (solo si es GET para no retrasar POSTs)
$update_available = false;
$update_info = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Para no ralentizar cada recarga, se podría guardar en SESSION, pero para demostración, lo hacemos en cada carga
    if (!isset($_SESSION['last_update_check']) || (time() - $_SESSION['last_update_check']) > 3600) {
        $json = @file_get_contents("https://raw.githubusercontent.com/AO-Labs-Chile/bio-links/main/updates/version.json");
        if ($json) {
            $data = json_decode($json, true);
            if ($data && isset($data['version']) && version_compare(APP_VERSION, $data['version'], '<')) {
                $_SESSION['update_available'] = true;
                $_SESSION['update_info'] = $data;
            } else {
                $_SESSION['update_available'] = false;
            }
        }
        $_SESSION['last_update_check'] = time();
    }
    
    if (!empty($_SESSION['update_available'])) {
        $update_available = true;
        $update_info = $_SESSION['update_info'];
    }
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error de seguridad (CSRF). Por favor recarga la página.");
    }

    if (isset($_POST['action']) && $_POST['action'] === 'upload_thumbnail_ajax') {
        header('Content-Type: application/json');
        try {
            $id = $_POST['id'] ?? 0;
            $type = $_POST['type'] ?? 'link'; // 'link' or 'avatar'
            $base64_image = $_POST['image'] ?? '';
            
            if (empty($base64_image)) {
                echo json_encode(['success' => false, 'error' => 'No image provided']);
                exit;
            }
            
            // Decode base64
            list($type_data, $data) = explode(';', $base64_image);
            list(, $data)      = explode(',', $data);
            $data = base64_decode($data);
            
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            
            $newName = uniqid() . '.png';
            $destination = 'uploads/' . $newName;
            
            if (file_put_contents($destination, $data)) {
                if ($type === 'link' && $id > 0) {
                    $stmt = $pdo->prepare("UPDATE links SET url_imagen = ? WHERE id = ?");
                    $stmt->execute([$destination, $id]);
                } else if ($type === 'avatar') {
                    $stmt = $pdo->prepare("UPDATE config SET url_logo = ?");
                    $stmt->execute([$destination]);
                }
                echo json_encode(['success' => true, 'url' => $destination]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save image']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
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

    function downloadRemoteImage($url) {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        $path = parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $ext = 'jpg';
        }
        
        if (!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
        }
        
        $newName = uniqid('fetch_') . '.' . $ext;
        $destination = 'uploads/' . $newName;
        
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $fp = fopen($destination, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_COOKIEFILE, "");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $success = curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            if ($success && file_exists($destination) && filesize($destination) > 0) {
                return $destination;
            }
        }
        
        if (ini_get('allow_url_fopen')) {
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36\r\n" .
                                "Connection: close\r\n",
                    'timeout' => 10
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ];
            $context = stream_context_create($options);
            $data = @file_get_contents($url, false, $context);
            if ($data !== false && !empty($data)) {
                if (file_put_contents($destination, $data) !== false) {
                    return $destination;
                }
            }
        }
        
        if (file_exists($destination)) {
            @unlink($destination);
        }
        return null;
    }

    if (isset($_POST['action']) && ($_POST['action'] === 'add_link' || $_POST['action'] === 'edit_link')) {
        $id = $_POST['id'] ?? 0;
        $titulo = $_POST['titulo'] ?? '';
        $url = $_POST['url'] ?? '';
        $estado = $_POST['estado'] ?? 'visible';
        $tipo = $_POST['tipo'] ?? 'link';
        $contenido_extra = $_POST['contenido_extra'] ?? '';
        $btn_texto = $_POST['btn_texto'] ?? '';
        $btn_icono = $_POST['btn_icono'] ?? '';
        $subtitulo = $_POST['subtitulo'] ?? '';
        $fecha_concierto = !empty($_POST['fecha_concierto']) ? $_POST['fecha_concierto'] : null;
        $productora = $_POST['productora'] ?? '';
        $icono = $_POST['icono'] ?? '';
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        $url_imagen = $_POST['current_imagen'] ?? '';
        $uploadedThumb = handleUpload('imagen_file');
        if ($uploadedThumb) {
            $url_imagen = $uploadedThumb;
        } else if (!empty($_POST['url_imagen_fetch'])) {
            $downloaded = downloadRemoteImage($_POST['url_imagen_fetch']);
            if ($downloaded) {
                $url_imagen = $downloaded;
            } else {
                $url_imagen = $_POST['url_imagen_fetch'];
            }
        }

        if (!empty($titulo)) {
            if ($_POST['action'] === 'add_link') {
                $orden = null;
                if (isset($_POST['insert_after']) && $_POST['insert_after'] !== '') {
                    if ($_POST['insert_after'] == '0') {
                        $orden = 1;
                        $pdo->query("UPDATE links SET orden = orden + 1");
                    } else {
                        $stmtAfter = $pdo->prepare("SELECT orden FROM links WHERE id = ?");
                        $stmtAfter->execute([$_POST['insert_after']]);
                        $afterLink = $stmtAfter->fetch();
                        if ($afterLink) {
                            $orden = $afterLink['orden'] + 1;
                            $stmtShift = $pdo->prepare("UPDATE links SET orden = orden + 1 WHERE orden >= ?");
                            $stmtShift->execute([$orden]);
                        }
                    }
                }
                
                if ($orden === null) {
                    $stmtOrder = $pdo->query("SELECT MAX(orden) as max_ord FROM links");
                    $row = $stmtOrder->fetch();
                    $orden = ($row['max_ord'] ?? 0) + 1;
                }
                
                $stmt = $pdo->prepare("INSERT INTO links (titulo, url, orden, estado, tipo, contenido_extra, btn_texto, btn_icono, url_imagen, subtitulo, fecha_concierto, productora, icono, destacado, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $url, $orden, $estado, $tipo, $contenido_extra, $btn_texto, $btn_icono, $url_imagen, $subtitulo, $fecha_concierto, $productora, $icono, $destacado, $parent_id]);
            } else if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE links SET titulo = ?, url = ?, tipo = ?, contenido_extra = ?, btn_texto = ?, btn_icono = ?, url_imagen = ?, subtitulo = ?, fecha_concierto = ?, productora = ?, icono = ?, destacado = ?, parent_id = ? WHERE id = ?");
                $stmt->execute([$titulo, $url, $tipo, $contenido_extra, $btn_texto, $btn_icono, $url_imagen, $subtitulo, $fecha_concierto, $productora, $icono, $destacado, $parent_id, $id]);
            }
            $mensaje = "Elemento guardado correctamente.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_link') {
        $id = $_POST['id'] ?? 0;
        if ($id > 0) {
            // Eliminar hijos si es una carpeta o acordeón
            $stmt = $pdo->prepare("DELETE FROM links WHERE parent_id = ?");
            $stmt->execute([$id]);

            $stmt = $pdo->prepare("DELETE FROM links WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = "Elemento eliminado.";
        }
    }
}

$stmtLinks = $pdo->query("SELECT * FROM links ORDER BY orden ASC");
$links = $stmtLinks->fetchAll();

$iconos_disponibles = [
    '' => 'Sin icono (Usar imagen)',
    'fab fa-instagram' => 'Instagram',
    'fab fa-tiktok' => 'TikTok',
    'fab fa-youtube' => 'YouTube',
    'fab fa-facebook' => 'Facebook',
    'fab fa-twitter' => 'Twitter / X',
    'fab fa-whatsapp' => 'WhatsApp',
    'fab fa-discord' => 'Discord',
    'fab fa-spotify' => 'Spotify',
    'fab fa-twitch' => 'Twitch',
    'fab fa-patreon' => 'Patreon',
    'fab fa-paypal' => 'PayPal',
    'fab fa-github' => 'GitHub',
    'fab fa-linkedin' => 'LinkedIn',
    'fab fa-pinterest' => 'Pinterest',
    'fab fa-telegram' => 'Telegram',
    'fab fa-reddit' => 'Reddit',
    'fab fa-steam' => 'Steam',
    'fas fa-globe' => 'Sitio Web',
    'fas fa-ticket-alt' => 'Ticket',
    'fas fa-shopping-cart' => 'Tienda',
    'fas fa-music' => 'Música',
    'fas fa-envelope' => 'Email',
    'fas fa-gamepad' => 'Juegos',
    'fas fa-store' => 'Comercio',
    'fas fa-tshirt' => 'Ropa/Merch',
    'fas fa-coffee' => 'Café/Donar',
    'fas fa-heart' => 'Corazón',
    'fas fa-star' => 'Estrella',
    'fas fa-video' => 'Video',
    'fas fa-camera' => 'Cámara',
    'fas fa-headphones' => 'Audífonos',
    'fas fa-book' => 'Libro',
    'fas fa-calendar' => 'Calendario',
    'fas fa-map-marker-alt' => 'Ubicación',
    'fas fa-phone' => 'Teléfono',
    'fas fa-link' => 'Enlace Genérico',
    'fas fa-crown' => 'Corona/Premium',
    'fas fa-gem' => 'Gema',
    'fas fa-gift' => 'Regalo',
    'fas fa-paint-brush' => 'Arte',
    'fas fa-users' => 'Comunidad',
    'fas fa-user' => 'Perfil'
];
?>
<?php
$stmtLogo = $pdo->query("SELECT url_logo FROM config LIMIT 1");
$adminLogo = $stmtLogo->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Links</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <style>
        .tipo-fields { display: none; }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
        .toggle-checkbox { z-index: 1; border-color: #e5e7eb; transition: all 0.3s; }
        .toggle-checkbox:checked { right: 0; border-color: #10B981; }
        .toggle-checkbox:not(:checked) { right: 20px; }
        .toggle-checkbox:checked + .toggle-label { background-color: #10B981; }
        .toggle-label { background-color: #e5e7eb; transition: all 0.3s; }
    </style>
<link rel="icon" href="<?= htmlspecialchars($adminLogo ?: 'favicon.ico') ?>">

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
    </script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        function toggleInlineThumbnail(id) {
            const panel = document.getElementById('inline-thumb-' + id);
            if (panel) {
                if (panel.classList.contains('hidden')) {
                    panel.classList.remove('hidden');
                } else {
                    panel.classList.add('hidden');
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 dark:bg-gray-900 text-gray-800 dark:text-gray-100 dark:text-gray-100 font-sans h-screen flex flex-col overflow-hidden transition-colors duration-300">
    <?php if ($update_available && $update_info): ?>
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-4 py-3 shadow-md flex justify-between items-center z-50">
        <div class="flex items-center gap-3">
            <i class="fas fa-gift text-2xl animate-bounce"></i>
            <div>
                <strong class="font-bold">¡Nueva versión disponible (<?= htmlspecialchars($update_info['version']) ?>)!</strong>
                <span class="text-sm opacity-90 hidden sm:inline ml-2"><?= htmlspecialchars($update_info['changelog'] ?? 'Hay nuevas mejoras y características esperándote.') ?></span>
            </div>
        </div>
        <form action="updater" method="POST" class="m-0 p-0" onsubmit="return confirm('¿Estás seguro que deseas actualizar ahora? Esto sobrescribirá los archivos del sistema.')">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="do_update">
            <button type="submit" class="bg-white text-purple-700 hover:bg-gray-100 font-bold py-2 px-4 rounded-xl text-sm transition shadow-sm flex items-center gap-2">
                <i class="fas fa-download"></i> Actualizar Ahora
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Nav -->
    

    <div class="flex-1 flex overflow-hidden w-full relative">

        <!-- Panel Izquierdo (Sidebar Escritorio) -->
        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 dark:border-gray-700 transition-colors duration-300 z-10">
            <div class="p-6 pb-2">
                <h1 class="font-bold text-2xl text-gray-800 dark:text-gray-100 dark:text-white flex items-center gap-2">
                    <i class="fas fa-link text-purple-600 dark:text-purple-400"></i> Admin
                </h1>
            </div>
            
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="admin" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                    <i class="fas fa-list"></i> <?= __('nav_links') ?>
                </a>
                <a href="settings" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors font-bold text-gray-600 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
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
                <div class="text-xs text-gray-400 dark:text-gray-500 text-center mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    Versión: v<?= APP_VERSION ?>
                </div>
            </div>
        </aside>

        <!-- Panel Central (Edición) -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6 pb-24 lg:pb-6 bg-gray-50 dark:bg-gray-900 dark:bg-gray-900 transition-colors duration-300 relative">

            <div class="max-w-4xl mx-auto w-full">
            
            <?php if ($mensaje): ?>
                <div class="bg-black text-white p-3 rounded-xl mb-6 text-center font-bold shadow-lg animate-pulse" id="msgAlert">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
                <script>setTimeout(()=>document.getElementById('msgAlert').style.display='none', 3000);</script>
            <?php endif; ?>

            <div class="sticky top-0 z-30 bg-gray-50/95 dark:bg-gray-900/95 backdrop-blur-sm pb-4 pt-4 -mx-2 px-2">
                <button onclick="openModal('add')" class="w-full bg-purple-600 text-white font-bold py-4 rounded-full shadow-lg hover:bg-purple-700 hover:scale-[1.01] transition-all flex justify-center items-center gap-2">
                    <i class="fas fa-plus"></i> Agregar Nuevo Enlace
                </button>
            </div>

            <?php
            $social_links_admin = [];
            $grupos = [];
            $grupo_actual = -1;
            $grupos[-1] = ['items' => [], 'concerts' => []];

            foreach ($links as $link) {
                if ($link['tipo'] === 'social') {
                    $social_links_admin[] = $link;
                    continue;
                }
                
                if ($link['tipo'] === 'header') {
                    $grupo_actual++;
                    $grupos[$grupo_actual] = ['header' => $link, 'items' => [], 'concerts' => []];
                } else {
                    if ($grupo_actual == -1) {
                        if ($link['tipo'] === 'concert') {
                            $grupos[-1]['concerts'][] = $link;
                        } else {
                            $grupos[-1]['items'][] = $link;
                        }
                    } else {
                        if ($link['tipo'] === 'concert') {
                            $grupos[$grupo_actual]['concerts'][] = $link;
                        } else {
                            $grupos[$grupo_actual]['items'][] = $link;
                        }
                    }
                }
            }

            $regular_links_admin = [];
            foreach ($grupos[-1]['items'] as $item) { $regular_links_admin[] = $item; }
            if (count($grupos[-1]['concerts']) > 0) {
                usort($grupos[-1]['concerts'], function($a, $b) {
                    $dest_a = isset($a['destacado']) ? (int)$a['destacado'] : 0;
                    $dest_b = isset($b['destacado']) ? (int)$b['destacado'] : 0;
                    if ($dest_a != $dest_b) return $dest_b - $dest_a;
                    $time_a = strtotime($a['fecha_concierto'] ?? '2099-12-31');
                    $time_b = strtotime($b['fecha_concierto'] ?? '2099-12-31');
                    return $time_a - $time_b;
                });
                foreach ($grupos[-1]['concerts'] as $concert) { $regular_links_admin[] = $concert; }
            }
            unset($grupos[-1]);

            foreach ($grupos as $grupo) {
                $regular_links_admin[] = $grupo['header'];
                foreach ($grupo['items'] as $item) { $regular_links_admin[] = $item; }
                
                if (count($grupo['concerts']) > 0) {
                    usort($grupo['concerts'], function($a, $b) {
                        $dest_a = isset($a['destacado']) ? (int)$a['destacado'] : 0;
                        $dest_b = isset($b['destacado']) ? (int)$b['destacado'] : 0;
                        if ($dest_a != $dest_b) return $dest_b - $dest_a;
                        $time_a = strtotime($a['fecha_concierto'] ?? '2099-12-31');
                        $time_b = strtotime($b['fecha_concierto'] ?? '2099-12-31');
                        return $time_a - $time_b;
                    });
                    foreach ($grupo['concerts'] as $concert) { $regular_links_admin[] = $concert; }
                }
            }

            // Obtener las carpetas para mostrar el nombre del padre
            $carpetas = [];
            foreach ($links as $lk) {
                if ($lk['tipo'] === 'expandable' || $lk['tipo'] === 'folder') {
                    $carpetas[$lk['id']] = $lk['titulo'];
                }
            }
            ?>

            <!-- SECCIÓN REDES SOCIALES -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"><i class="fas fa-hashtag text-purple-500"></i> Redes Sociales (Barra Superior)</h2>
                <div id="socialLinksContainer" class="space-y-4">
                    <?php if(empty($social_links_admin)): ?>
                        <p class="text-gray-400 text-sm italic">No tienes botones sociales. Agrega uno con el botón de arriba.</p>
                    <?php endif; ?>
                    <?php foreach ($social_links_admin as $link): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-3xl dark:border-gray-700 p-4 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col group transition hover:shadow-md link-item" data-id="<?= $link['id'] ?>">
                            <div class="flex items-center justify-between w-full">
                                <div class="drag-handle p-2 text-gray-300 hover:text-gray-500 dark:text-gray-400"><i class="fas fa-grip-vertical"></i></div>

                                <div class="flex-1 flex items-center px-4 cursor-pointer" onclick='openModal("edit", <?= json_encode($link) ?>)'>
                                    <div class="w-12 h-12 rounded bg-purple-50 flex items-center justify-center text-purple-600 text-xl mr-4"><i class="<?= htmlspecialchars($link['icono'] ?: 'fas fa-link') ?>"></i></div>
                                    <div class="overflow-hidden">
                                        <h3 class="font-bold text-gray-800 dark:text-gray-100 text-sm md:text-base flex items-center gap-2">
                                            <?= htmlspecialchars($link['titulo']) ?>
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5"><?= htmlspecialchars($link['url']) ?></p>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-2">
                                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                                        <input type="checkbox" name="toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer" <?= $link['estado'] == 'visible' ? 'checked' : '' ?> onchange="updateStatus(<?= $link['id'] ?>, this.checked)"/>
                                        <label class="toggle-label block overflow-hidden h-5 rounded-full bg-gray-300 cursor-pointer"></label>
                                    </div>
                                    <form action="admin" method="POST" onsubmit="return confirm('¿Borrar?');" class="mr-2">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action" value="delete_link">
                                        <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-500"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>

                            <!-- Inline Thumbnail Panel -->
                            <div id="inline-thumb-<?= $link['id'] ?>" class="hidden mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900 rounded-xl p-2 relative">
                                    <span class="absolute left-1/2 -translate-x-1/2 text-sm font-bold text-gray-700 dark:text-gray-300">Add Thumbnail</span>
                                    <button onclick="toggleInlineThumbnail(<?= $link['id'] ?>)" class="ml-auto text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 z-10 px-2">&times;</button>
                                </div>
                                <div class="mt-3 text-center">
                                    <input type="file" id="file-thumb-<?= $link['id'] ?>" accept="image/*" class="hidden" onchange="openCropperModal(this.files[0], <?= $link['id'] ?>, 'link')">
                                    <button onclick="document.getElementById('file-thumb-<?= $link['id'] ?>').click()" class="w-full bg-purple-600 text-white font-bold py-3 rounded-xl mb-2 hover:bg-purple-700 transition">Change</button>
                                    <form action="admin" method="POST" onsubmit="return confirm('¿Remover miniatura?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="action" value="remove_thumbnail">
                                        <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                        <button type="submit" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SECCIÓN ENLACES REGULARES -->
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"><i class="fas fa-list text-purple-500"></i> Enlaces y Contenido</h2>
                <div id="linksContainer" class="space-y-4 mb-12">
                    <?php if(empty($regular_links_admin)): ?>
                        <p class="text-gray-400 text-sm italic">No tienes enlaces regulares.</p>
                    <?php else: ?>
                        <!-- Insert before the very first item -->
                        <div class="insert-between group relative h-4 -my-2 z-10 flex items-center justify-center cursor-pointer" onclick="openModal('add', null, 0)">
                            <div class="absolute inset-0 bg-transparent"></div>
                            <div class="w-full h-[2px] bg-purple-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="absolute w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-sm font-bold shadow-md">
                                <i class="fas fa-plus"></i>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($regular_links_admin as $link): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-3xl dark:border-gray-700 p-4 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col group transition hover:shadow-md link-item <?= !empty($link['parent_id']) ? 'ml-8 md:ml-12 border-l-4 border-l-purple-400' : '' ?>" data-id="<?= $link['id'] ?>">
                            <div class="flex items-center justify-between w-full">
                                <div class="drag-handle p-2 text-gray-300 hover:text-gray-500 dark:text-gray-400"><i class="fas fa-grip-vertical"></i></div>

                                <div class="flex-1 flex items-center px-4">
                                    <div class="cursor-pointer mr-4" onclick='toggleInlineThumbnail(<?= $link["id"] ?>)'>
                                        <?php if ($link['tipo'] == 'header'): ?>
                                            <div class="w-12 h-12 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 font-bold"><i class="fas fa-heading"></i></div>
                                        <?php elseif (!empty($link['icono'])): ?>
                                            <div class="w-12 h-12 rounded bg-purple-50 dark:bg-purple-900/40 flex items-center justify-center text-purple-600 dark:text-purple-400 text-xl"><i class="<?= htmlspecialchars($link['icono']) ?>"></i></div>
                                        <?php elseif (!empty($link['url_imagen'])): ?>
                                            <img src="<?= htmlspecialchars($link['url_imagen']) ?>" id="thumb-img-<?= $link['id'] ?>" class="w-12 h-12 rounded object-cover border border-gray-200 dark:border-gray-600">
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="overflow-hidden flex-1 cursor-pointer" onclick='openModal("edit", <?= json_encode($link) ?>)'>
                                        <h3 class="font-bold text-gray-800 dark:text-gray-100 text-sm md:text-base flex items-center gap-2">
                                            <?= htmlspecialchars($link['titulo']) ?>
                                            <span class="text-[10px] uppercase bg-gray-200 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded font-bold"><?= $link['tipo'] ?></span>
                                            <?php if(isset($link['destacado']) && $link['destacado'] == 1): ?>
                                                <i class="fas fa-star text-yellow-500 text-xs" title="Destacado"></i>
                                            <?php endif; ?>
                                        </h3>
                                        <?php if (!empty($link['parent_id']) && isset($carpetas[$link['parent_id']])): ?>
                                            <p class="text-xs text-purple-600 font-semibold truncate mt-0.5"><i class="fas fa-level-up-alt rotate-90 mr-1"></i> Dentro de: <?= htmlspecialchars($carpetas[$link['parent_id']]) ?></p>
                                        <?php endif; ?>
                                        <?php if ($link['tipo'] != 'header'): ?>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5"><?= htmlspecialchars($link['url']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-2">
                                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                                        <input type="checkbox" name="toggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer" <?= $link['estado'] == 'visible' ? 'checked' : '' ?> onchange="updateStatus(<?= $link['id'] ?>, this.checked)"/>
                                        <label class="toggle-label block overflow-hidden h-5 rounded-full bg-gray-300 cursor-pointer"></label>
                                    </div>
                                    <form action="admin" method="POST" onsubmit="return confirm('<?php echo ($link['tipo'] === 'expandable' || $link['tipo'] === 'folder') ? 'ADVERTENCIA: Esta es una carpeta. Si la borras, TODOS los botones anidados o hijos dentro de ella se eliminarán también. ¿Estás seguro?' : '¿Borrar?'; ?>');" class="mr-2">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action" value="delete_link">
                                        <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-500"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>

                            <!-- Inline Thumbnail Panel -->
                            <div id="inline-thumb-<?= $link['id'] ?>" class="hidden mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900 rounded-xl p-2 relative">
                                    <span class="absolute left-1/2 -translate-x-1/2 text-sm font-bold text-gray-700 dark:text-gray-300">Add Thumbnail</span>
                                    <button onclick="toggleInlineThumbnail(<?= $link['id'] ?>)" class="ml-auto text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 z-10 px-2">&times;</button>
                                </div>
                                <div class="mt-3 text-center">
                                    <input type="file" id="file-thumb-<?= $link['id'] ?>" accept="image/*" class="hidden" onchange="openCropperModal(this.files[0], <?= $link['id'] ?>, 'link')">
                                    <button onclick="document.getElementById('file-thumb-<?= $link['id'] ?>').click()" class="w-full bg-purple-600 text-white font-bold py-3 rounded-xl mb-2 hover:bg-purple-700 transition">Change</button>
                                    <form action="admin" method="POST" onsubmit="return confirm('¿Remover miniatura?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="action" value="remove_thumbnail">
                                        <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                        <button type="submit" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">Remove</button>
                                    </form>
                                </div>
                            </div>

                            <?php if ($link['tipo'] != 'header'): ?>
                                <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between px-10 text-xs text-gray-500 dark:text-gray-400 font-semibold">
                                    <div><i class="fas fa-chart-bar mr-1"></i> <?= $link['clics'] ?> clics</div>
                                    <?php if ($link['tipo'] == 'concert'): ?>
                                        <div><i class="fas fa-calendar-alt mr-1"></i> Evento</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Insert between items -->
                        <div class="insert-between group relative h-4 -my-2 z-10 flex items-center justify-center cursor-pointer" onclick="openModal('add', null, <?= $link['id'] ?>)">
                            <div class="absolute inset-0 bg-transparent"></div>
                            <div class="w-full h-[2px] bg-purple-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="absolute w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-sm font-bold shadow-md">
                                <i class="fas fa-plus"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
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

    <div id="itemModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50 backdrop-blur-sm" onclick="if(event.target === this) confirmCloseModal()">
        <div class="bg-white dark:bg-gray-800 rounded-3xl dark:border-gray-700 shadow-2xl w-full max-w-lg relative flex flex-col max-h-[90vh]">
            <div class="flex-shrink-0 flex items-center justify-between p-6 border-b border-gray-100">
                <h2 id="modalTitle" class="text-2xl font-bold text-gray-800 dark:text-gray-100">Agregar Elemento</h2>
                <button type="button" onclick="confirmCloseModal()" class="text-gray-400 hover:text-gray-800 dark:text-gray-100 text-3xl leading-none">&times;</button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                <form id="linkForm" action="admin" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" id="modalAction" value="add_link">
                <input type="hidden" name="id" id="modalId" value="0">
                <input type="hidden" name="current_imagen" id="modalCurrentImg" value="">
                <input type="hidden" name="insert_after" id="modalInsertAfter" value="">

                <!-- 1. Información Básica -->
                <div class="space-y-4 bg-gray-50 dark:bg-gray-900/50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider mb-2"><i class="fas fa-info-circle mr-1"></i> Información Básica</h3>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Tipo</label>
                        <select name="tipo" id="modalTipo" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border bg-white dark:bg-gray-800 focus:ring-purple-500" onchange="toggleModalFields()">
                            <option value="link">Enlace Normal</option>
                            <option value="social">Botón Red Social (Arriba)</option>
                            <option value="header">Encabezado Separador</option>
                            <option value="folder">Carpeta / Categoría</option>
                            <option value="expandable">Acordeón Clásico (con link extra)</option>
                            <option value="concert">Concierto (Clásico)</option>
                            <option value="event">Evento (Botón Personalizable)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Título / Texto</label>
                        <input type="text" name="titulo" id="modalTitulo" required class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border bg-white dark:bg-gray-800 focus:ring-purple-500">
                    </div>

                    <div class="modal-field" data-types="link,social,expandable,concert">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">URL Destino</label>
                        <input type="url" name="url" id="modalUrl" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border bg-white dark:bg-gray-800 focus:ring-purple-500">
                    </div>

                    <div class="modal-field" data-types="link,expandable,folder,concert,event">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Subtítulo</label>
                        <input type="text" name="subtitulo" id="modalSubtitulo" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border bg-white dark:bg-gray-800 focus:ring-purple-500">
                    </div>

                    <div class="modal-field" data-types="link,concert,event">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Carpeta Padre (Opcional)</label>
                        <select name="parent_id" id="modalParentId" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border bg-white dark:bg-gray-800 focus:ring-purple-500">
                            <option value="">-- Ninguna --</option>
                            <?php foreach($carpetas as $cid => $cnombre): ?>
                                <option value="<?= $cid ?>"><?= htmlspecialchars($cnombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="modal-field" data-types="concert,event">
                        <label class="flex items-center space-x-3 cursor-pointer bg-yellow-50 border border-yellow-200 p-3 rounded-xl mt-2">
                            <input type="checkbox" name="destacado" id="modalDestacado" value="1" class="w-5 h-5 text-yellow-600 rounded border-gray-300 dark:border-gray-600 focus:ring-yellow-500">
                            <span class="font-bold text-yellow-800"><i class="fas fa-star mr-1"></i> Destacar (Fijar arriba)</span>
                        </label>
                    </div>
                </div>

                <!-- 2. Apariencia Visual -->
                <div class="space-y-4 bg-gray-50 dark:bg-gray-900/50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 modal-field" data-types="link,social,expandable,folder,concert,event">
                    <h3 class="font-bold text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider mb-2"><i class="fas fa-paint-brush mr-1"></i> Apariencia Visual</h3>
                    
                    <div class="modal-field" data-types="link,expandable,folder,concert,event">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Miniatura (Subir o Extraer de URL)</label>
                        <div class="flex gap-2 mt-1">
                            <div class="flex-1">
                                <label class="flex flex-col items-center justify-center w-full h-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer bg-white hover:bg-gray-50 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"><i class="fas fa-upload"></i> Subir Archivo</div>
                                    <input type="file" name="imagen_file" accept="image/*" class="hidden">
                                </label>
                            </div>
                            <div class="flex items-center justify-center font-bold text-gray-400">O</div>
                            <div class="flex-1 flex">
                                <input type="hidden" name="url_imagen_fetch" id="modalImgFetch">
                                <button type="button" onclick="fetchMagicImage()" class="w-full bg-purple-100 text-purple-700 hover:bg-purple-200 rounded-xl font-bold text-sm h-12 transition">
                                    <i class="fas fa-magic"></i> Extraer de URL
                                </button>
                            </div>
                        <div id="fetchStatus" class="text-xs text-center mt-2 h-4 font-semibold text-gray-500 dark:text-gray-400"></div>
                        <div id="magicImagePreviewContainer" class="hidden mt-3 flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
                            <img id="magicSelectedImagePreview" src="" class="w-12 h-12 object-cover rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-200 dark:bg-gray-700">
                            <div class="flex-1">
                                <div class="text-xs font-bold text-green-600 dark:text-green-400">¡Imagen Seleccionada!</div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate max-w-[200px]" id="magicSelectedImageName"></div>
                            </div>
                            <button type="button" onclick="openMagicImagePopup()" class="px-3 py-1.5 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/40 dark:hover:bg-purple-900/60 text-purple-700 dark:text-purple-300 font-bold text-xs rounded-xl transition">
                                Cambiar
                            </button>
                        </div>
                    </div>

                    <div class="modal-field" data-types="link,social,folder,concert,event">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Icono Principal (Si no hay miniatura)</label>
                        <input type="hidden" name="icono" id="modalIcono">
                        <div class="grid grid-cols-6 sm:grid-cols-8 gap-2 bg-white p-3 rounded-xl border border-gray-200 dark:border-gray-700 max-h-32 overflow-y-auto" id="iconGridContainer">
                            <?php foreach($iconos_disponibles as $clase => $nombre): ?>
                                <div class="icon-option flex flex-col items-center justify-center p-2 rounded-lg cursor-pointer hover:bg-purple-100 transition border-2 border-transparent" data-icon="<?= $clase ?>" onclick="selectIcon('<?= $clase ?>')" title="<?= $nombre ?>">
                                    <?php if(empty($clase)): ?>
                                        <i class="fas fa-ban text-gray-400 text-lg"></i>
                                    <?php else: ?>
                                        <i class="<?= $clase ?> text-xl text-gray-700 dark:text-gray-300"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="selectedIconLabel" class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-semibold font-sans text-center"></div>
                    </div>
                </div>

                <!-- 3. Detalles del Evento -->
                <div class="space-y-4 bg-gray-50 dark:bg-gray-900/50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 modal-field" data-types="concert,event">
                    <h3 class="font-bold text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider mb-2"><i class="fas fa-calendar-alt mr-1"></i> Detalles del Evento</h3>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Fecha y Hora (Opcional)</label>
                        <div class="flex gap-2 mt-1">
                            <input type="date" id="modalFechaDate" class="flex-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-2 border bg-white dark:bg-gray-800 focus:ring-purple-500">
                            <div class="flex items-center gap-1 bg-white border border-gray-300 dark:border-gray-600 rounded-xl px-2">
                                <input type="number" id="modalFechaHour" placeholder="00" min="0" max="23" class="w-12 text-center bg-transparent border-0 focus:ring-0 p-1">
                                <span class="font-bold text-gray-400">:</span>
                                <input type="number" id="modalFechaMinute" placeholder="00" min="0" max="59" class="w-12 text-center bg-transparent border-0 focus:ring-0 p-1">
                            </div>
                        </div>
                        <input type="hidden" name="fecha_concierto" id="modalFecha">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Productora (Opcional)</label>
                        <input type="text" name="productora" id="modalProductora" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border bg-white dark:bg-gray-800 focus:ring-purple-500">
                    </div>
                </div>

                <!-- 4. Botón de Acción -->
                <div class="space-y-4 bg-gray-50 dark:bg-gray-900/50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 modal-field" data-types="expandable,event">
                    <h3 class="font-bold text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider mb-2"><i class="fas fa-hand-pointer mr-1"></i> Botón de Acción</h3>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Texto del Botón (Ej: Tickets / Comprar)</label>
                        <input type="text" name="btn_texto" id="modalBtnTexto" placeholder="Opcional" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 p-3 border bg-white dark:bg-gray-800 focus:ring-purple-500">
                    </div>

                    <div class="modal-field" data-types="event">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Icono del Botón</label>
                        <input type="hidden" name="btn_icono" id="modalBtnIcono">
                        <div class="grid grid-cols-6 sm:grid-cols-8 gap-2 bg-white p-3 rounded-xl border border-gray-200 dark:border-gray-700 max-h-32 overflow-y-auto" id="btnIconGridContainer">
                            <?php foreach($iconos_disponibles as $clase => $nombre): ?>
                                <div class="btn-icon-option flex flex-col items-center justify-center p-2 rounded-lg cursor-pointer hover:bg-purple-100 transition border-2 border-transparent" data-icon="<?= $clase ?>" onclick="selectBtnIcon('<?= $clase ?>')" title="<?= $nombre ?>">
                                    <i class="<?= $clase ?: 'fas fa-link' ?> text-lg text-gray-700 dark:text-gray-300"></i>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 5. Contenido Extra -->
                <div class="space-y-4 bg-gray-50 dark:bg-gray-900/50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 modal-field" data-types="expandable">
                    <h3 class="font-bold text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider mb-2"><i class="fas fa-file-alt mr-1"></i> Contenido Desplegable</h3>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Texto Detallado</label>
                        <div class="bg-white rounded-xl border border-gray-300 dark:border-gray-600 overflow-hidden">
                            <div id="quillEditor" style="min-height: 100px;"></div>
                        </div>
                        <input type="hidden" name="contenido_extra" id="modalContenido">
                    </div>
                </div>
                <div class="pt-6">
                    <button type="submit" class="w-full bg-purple-600 text-white font-bold py-4 rounded-full hover:bg-purple-700 transition shadow-lg text-lg">Guardar Elemento</button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script>
        function toggleModalFields() {
            const val = document.getElementById('modalTipo').value;
            document.querySelectorAll('.modal-field').forEach(el => {
                const types = el.getAttribute('data-types').split(',');
                el.style.display = types.includes(val) ? 'block' : 'none';
            });
        }

        function openModal(mode, data = null, insertAfterId = null) {
            const isEdit = mode === 'edit';
            document.getElementById('modalTitle').innerText = isEdit ? 'Editar Elemento' : 'Agregar Elemento';
            document.getElementById('modalAction').value = isEdit ? 'edit_link' : 'add_link';
            
            document.getElementById('modalId').value = isEdit ? data.id : '0';
            document.getElementById('modalInsertAfter').value = insertAfterId !== null ? insertAfterId : '';
            document.getElementById('modalTipo').value = isEdit ? data.tipo : 'link';
            document.getElementById('modalTitulo').value = isEdit ? data.titulo : '';
            document.getElementById('modalUrl').value = isEdit ? data.url : '';
            document.getElementById('modalIcono').value = isEdit ? data.icono : '';
            document.getElementById('modalSubtitulo').value = isEdit ? data.subtitulo : '';
            document.getElementById('modalContenido').value = isEdit ? data.contenido_extra : '';
            document.getElementById('modalBtnTexto').value = isEdit ? (data.btn_texto || '') : '';
            document.getElementById('modalBtnIcono').value = isEdit ? (data.btn_icono || '') : '';
            if (window.quill) {
                window.quill.root.innerHTML = isEdit ? (data.contenido_extra || '') : '';
            }
            document.getElementById('modalCurrentImg').value = isEdit ? data.url_imagen : '';
            document.getElementById('modalImgFetch').value = '';
            document.getElementById('fetchStatus').innerText = isEdit && data.url_imagen ? 'Tiene imagen guardada.' : '';
            
            // Limpiar selector de imágenes mágicas
            const magicPreview = document.getElementById('magicImagePreviewContainer');
            if (magicPreview) {
                magicPreview.classList.add('hidden');
                document.getElementById('magicImageGrid').innerHTML = '';
            }
            document.getElementById('modalParentId').value = isEdit && data.parent_id ? data.parent_id : '';
            document.getElementById('modalDestacado').checked = isEdit && data.destacado == 1;
            document.getElementById('modalProductora').value = isEdit ? data.productora : '';
            
            selectIcon(isEdit ? (data.icono || '') : '');
            selectBtnIcon(isEdit ? (data.btn_icono || '') : '');
            
            if(isEdit && data.fecha_concierto) {
                const p = data.fecha_concierto.split(' ');
                document.getElementById('modalFechaDate').value = p[0] || '';
                if(p[1]) {
                    const t = p[1].split(':');
                    document.getElementById('modalFechaHour').value = t[0] || '';
                    document.getElementById('modalFechaMinute').value = t[1] || '';
                }
            } else {
                document.getElementById('modalFechaDate').value = '';
                document.getElementById('modalFechaHour').value = '';
                document.getElementById('modalFechaMinute').value = '';
            }

            toggleModalFields();
            document.getElementById('itemModal').classList.remove('hidden');
        }

        function selectBtnIcon(iconClass) {
            document.getElementById('modalBtnIcono').value = iconClass;
            document.querySelectorAll('.btn-icon-option').forEach(el => {
                if (el.getAttribute('data-icon') === iconClass) {
                    el.classList.add('border-purple-500', 'bg-purple-100');
                    el.classList.remove('border-transparent');
                } else {
                    el.classList.remove('border-purple-500', 'bg-purple-100');
                    el.classList.add('border-transparent');
                }
            });
        }

        function confirmCloseModal() {
            if (confirm("¿Estás seguro que deseas cerrar el editor? Se perderán los cambios no guardados.")) {
                closeModal();
            }
        }

        function closeModal() {
            document.getElementById('itemModal').classList.add('hidden');
        }

        const iconNames = <?= json_encode($iconos_disponibles) ?>;

        function selectIcon(iconClass) {
            document.getElementById('modalIcono').value = iconClass;
            document.querySelectorAll('.icon-option').forEach(el => {
                if (el.getAttribute('data-icon') === iconClass) {
                    el.classList.add('border-purple-500', 'bg-purple-100');
                    el.classList.remove('border-transparent');
                } else {
                    el.classList.remove('border-purple-500', 'bg-purple-100');
                    el.classList.add('border-transparent');
                }
            });
            document.getElementById('selectedIconLabel').innerText = "Seleccionado: " + (iconNames[iconClass] || 'Ninguno');
        }
        
        window.addEventListener('DOMContentLoaded', () => {
            if(document.getElementById('quillEditor')){
                window.quill = new Quill('#quillEditor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['clean']
                        ]
                    }
                });
            }
        });

        document.getElementById('linkForm').addEventListener('submit', function(e) {
            if (window.quill) {
                const html = window.quill.root.innerHTML;
                document.getElementById('modalContenido').value = html === '<p><br></p>' ? '' : html;
            }
            
            const d = document.getElementById('modalFechaDate').value;
            const h = document.getElementById('modalFechaHour').value.padStart(2, '0');
            const m = document.getElementById('modalFechaMinute').value.padStart(2, '0');
            if (d && h && m) {
                document.getElementById('modalFecha').value = `${d} ${h}:${m}:00`;
            } else {
                document.getElementById('modalFecha').value = '';
            }
        });

        const csrfToken = "<?= $_SESSION['csrf_token'] ?>";

        async function fetchMagicImage() {
            const url = document.getElementById('modalUrl').value;
            const status = document.getElementById('fetchStatus');
            const hiddenImg = document.getElementById('modalImgFetch');
            const previewContainer = document.getElementById('magicImagePreviewContainer');
            const previewGrid = document.getElementById('magicImageGrid');
            const previewImg = document.getElementById('magicSelectedImagePreview');
            const previewName = document.getElementById('magicSelectedImageName');

            if(!url) {
                status.innerText = 'Pon una URL arriba primero.';
                status.className = 'text-xs text-red-500 mt-2 h-4 font-bold';
                return;
            }
            
            // Ocultar previsualizaciones previas
            if (previewContainer) previewContainer.classList.add('hidden');
            if (previewGrid) previewGrid.innerHTML = '';
            hiddenImg.value = '';
            
            status.innerText = 'Buscando imágenes...';
            status.className = 'text-xs text-blue-500 mt-2 h-4 font-bold';
            
            try {
                // Codificar en Base64 para evitar bloqueos preventivos de WAF (ModSecurity SSRF Protection)
                const encodedUrl = btoa(unescape(encodeURIComponent(url)));
                const fd = new FormData(); fd.append('url', encodedUrl);
                const res = await fetch('fetch_og_image.php', { method: 'POST', body: fd });
                const json = await res.json();
                
                // Si la respuesta incluye 'image' y 'images' (o solo una), extraer y validar
                const images = json.images || (json.image ? [json.image] : []);
                
                if(json.success && images.length > 0) {
                    status.innerText = '¡Imágenes encontradas con éxito!';
                    status.className = 'text-xs text-green-600 mt-2 h-4 font-bold';
                    
                    // Renderizar las imágenes encontradas si el contenedor del grid del popup existe
                    if (previewGrid) {
                        images.forEach((imgUrl, index) => {
                            const wrapper = document.createElement('div');
                            wrapper.className = 'relative aspect-square rounded-2xl overflow-hidden cursor-pointer border-4 border-transparent hover:border-purple-500 transition-all bg-gray-100 dark:bg-gray-800 shadow-sm';
                            wrapper.onclick = () => {
                                selectMagicImage(imgUrl, wrapper);
                                closeMagicImagePopup();
                            };
                            
                            const imgEl = document.createElement('img');
                            imgEl.src = imgUrl;
                            imgEl.className = 'w-full h-full object-cover';
                            wrapper.appendChild(imgEl);
                            
                            // Seleccionar la primera por defecto
                            if (index === 0) {
                                selectMagicImage(imgUrl, wrapper);
                            }
                            
                            previewGrid.appendChild(wrapper);
                        });
                        
                        // Abrir el selector popup
                        openMagicImagePopup();
                    } else {
                        // Respaldo por si el HTML está cacheado sin el grid: asignar directamente la primera imagen
                        hiddenImg.value = images[0];
                        if (previewImg) previewImg.src = images[0];
                        if (previewName) previewName.innerText = images[0].split('/').pop() || 'imagen';
                        if (previewContainer) previewContainer.classList.remove('hidden');
                    }
                } else {
                    status.innerText = json.error || 'No se encontraron imágenes en la URL.';
                    status.className = 'text-xs text-red-500 mt-2 h-4 font-bold';
                }
            } catch (err) {
                console.error(err);
                status.innerText = 'Error de conexión o datos inválidos.';
                status.className = 'text-xs text-red-500 mt-2 h-4 font-bold';
            }
        }

        function selectMagicImage(url, element) {
            document.getElementById('modalImgFetch').value = url;
            
            const previewImg = document.getElementById('magicSelectedImagePreview');
            const previewName = document.getElementById('magicSelectedImageName');
            const previewContainer = document.getElementById('magicImagePreviewContainer');
            
            if (previewImg) previewImg.src = url;
            if (previewName) {
                const filename = url.split('/').pop() || 'imagen';
                previewName.innerText = filename.length > 25 ? filename.substring(0, 22) + '...' : filename;
            }
            if (previewContainer) previewContainer.classList.remove('hidden');
            
            const grid = document.getElementById('magicImageGrid');
            if (grid) {
                grid.querySelectorAll('div').forEach(div => {
                    div.classList.remove('border-purple-600', 'ring-4', 'ring-purple-300/50');
                    div.classList.add('border-transparent');
                });
                if (element) {
                    element.classList.remove('border-transparent');
                    element.classList.add('border-purple-600', 'ring-4', 'ring-purple-300/50');
                }
            }
        }

        async function updateStatus(id, isVisible) {
            const estado = isVisible ? 'visible' : 'oculto';
            try {
                await fetch('update_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id, estado, csrf_token: csrfToken})
                });
                reloadPreview();
            } catch(e) { console.error(e); }
        }

        var el = document.getElementById('linksContainer');
        if(el) {
            var sortable = Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                filter: '.insert-between',
                onEnd: function (evt) {
                    const items = el.querySelectorAll('.link-item');
                    const order = Array.from(items).map(item => item.getAttribute('data-id'));
                    fetch('update_order.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({order: order, csrf_token: csrfToken})
                    }).then(res => res.json()).then(data => {
                        if(data.success) { reloadPreview(); }
                    });
                }
            });
        }

        var elSocial = document.getElementById('socialLinksContainer');
        if(elSocial) {
            Sortable.create(elSocial, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function (evt) {
                    const items = elSocial.querySelectorAll('.link-item');
                    const order = Array.from(items).map(item => item.getAttribute('data-id'));
                    fetch('update_order.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({order: order, csrf_token: csrfToken})
                    }).then(res => res.json()).then(data => {
                        if(data.success) { reloadPreview(); }
                    });
                }
            });
        }

        function reloadPreview() {
            const iframe = document.getElementById('previewIframe');
            if (iframe) iframe.src = iframe.src;
            const mIframe = document.getElementById('mobilePreviewIframe');
            if (mIframe) mIframe.src = mIframe.src;
        }
    </script>

    <!-- Mobile Bottom Nav -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 flex justify-around p-2 z-40 transition-colors duration-300 pb-safe">
        <a href="admin" class="flex flex-col items-center p-2 text-purple-600 dark:text-purple-400">
            <i class="fas fa-list text-xl mb-1"></i>
            <span class="text-[10px] font-bold">Links</span>
        </a>
        <a href="settings" class="flex flex-col items-center p-2 text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-purple-600">
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

        function openMagicImagePopup() {
            const popup = document.getElementById('magicImagePopup');
            const card = document.getElementById('magicPopupCard');
            if (popup && card) {
                popup.classList.remove('hidden');
                popup.offsetHeight; // force reflow
                popup.classList.remove('opacity-0');
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }
        }

        function closeMagicImagePopup() {
            const popup = document.getElementById('magicImagePopup');
            const card = document.getElementById('magicPopupCard');
            if (popup && card) {
                popup.classList.add('opacity-0');
                card.classList.remove('scale-100');
                card.classList.add('scale-95');
                setTimeout(() => {
                    popup.classList.add('hidden');
                }, 300);
            }
        }
    </script>

    <!-- Modal Selector de Imágenes Mágicas (Popup) -->
    <div id="magicImagePopup" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-900 rounded-3xl max-w-md w-full mx-4 overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform scale-95 transition-transform duration-300 animate-fade-in" id="magicPopupCard">
            <!-- Cabecera -->
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">Imágenes Encontradas</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-sans">Elige la imagen que deseas asignar a este enlace</p>
                </div>
                <button type="button" onclick="closeMagicImagePopup()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <!-- Contenido -->
            <div class="p-5 max-h-[300px] overflow-y-auto">
                <div id="magicImageGrid" class="grid grid-cols-3 gap-3">
                    <!-- Se rellena dinámicamente -->
                </div>
            </div>
            <!-- Pie -->
            <div class="p-4 bg-gray-50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                <button type="button" onclick="closeMagicImagePopup()" class="px-4 py-2 text-sm font-bold text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 rounded-xl transition">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</body>
</html>
