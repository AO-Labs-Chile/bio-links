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

// Auto-migration
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

$mensaje = '';

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
                $newName = uniqid() . '.' . $ext;
                $destination = 'uploads/' . $newName;
                if (move_uploaded_file($tmp_name, $destination)) {
                    return $destination;
                }
            }
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
            $url_imagen = $_POST['url_imagen_fetch'];
        }

        if (!empty($titulo)) {
            if ($_POST['action'] === 'add_link') {
                $stmtOrder = $pdo->query("SELECT MAX(orden) as max_ord FROM links");
                $row = $stmtOrder->fetch();
                $orden = ($row['max_ord'] ?? 0) + 1;
                
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
</head>
<body class="bg-gray-100 text-gray-800 font-sans h-screen flex flex-col">

    <!-- Nav -->
    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex flex-wrap justify-between items-center sticky top-0 z-40">
        <h1 class="font-bold text-xl text-gray-800 flex items-center gap-2 mb-2 md:mb-0">
            <i class="fas fa-link text-purple-600"></i> Admin Links
        </h1>
        <div class="flex items-center space-x-2 md:space-x-4">
            <a href="admin" class="bg-purple-100 text-purple-700 px-3 py-1.5 rounded font-bold text-sm"><i class="fas fa-list mr-1"></i> Links</a>
            <a href="settings" class="text-gray-600 hover:text-purple-600 font-semibold px-2 py-1.5 text-sm transition"><i class="fas fa-paint-roller mr-1"></i> Diseño</a>
            <a href="index" target="_blank" class="text-gray-600 hover:text-purple-600 font-semibold px-2 py-1.5 text-sm transition"><i class="fas fa-external-link-alt mr-1"></i> Ver Sitio</a>
            <a href="logout" class="text-red-500 hover:text-red-700 font-semibold px-2 py-1.5 text-sm transition"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </div>
    </nav>

    <div class="flex-1 flex overflow-hidden">
        <!-- Panel Izquierdo (Edición) -->
        <div class="flex-1 overflow-y-auto p-6 md:pr-4">
            
            <?php if ($mensaje): ?>
                <div class="bg-black text-white p-3 rounded-xl mb-6 text-center font-bold shadow-lg animate-pulse" id="msgAlert">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
                <script>setTimeout(()=>document.getElementById('msgAlert').style.display='none', 3000);</script>
            <?php endif; ?>

            <div class="sticky top-0 z-30 bg-gray-100/95 backdrop-blur-sm pb-4 pt-2 -mx-2 px-2" style="margin-top: -8px;">
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
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-hashtag text-purple-500"></i> Redes Sociales (Barra Superior)</h2>
                <div id="socialLinksContainer" class="space-y-4">
                    <?php if(empty($social_links_admin)): ?>
                        <p class="text-gray-400 text-sm italic">No tienes botones sociales. Agrega uno con el botón de arriba.</p>
                    <?php endif; ?>
                    <?php foreach ($social_links_admin as $link): ?>
                        <div class="bg-white rounded-3xl p-4 shadow-sm border border-gray-200 flex flex-col group transition hover:shadow-md link-item" data-id="<?= $link['id'] ?>">
                            <div class="flex items-center justify-between w-full">
                                <div class="drag-handle p-2 text-gray-300 hover:text-gray-500"><i class="fas fa-grip-vertical"></i></div>

                                <div class="flex-1 flex items-center px-4 cursor-pointer" onclick='openModal("edit", <?= json_encode($link) ?>)'>
                                    <div class="w-12 h-12 rounded bg-purple-50 flex items-center justify-center text-purple-600 text-xl mr-4"><i class="<?= htmlspecialchars($link['icono'] ?: 'fas fa-link') ?>"></i></div>
                                    <div class="overflow-hidden">
                                        <h3 class="font-bold text-gray-800 text-sm md:text-base flex items-center gap-2">
                                            <?= htmlspecialchars($link['titulo']) ?>
                                        </h3>
                                        <p class="text-xs text-gray-500 truncate mt-0.5"><?= htmlspecialchars($link['url']) ?></p>
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
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SECCIÓN ENLACES REGULARES -->
            <div>
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-list text-purple-500"></i> Enlaces y Contenido</h2>
                <div id="linksContainer" class="space-y-4 mb-12">
                    <?php if(empty($regular_links_admin)): ?>
                        <p class="text-gray-400 text-sm italic">No tienes enlaces regulares.</p>
                    <?php endif; ?>
                    <?php foreach ($regular_links_admin as $link): ?>
                        <div class="bg-white rounded-3xl p-4 shadow-sm border border-gray-200 flex flex-col group transition hover:shadow-md link-item <?= !empty($link['parent_id']) ? 'ml-8 md:ml-12 border-l-4 border-l-purple-400' : '' ?>" data-id="<?= $link['id'] ?>">
                            <div class="flex items-center justify-between w-full">
                                <div class="drag-handle p-2 text-gray-300 hover:text-gray-500"><i class="fas fa-grip-vertical"></i></div>

                                <div class="flex-1 flex items-center px-4 cursor-pointer" onclick='openModal("edit", <?= json_encode($link) ?>)'>
                                    <?php if ($link['tipo'] == 'header'): ?>
                                        <div class="w-12 h-12 rounded bg-gray-100 flex items-center justify-center text-gray-400 font-bold mr-4"><i class="fas fa-heading"></i></div>
                                    <?php elseif (!empty($link['icono'])): ?>
                                        <div class="w-12 h-12 rounded bg-purple-50 flex items-center justify-center text-purple-600 text-xl mr-4"><i class="<?= htmlspecialchars($link['icono']) ?>"></i></div>
                                    <?php elseif (!empty($link['url_imagen'])): ?>
                                        <img src="<?= htmlspecialchars($link['url_imagen']) ?>" class="w-12 h-12 rounded object-cover mr-4 border">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded bg-gray-100 flex items-center justify-center text-gray-400 mr-4"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>

                                    <div class="overflow-hidden">
                                        <h3 class="font-bold text-gray-800 text-sm md:text-base flex items-center gap-2">
                                            <?= htmlspecialchars($link['titulo']) ?>
                                            <span class="text-[10px] uppercase bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded font-bold"><?= $link['tipo'] ?></span>
                                            <?php if(isset($link['destacado']) && $link['destacado'] == 1): ?>
                                                <i class="fas fa-star text-yellow-500 text-xs" title="Destacado"></i>
                                            <?php endif; ?>
                                        </h3>
                                        <?php if (!empty($link['parent_id']) && isset($carpetas[$link['parent_id']])): ?>
                                            <p class="text-xs text-purple-600 font-semibold truncate mt-0.5"><i class="fas fa-level-up-alt rotate-90 mr-1"></i> Dentro de: <?= htmlspecialchars($carpetas[$link['parent_id']]) ?></p>
                                        <?php endif; ?>
                                        <?php if ($link['tipo'] != 'header'): ?>
                                            <p class="text-xs text-gray-500 truncate mt-0.5"><?= htmlspecialchars($link['url']) ?></p>
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

                            <?php if ($link['tipo'] != 'header'): ?>
                                <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between px-10 text-xs text-gray-500 font-semibold">
                                    <div><i class="fas fa-chart-bar mr-1"></i> <?= $link['clics'] ?> clics</div>
                                    <?php if ($link['tipo'] == 'concert'): ?>
                                        <div><i class="fas fa-calendar-alt mr-1"></i> Evento</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Panel Derecho (Vista Previa Móvil) -->
        <div class="hidden lg:flex w-[400px] border-l border-gray-200 bg-gray-50 items-center justify-center p-4">
            <div class="relative w-[320px] h-[650px] bg-black rounded-[40px] border-[8px] border-black shadow-2xl overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-6 bg-black z-50 rounded-b-xl w-32 mx-auto"></div>
                <iframe src=\"index\" id="previewIframe" class="w-full h-full bg-white border-0" title="Live Preview"></iframe>
            </div>
        </div>
    </div>

    <!-- Modal Agregar/Editar Elemento -->
    <div id="itemModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50 overflow-y-auto backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-lg my-8 relative">
            <button type="button" onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-800 text-2xl">&times;</button>
            <h2 id="modalTitle" class="text-2xl font-bold mb-6 text-gray-800">Agregar Elemento</h2>
            
            <form id="linkForm" action="admin" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" id="modalAction" value="add_link">
                <input type="hidden" name="id" id="modalId" value="0">
                <input type="hidden" name="current_imagen" id="modalCurrentImg" value="">
                
                <div>
                    <label class="block text-sm font-bold text-gray-700">Tipo</label>
                    <select name="tipo" id="modalTipo" class="mt-1 block w-full rounded-xl border-gray-300 p-3 border bg-gray-50 focus:ring-purple-500" onchange="toggleModalFields()">
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
                    <label class="block text-sm font-bold text-gray-700">Título / Texto</label>
                    <input type="text" name="titulo" id="modalTitulo" required class="mt-1 block w-full rounded-xl border-gray-300 p-3 border focus:ring-purple-500">
                </div>

                <div class="modal-field" data-types="concert,event">
                    <label class="block text-sm font-bold text-gray-700">Productora (Opcional)</label>
                    <select name="parent_id" id="modalParentId" class="mt-1 block w-full rounded-xl border-gray-300 p-3 border focus:ring-purple-500">
                        <option value="">-- Ninguna --</option>
                        <?php foreach($carpetas as $cid => $cnombre): ?>
                            <option value="<?= $cid ?>"><?= htmlspecialchars($cnombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-field" data-types="link,social,expandable,concert">
                    <label class="block text-sm font-bold text-gray-700">URL Destino</label>
                    <input type="url" name="url" id="modalUrl" class="mt-1 block w-full rounded-xl border-gray-300 p-3 border focus:ring-purple-500">
                </div>

                <div class="modal-field" data-types="link,expandable,folder,concert,event">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Imagen / Miniatura (Reemplaza al icono)</label>
                    <input type="hidden" name="icono" id="modalIcono">
                    <div class="grid grid-cols-6 sm:grid-cols-8 gap-2 bg-gray-50 p-3 rounded-xl border border-gray-200 max-h-48 overflow-y-auto" id="iconGridContainer">
                        <?php foreach($iconos_disponibles as $clase => $nombre): ?>
                            <div class="icon-option flex flex-col items-center justify-center p-2 rounded-lg cursor-pointer hover:bg-purple-100 transition border-2 border-transparent" data-icon="<?= $clase ?>" onclick="selectIcon('<?= $clase ?>')" title="<?= $nombre ?>">
                                <?php if(empty($clase)): ?>
                                    <i class="fas fa-ban text-gray-400 text-lg"></i>
                                <?php else: ?>
                                    <i class="<?= $clase ?> text-xl text-gray-700"></i>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="selectedIconLabel" class="text-xs text-gray-500 mt-2 font-semibold font-sans text-center"></div>
                </div>

                <div class="modal-field" data-types="link,concert,expandable,folder">
                    <label class="block text-sm font-bold text-gray-700">Miniatura (Subir o Extraer de URL)</label>
                    <div class="flex gap-2 mt-1">
                        <div class="flex-1">
                            <label class="flex flex-col items-center justify-center w-full h-12 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100">
                                <div class="flex items-center gap-2 text-sm text-gray-500"><i class="fas fa-upload"></i> Subir Archivo</div>
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
                    </div>
                    <div id="fetchStatus" class="text-xs text-center mt-2 h-4 font-semibold"></div>
                </div>

                <div class="modal-field" data-types="link,expandable,folder,concert,event">
                    <label class="block text-sm font-bold text-gray-700">Subtítulo</label>
                    <input type="text" name="subtitulo" id="modalSubtitulo" class="mt-1 block w-full rounded-xl border-gray-300 p-3 border">
                </div>

                <div class="modal-field" data-types="expandable,event">
                    <label class="block text-sm font-bold text-gray-700">Texto del Botón Principal (Opcional, Ej: Tickets / Más Info)</label>
                    <input type="text" name="btn_texto" id="modalBtnTexto" placeholder="Tickets / Más Info" class="mt-1 block w-full rounded-xl border-gray-300 p-3 border focus:ring-purple-500">
                </div>

                <div class="modal-field" data-types="concert,event">
                    <label class="block text-sm font-bold text-gray-700">Fecha del Concierto/Evento (Opcional)</label>
                    <div class="flex gap-2">
                        <input type="date" id="modalFechaDate" class="flex-1 block w-full rounded-xl border-gray-300 p-2 border text-sm focus:ring-purple-500">
                        <div class="flex items-center gap-1 bg-gray-50 border rounded-xl px-2">
                            <input type="number" id="modalFechaHour" placeholder="00" min="0" max="23" class="w-12 text-center bg-transparent border-0 focus:ring-0 text-sm p-1">
                            <span class="font-bold text-gray-400">:</span>
                            <input type="number" id="modalFechaMinute" placeholder="00" min="0" max="59" class="w-12 text-center bg-transparent border-0 focus:ring-0 text-sm p-1">
                        </div>
                    </div>
                    <input type="hidden" name="fecha_concierto" id="modalFecha">
                </div>
                
                <div class="modal-field" data-types="event">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Icono para Botón de Acción</label>
                    <input type="hidden" name="btn_icono" id="modalBtnIcono">
                    <div class="grid grid-cols-6 sm:grid-cols-8 gap-2 bg-gray-50 p-3 rounded-xl border border-gray-200 max-h-32 overflow-y-auto" id="btnIconGridContainer">
                        <?php foreach($iconos_disponibles as $clase => $nombre): ?>
                            <div class="btn-icon-option flex flex-col items-center justify-center p-2 rounded-lg cursor-pointer hover:bg-purple-100 transition border-2 border-transparent" data-icon="<?= $clase ?>" onclick="selectBtnIcon('<?= $clase ?>')" title="<?= $nombre ?>">
                                <i class="<?= $clase ?: 'fas fa-link' ?> text-lg text-gray-700"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-field" data-types="expandable">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Texto Desplegable (Admite formato)</label>
                    <div class="bg-white rounded-xl border border-gray-300 overflow-hidden">
                        <div id="quillEditor" style="min-height: 100px;"></div>
                    </div>
                    <input type="hidden" name="contenido_extra" id="modalContenido">
                </div>

                <div class="modal-field" data-types="concert">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Productora</label>
                            <input type="text" name="productora" id="modalProductora" class="mt-1 block w-full rounded-xl border-gray-300 p-3 border text-sm">
                        </div>
                        <div class="mt-2">
                            <label class="flex items-center space-x-3 cursor-pointer bg-yellow-50 border border-yellow-200 p-3 rounded-xl">
                                <input type="checkbox" name="destacado" id="modalDestacado" value="1" class="w-5 h-5 text-yellow-600 rounded border-gray-300 focus:ring-yellow-500">
                                <span class="font-bold text-yellow-800"><i class="fas fa-star mr-1"></i> Destacar (Fijar arriba)</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="w-full bg-purple-600 text-white font-bold py-4 rounded-full hover:bg-purple-700 transition shadow-lg text-lg">Guardar Elemento</button>
                </div>
            </form>
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

        function openModal(mode, data = null) {
            const isEdit = mode === 'edit';
            document.getElementById('modalTitle').innerText = isEdit ? 'Editar Elemento' : 'Agregar Elemento';
            document.getElementById('modalAction').value = isEdit ? 'edit_link' : 'add_link';
            
            document.getElementById('modalId').value = isEdit ? data.id : '0';
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
            document.getElementById('modalProductora').value = isEdit ? data.productora : '';
            document.getElementById('modalCurrentImg').value = isEdit ? data.url_imagen : '';
            document.getElementById('modalImgFetch').value = '';
            document.getElementById('modalParentId').value = isEdit && data.parent_id ? data.parent_id : '';
            document.getElementById('modalDestacado').checked = isEdit && data.destacado == 1;
            document.getElementById('fetchStatus').innerText = isEdit && data.url_imagen ? 'Tiene imagen guardada.' : '';
            
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

            if(!url) {
                status.innerText = 'Pon una URL arriba primero.';
                status.className = 'text-xs text-red-500 mt-2 h-4 font-bold';
                return;
            }
            status.innerText = 'Buscando imagen mágica...';
            status.className = 'text-xs text-blue-500 mt-2 h-4 font-bold';
            try {
                const fd = new FormData(); fd.append('url', url);
                const res = await fetch('fetch_og_image.php', { method: 'POST', body: fd });
                const json = await res.json();
                if(json.success) {
                    hiddenImg.value = json.image;
                    status.innerText = '¡Imagen capturada con éxito!';
                    status.className = 'text-xs text-green-600 mt-2 h-4 font-bold';
                } else {
                    status.innerText = json.error || 'No se encontró imagen.';
                    status.className = 'text-xs text-red-500 mt-2 h-4 font-bold';
                }
            } catch (err) {
                status.innerText = 'Error de conexión.';
                status.className = 'text-xs text-red-500 mt-2 h-4 font-bold';
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
        }
    </script>
</body>
</html>
