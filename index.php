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
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE config ADD COLUMN idioma VARCHAR(10) DEFAULT 'es'");
} catch (Exception $e) {}
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

$stmtConfig = $pdo->query("SELECT * FROM config LIMIT 1");
$config = $stmtConfig->fetch();

if (!$config) {
    $config = [
        'nombre_sitio' => 'Mi Bio Links',
        'biografia' => '',
        'url_logo' => '',
        'color_fondo' => '#4c1d95',
        'color_fondo_secundario' => '',
        'angulo_gradiente' => 180,
        'color_texto' => '#f3f4f6',
        'color_boton' => '#4ade80',
        'color_boton_texto' => '#111827',
        'patron_fondo' => 'ninguno',
        'fuente_texto' => 'Inter',
        'estilo_boton' => 'solid',
        'redondeo_boton' => 'rounded-xl',
        'url_fondo' => ''
    ];
}

$stmtLinks = $pdo->query("SELECT * FROM links WHERE estado = 'visible' ORDER BY orden ASC");
$todos_links = $stmtLinks->fetchAll();

$social_links = [];
$regular_links = [];

// Agrupación y Ordenamiento Inteligente
$grupos = [];
$grupo_actual = -1;
$grupos[-1] = [];
$hijos = [];

// Primero separamos los hijos
foreach ($todos_links as $link) {
    if (!empty($link['parent_id'])) {
        $pid = $link['parent_id'];
        if (!isset($hijos[$pid])) $hijos[$pid] = [];
        $hijos[$pid][] = $link;
    }
}

foreach ($todos_links as $link) {
    if (!empty($link['parent_id'])) continue; // Los hijos ya fueron procesados

    if ($link['tipo'] === 'social') {
        $social_links[] = $link;
        continue;
    }
    
    // Si es expandable o folder, le asignamos sus hijos
    if ($link['tipo'] === 'expandable' || $link['tipo'] === 'folder') {
        $link['sub_links'] = $hijos[$link['id']] ?? [];
    }
    
    if ($link['tipo'] === 'header') {
        $grupo_actual++;
        $grupos[$grupo_actual] = ['header' => $link, 'items' => [], 'concerts' => []];
    } else {
        if ($grupo_actual == -1) {
            $grupos[-1][] = $link;
        } else {
            if ($link['tipo'] === 'concert') {
                $grupos[$grupo_actual]['concerts'][] = $link;
            } else {
                $grupos[$grupo_actual]['items'][] = $link;
            }
        }
    }
}

foreach ($grupos[-1] as $item) { $regular_links[] = $item; }
unset($grupos[-1]);

foreach ($grupos as $grupo) {
    $regular_links[] = $grupo['header'];
    foreach ($grupo['items'] as $item) { $regular_links[] = $item; }
    
    if (count($grupo['concerts']) > 0) {
        usort($grupo['concerts'], function($a, $b) {
            $dest_a = isset($a['destacado']) ? (int)$a['destacado'] : 0;
            $dest_b = isset($b['destacado']) ? (int)$b['destacado'] : 0;
            if ($dest_a != $dest_b) return $dest_b - $dest_a;
            
            $time_a = strtotime($a['fecha_concierto'] ?? '2099-12-31');
            $time_b = strtotime($b['fecha_concierto'] ?? '2099-12-31');
            return $time_a - $time_b;
        });
        foreach ($grupo['concerts'] as $concert) { $regular_links[] = $concert; }
    }
}

// CSS Dinámico
$bg_color = $config['color_fondo'];
$bg_secundario = $config['color_fondo_secundario'] ?? '';
$angulo = $config['angulo_gradiente'] ?? 180;
$text_color = $config['color_texto'];
$btn_bg = $config['color_boton'];
$btn_text = $config['color_boton_texto'];

$fuente = $config['fuente_texto'] ?? 'Inter';
$fuente_url = urlencode($fuente);
$estilo_btn = $config['estilo_boton'] ?? 'solid';
$redondeo_btn = $config['redondeo_boton'] ?? 'rounded-xl';
$sombra_btn = $config['sombra_boton'] ?? 'shadow-none';
$forma_img = $config['forma_imagen'] ?? 'rounded-lg';
$url_fondo = $config['url_fondo'] ?? '';

// Lógica de Fondo del Body
$body_bg_css = "background-color: {$bg_color};";
if (!empty($url_fondo)) {
    $body_bg_css = "background-image: url('{$url_fondo}'); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100vh;";
} else if (!empty($config['color_fondo_secundario'])) {
    $gradType = $config['tipo_gradiente'] ?? 'lineal_vertical';
    if ($gradType === 'radial') {
        $body_bg_css = "background: radial-gradient(circle, {$config['color_fondo']}, {$config['color_fondo_secundario']}); min-height: 100vh;";
    } else if ($gradType === 'lineal_horizontal') {
        $body_bg_css = "background: linear-gradient(90deg, {$config['color_fondo']}, {$config['color_fondo_secundario']}); min-height: 100vh;";
    } else if ($gradType === 'lineal_diagonal') {
        $body_bg_css = "background: linear-gradient(135deg, {$config['color_fondo']}, {$config['color_fondo_secundario']}); min-height: 100vh;";
    } else {
        $body_bg_css = "background: linear-gradient(180deg, {$config['color_fondo']}, {$config['color_fondo_secundario']}); min-height: 100vh;";
    }
}

// Lógica de Patrones Decorativos
$pattern_css = "";
if ($config['patron_fondo'] == 'puntos') {
    $pattern_css = "background-image: radial-gradient(rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 20px 20px;";
} else if ($config['patron_fondo'] == 'cuadricula') {
    $pattern_css = "background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 30px 30px;";
} else if ($config['patron_fondo'] == 'topografia') {
    $pattern_css = "background-image: repeating-radial-gradient(circle at 0 0, transparent 0, rgba(255,255,255,0.03) 10px, transparent 11px, transparent 20px);";
} else if ($config['patron_fondo'] == 'rayas_diagonales') {
    $pattern_css = "background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.05) 10px, rgba(255,255,255,0.05) 20px);";
} else if ($config['patron_fondo'] == 'rayas_horizontales') {
    $pattern_css = "background-image: repeating-linear-gradient(0deg, transparent, transparent 10px, rgba(255,255,255,0.05) 10px, rgba(255,255,255,0.05) 20px);";
} else if ($config['patron_fondo'] == 'rayas_verticales') {
    $pattern_css = "background-image: repeating-linear-gradient(90deg, transparent, transparent 10px, rgba(255,255,255,0.05) 10px, rgba(255,255,255,0.05) 20px);";
} else if ($config['patron_fondo'] == 'zigzag') {
    $pattern_css = "background-image: linear-gradient(135deg, rgba(255,255,255,0.05) 25%, transparent 25%), linear-gradient(225deg, rgba(255,255,255,0.05) 25%, transparent 25%), linear-gradient(45deg, rgba(255,255,255,0.05) 25%, transparent 25%), linear-gradient(315deg, rgba(255,255,255,0.05) 25%, transparent 25%); background-position: 15px 0, 15px 0, 0 0, 0 0; background-size: 30px 30px;";
} else if ($config['patron_fondo'] == 'cuadros') {
    $pattern_css = "background-image: conic-gradient(rgba(255,255,255,0.05) 90deg, transparent 90deg 180deg, rgba(255,255,255,0.05) 180deg 270deg, transparent 270deg); background-size: 40px 40px;";
} else if ($config['patron_fondo'] == 'cruz') {
    $pattern_css = "background-image: linear-gradient(90deg, transparent 22px, rgba(255,255,255,0.1) 22px, rgba(255,255,255,0.1) 28px, transparent 28px), linear-gradient(0deg, transparent 22px, rgba(255,255,255,0.1) 22px, rgba(255,255,255,0.1) 28px, transparent 28px); background-size: 50px 50px;";
} else if ($config['patron_fondo'] == 'papel_cuadriculado') {
    $pattern_css = "background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 15px 15px;";
} else if ($config['patron_fondo'] == 'ondas') {
    $pattern_css = "background-image: radial-gradient(circle at 100% 50%, transparent 20%, rgba(255,255,255,0.05) 21%, rgba(255,255,255,0.05) 34%, transparent 35%, transparent), radial-gradient(circle at 0% 50%, transparent 20%, rgba(255,255,255,0.05) 21%, rgba(255,255,255,0.05) 34%, transparent 35%, transparent); background-size: 40px 40px;";
}

// Lógica de Estilo de Botón
$btn_css = "";
if ($estilo_btn == 'solid') {
    $btn_css = "background-color: {$btn_bg}; color: {$btn_text}; border: 2px solid {$btn_bg};";
    $btn_hover = "filter: brightness(1.1); box-shadow: 0 0 15px {$btn_bg}80;";
} else if ($estilo_btn == 'outline') {
    $btn_css = "background-color: transparent; border: 2px solid {$btn_bg}; color: {$btn_bg};";
    $btn_hover = "background-color: {$btn_bg}; color: {$btn_text}; box-shadow: 0 0 15px {$btn_bg}80;";
} else if ($estilo_btn == 'glass') {
    $btn_css = "background-color: {$btn_bg}30; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.4); color: {$btn_text}; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);";
    $btn_hover = "background-color: {$btn_bg}50; border-color: rgba(255,255,255,0.6); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2); transform: translateY(-2px);";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['nombre_sitio']) ?></title>
    
    <!-- SEO & Meta Tags -->
    <?php
    $site_name = htmlspecialchars(!empty($config['seo_titulo']) ? $config['seo_titulo'] : ($config['nombre_sitio'] ?? 'Mi Bio Links'));
    $desc = htmlspecialchars(!empty($config['seo_descripcion']) ? $config['seo_descripcion'] : strip_tags($config['biografia'] ?? 'Descubre todos mis enlaces y eventos aquí.'));
    $logo_url = htmlspecialchars(!empty($config['seo_imagen']) ? $config['seo_imagen'] : ($config['url_logo'] ?? ''));
    $current_url = "https://" . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');
    ?>
    <meta name="description" content="<?= $desc ?>">
    <meta property="og:title" content="<?= $site_name ?>">
    <meta property="og:description" content="<?= $desc ?>">
    <meta property="og:url" content="<?= $current_url ?>">
    <meta property="og:type" content="website">
    <?php if(!empty($logo_url)): ?>
        <meta property="og:image" content="<?= $logo_url ?>">
        <link rel="icon" type="image/png" href="<?= $logo_url ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="<?= $current_url ?>">
    
    <?php if (!empty($config['analytics_id'])): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($config['analytics_id']) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= htmlspecialchars($config['analytics_id']) ?>');
    </script>
    <?php endif; ?>

    <?php if (!empty($config['facebook_pixel'])): ?>
    <!-- Facebook Pixel -->
    <script>
      !function(f,b,e,v,n,t,s)
      {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)}(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '<?= htmlspecialchars($config['facebook_pixel']) ?>');
      fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= htmlspecialchars($config['facebook_pixel']) ?>&ev=PageView&noscript=1"/></noscript>
    <?php endif; ?>
    
    <!-- Fuente Dinámica -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=<?= $fuente_url ?>:wght@400;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: '<?= $fuente ?>', sans-serif; color: <?= $text_color ?>; <?= $body_bg_css ?> margin: 0; padding: 0; }
        .pattern-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; <?= $pattern_css ?> }
        
        .link-card { <?= $btn_css ?> }
        .link-card:hover { <?= $btn_hover ?> }

        .sub-link-card { background-color: <?= $text_color ?> !important; color: <?= $bg_color ?> !important; border: 2px solid <?= $text_color ?> !important; }
        .sub-link-card:hover { filter: brightness(0.9); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] summary ~ * { animation: sweep .3s ease-in-out; }
        @keyframes sweep { 0% {opacity: 0; margin-top: -10px} 100% {opacity: 1; margin-top: 0px} }
        
        .quill-content ul { list-style-type: disc; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
        .quill-content ol { list-style-type: decimal; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
        .quill-content p { margin-bottom: 0.5rem; }
        .quill-content a { text-decoration: underline; }
        
        .social-icon:hover { transform: scale(1.1); color: <?= $btn_bg ?>; filter: drop-shadow(0 0 5px <?= $btn_bg ?>); }
    </style>
</head>
<body class="flex flex-col items-center py-12 px-4 relative min-h-screen">
    <div class="pattern-overlay z-0"></div>
    <div class="w-full max-w-2xl flex flex-col items-center z-10">
        
        <?php if (!empty($config['url_logo'])): ?>
            <img src="<?= htmlspecialchars($config['url_logo']) ?>" alt="Logo" class="w-24 h-24 rounded-full mb-4 object-cover shadow-2xl border-2" style="border-color: <?= $btn_bg ?>">
        <?php endif; ?>
        
        <h1 class="text-2xl md:text-3xl font-bold mb-2 tracking-wide text-center drop-shadow-md">
            <?= htmlspecialchars($config['nombre_sitio']) ?>
        </h1>
        
        <?php if (!empty($config['biografia'])): ?>
            <p class="text-center mb-4 max-w-md opacity-90 text-sm md:text-base leading-relaxed drop-shadow-md font-medium">
                <?= nl2br(htmlspecialchars($config['biografia'])) ?>
            </p>
        <?php endif; ?>

        <?php if (count($social_links) > 0): ?>
            <div class="flex flex-wrap justify-center gap-4 mb-8">
                <?php foreach ($social_links as $slink): ?>
                    <a href="go?id=<?= $slink['id'] ?>" target="_blank" rel="noopener noreferrer" class="text-3xl transition-all duration-300 social-icon drop-shadow-md" title="<?= htmlspecialchars($slink['titulo']) ?>">
                        <?php if (!empty($slink['icono'])): ?>
                            <i class="<?= htmlspecialchars($slink['icono']) ?>"></i>
                        <?php else: ?>
                            <i class="fas fa-link"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="mb-6"></div>
        <?php endif; ?>

        <div class="w-full space-y-4">
            <?php foreach ($regular_links as $link): ?>
                
                <?php if ($link['tipo'] == 'header'): ?>
                    <div class="mt-8 mb-4">
                        <h2 class="text-base font-bold text-center uppercase tracking-[0.2em] opacity-80 border-b pb-2 drop-shadow-md" style="border-color: <?= $text_color ?>40">
                            <?= htmlspecialchars($link['titulo']) ?>
                        </h2>
                    </div>
                
                <?php elseif ($link['tipo'] == 'link'): ?>
                    <a href="go?id=<?= $link['id'] ?>" target="_blank" rel="noopener noreferrer" 
                       class="link-card block w-full py-4 px-4 <?= $redondeo_btn ?> font-semibold transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden flex items-center justify-between">
                        <div class="flex items-center w-full">
                            <?php if (!empty($link['icono'])): ?>
                                <div class="w-10 text-center shrink-0 mr-3 text-xl"><i class="<?= htmlspecialchars($link['icono']) ?>"></i></div>
                            <?php elseif (!empty($link['url_imagen'])): ?>
                                <img src="<?= htmlspecialchars($link['url_imagen']) ?>" class="w-10 h-10 <?= htmlspecialchars($forma_img) ?> object-cover shrink-0 mr-4 border border-white/20">
                            <?php endif; ?>
                            <div class="flex-1 text-center <?php if (!empty($link['icono']) || !empty($link['url_imagen'])) echo '-ml-12 md:-ml-8'; ?>">
                                <div class="text-[17px] font-bold"><?= htmlspecialchars($link['titulo']) ?></div>
                                <?php if (!empty($link['subtitulo'])): ?>
                                    <div class="text-xs opacity-80 mt-0.5 font-normal"><?= htmlspecialchars($link['subtitulo']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute right-4"><i class="fas fa-chevron-right text-sm"></i></div>
                    </a>

                <?php elseif ($link['tipo'] == 'expandable' || $link['tipo'] == 'folder'): ?>
                    <details class="w-full <?= $redondeo_btn ?> transition-all duration-300 overflow-hidden link-card relative group">
                        <summary class="py-4 px-4 font-semibold cursor-pointer flex items-center justify-between hover:bg-white/5 transition relative">
                            <div class="flex items-center w-full">
                                <?php if (!empty($link['icono'])): ?>
                                    <div class="w-10 text-center shrink-0 mr-3 text-xl"><i class="<?= htmlspecialchars($link['icono']) ?>"></i></div>
                                <?php elseif (!empty($link['url_imagen'])): ?>
                                    <img src="<?= htmlspecialchars($link['url_imagen']) ?>" class="w-10 h-10 <?= htmlspecialchars($forma_img) ?> object-cover shrink-0 mr-4 border border-white/20">
                                <?php endif; ?>
                                <div class="flex-1 text-center <?php if (!empty($link['icono']) || !empty($link['url_imagen'])) echo '-ml-12 md:-ml-8'; ?>">
                                    <div class="text-[17px] font-bold"><?= htmlspecialchars($link['titulo']) ?></div>
                                    <?php if (!empty($link['subtitulo'])): ?>
                                        <div class="text-xs opacity-80 mt-0.5 font-normal"><?= htmlspecialchars($link['subtitulo']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <i class="fas fa-chevron-down opacity-60 text-sm absolute right-4 transition-transform group-open:rotate-180"></i>
                        </summary>
                        <div class="p-5 bg-black/30 border-t border-black/10 text-sm flex flex-col gap-3">
                            <?php if ($link['tipo'] === 'expandable'): ?>
                                <?php if (!empty($link['contenido_extra'])): ?>
                                    <div class="mb-2 leading-relaxed text-[15px] quill-content" style="color: <?= $text_color ?>; opacity: 0.9;"><?= $link['contenido_extra'] ?></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($link['url'])): ?>
                                    <a href="go?id=<?= $link['id'] ?>" target="_blank" class="block w-full text-center py-3 <?= $redondeo_btn ?> font-bold transition shadow-lg hover:opacity-90 flex justify-center items-center gap-2 mb-3" style="background-color: <?= $text_color ?>; color: <?= $bg_color ?>">
                                        <span><?= !empty($link['btn_texto']) ? htmlspecialchars($link['btn_texto']) : __('public_go_link') ?></span> <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (isset($link['sub_links']) && count($link['sub_links']) > 0): ?>
                                <div class="w-full space-y-3 mt-2">
                                    <?php foreach ($link['sub_links'] as $sublink): ?>
                                        <a href="go?id=<?= $sublink['id'] ?>" target="_blank" rel="noopener noreferrer" 
                                           class="sub-link-card block w-full py-3 px-4 <?= $redondeo_btn ?> font-semibold transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden flex items-center justify-between" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);">
                                            <div class="flex items-center w-full">
                                                <?php if (!empty($sublink['icono'])): ?>
                                                    <div class="w-8 text-center shrink-0 mr-3 text-lg"><i class="<?= htmlspecialchars($sublink['icono']) ?>"></i></div>
                                                <?php elseif (!empty($sublink['url_imagen'])): ?>
                                                    <img src="<?= htmlspecialchars($sublink['url_imagen']) ?>" class="w-8 h-8 <?= htmlspecialchars($forma_img) ?> object-cover shrink-0 mr-3 border border-white/20">
                                                <?php endif; ?>
                                                <div class="flex-1 text-center <?php if (!empty($sublink['icono']) || !empty($sublink['url_imagen'])) echo '-ml-10 md:-ml-8'; ?>">
                                                    <div class="text-[15px] font-bold"><?= htmlspecialchars($sublink['titulo']) ?></div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>

                <?php elseif ($link['tipo'] == 'concert'): ?>
                    <details class="w-full <?= $redondeo_btn ?> transition-all duration-300 overflow-hidden group relative link-card">
                        <?php if(isset($link['destacado']) && $link['destacado'] == 1): ?>
                            <div class="absolute top-0 right-0 bg-yellow-400 text-black text-[10px] font-bold px-3 py-1 rounded-bl-xl z-10 shadow-sm"><i class="fas fa-star mr-1"></i>DESTACADO</div>
                        <?php endif; ?>
                        
                        <summary class="py-4 px-4 font-semibold cursor-pointer flex items-center justify-between hover:bg-white/5 transition relative">
                            <div class="flex items-center w-full pr-6">
                                <?php if (!empty($link['icono'])): ?>
                                    <div class="w-10 text-center shrink-0 mr-3 text-xl"><i class="<?= htmlspecialchars($link['icono']) ?>"></i></div>
                                <?php elseif (!empty($link['url_imagen'])): ?>
                                    <img src="<?= htmlspecialchars($link['url_imagen']) ?>" class="w-12 h-12 <?= htmlspecialchars($forma_img) ?> object-cover shrink-0 mr-4 shadow-sm">
                                <?php else: ?>
                                    <div class="w-12 h-12 <?= htmlspecialchars($forma_img) ?> bg-black/10 shrink-0 mr-4 flex items-center justify-center text-xl"><i class="fas fa-ticket-alt"></i></div>
                                <?php endif; ?>
                                <div class="flex-1 text-center <?php if (!empty($link['icono']) || !empty($link['url_imagen'])) echo '-ml-12 md:-ml-8'; ?>">
                                    <div class="text-[17px] uppercase tracking-wide font-bold"><?= htmlspecialchars($link['titulo']) ?></div>
                                    <div class="text-xs opacity-80 mt-0.5 font-normal">
                                        <?php if (!empty($link['fecha_concierto'])): ?>
                                            <i class="far fa-calendar-alt mr-1"></i> <?= date('d/m/Y', strtotime($link['fecha_concierto'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <i class="fas fa-chevron-down opacity-60 text-sm absolute right-4 transition-transform group-open:rotate-180"></i>
                        </summary>
                        <div class="p-5 bg-black/30 border-t border-black/10 flex flex-col items-center">
                            
                            <?php if (!empty($link['fecha_concierto'])): ?>
                                <div class="bg-black/20 backdrop-blur-md px-6 py-4 <?= $redondeo_btn ?> text-center border border-white/20 countdown-timer shadow-inner mb-4 w-full max-w-xs" data-date="<?= $link['fecha_concierto'] ?>">
                                    <div class="text-xs uppercase tracking-wider opacity-80 mb-1 font-bold"><?= __('public_time_left') ?></div>
                                    <div class="font-mono text-3xl font-bold countdown-text drop-shadow-md">--:--</div>
                                    <div class="text-xs opacity-60 mt-1"><?= date('H:i \h\r\s', strtotime($link['fecha_concierto'])) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($link['productora'])): ?>
                                <div class="text-sm opacity-90 mb-5 font-semibold"><i class="fas fa-building mr-2"></i><?= htmlspecialchars($link['productora']) ?></div>
                            <?php endif; ?>

                            <a href="go?id=<?= $link['id'] ?>" target="_blank" class="block w-full text-center py-3 <?= $redondeo_btn ?> font-bold transition shadow-lg hover:opacity-90 flex justify-center items-center gap-2" style="background-color: <?= $text_color ?>; color: <?= $bg_color ?>">
                                <span><i class="fas fa-ticket-alt mr-2"></i><?= __('public_tickets') ?></span>
                            </a>
                        </div>
                    </details>

                <?php elseif ($link['tipo'] == 'event'): ?>
                    <details class="w-full <?= $redondeo_btn ?> transition-all duration-300 overflow-hidden group relative link-card">
                        <?php if(isset($link['destacado']) && $link['destacado'] == 1): ?>
                            <div class="absolute top-0 right-0 bg-yellow-400 text-black text-[10px] font-bold px-3 py-1 rounded-bl-xl z-10 shadow-sm"><i class="fas fa-star mr-1"></i>DESTACADO</div>
                        <?php endif; ?>
                        
                        <summary class="py-4 px-4 font-semibold cursor-pointer flex items-center justify-between hover:bg-white/5 transition relative">
                            <div class="flex items-center">
                                <?php if (!empty($link['icono'])): ?>
                                    <div class="w-10 text-center shrink-0 mr-3 text-xl"><i class="<?= htmlspecialchars($link['icono']) ?>"></i></div>
                                <?php elseif (!empty($link['url_imagen'])): ?>
                                    <img src="<?= htmlspecialchars($link['url_imagen']) ?>" class="w-12 h-12 <?= htmlspecialchars($forma_img) ?> object-cover shrink-0 mr-4 shadow-sm">
                                <?php else: ?>
                                    <div class="w-12 h-12 <?= htmlspecialchars($forma_img) ?> bg-black/10 shrink-0 mr-4 flex items-center justify-center text-xl"><i class="fas fa-calendar-alt"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="text-[17px] uppercase tracking-wide font-bold"><?= htmlspecialchars($link['titulo']) ?></div>
                                    <div class="text-xs opacity-80 mt-0.5 font-normal">
                                        <?php if (!empty($link['fecha_concierto'])): ?>
                                            <i class="far fa-calendar-alt mr-1"></i> <?= date('d/m/Y', strtotime($link['fecha_concierto'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <i class="fas fa-chevron-down opacity-60 text-sm transition-transform group-open:rotate-180"></i>
                        </summary>
                        <div class="p-5 bg-black/30 border-t border-black/10 flex flex-col items-center">
                            
                            <?php if (!empty($link['fecha_concierto'])): ?>
                                <div class="bg-black/20 backdrop-blur-md px-6 py-4 <?= $redondeo_btn ?> text-center border border-white/20 countdown-timer shadow-inner mb-4 w-full max-w-xs" data-date="<?= $link['fecha_concierto'] ?>">
                                    <div class="text-xs uppercase tracking-wider opacity-80 mb-1 font-bold"><?= __('public_time_left') ?></div>
                                    <div class="font-mono text-3xl font-bold countdown-text drop-shadow-md">--:--</div>
                                    <div class="text-xs opacity-60 mt-1"><?= date('H:i \h\r\s', strtotime($link['fecha_concierto'])) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($link['productora'])): ?>
                                <div class="text-sm opacity-90 mb-5 font-semibold"><i class="fas fa-building mr-2"></i><?= htmlspecialchars($link['productora']) ?></div>
                            <?php endif; ?>

                            <a href="go?id=<?= $link['id'] ?>" target="_blank" class="block w-full text-center py-3 <?= $redondeo_btn ?> font-bold transition shadow-lg hover:opacity-90 flex justify-center items-center gap-2" style="background-color: <?= $text_color ?>; color: <?= $bg_color ?>">
                                <?php
                                $btn_texto_evento = !empty($link['btn_texto']) ? htmlspecialchars($link['btn_texto']) : __('public_tickets');
                                $btn_icono_evento = !empty($link['btn_icono']) ? htmlspecialchars($link['btn_icono']) : 'fa-ticket-alt';
                                ?>
                                <span><i class="fas <?= $btn_icono_evento ?> mr-2"></i><?= $btn_texto_evento ?></span>
                            </a>
                        </div>
                    </details>

                <?php endif; ?>

            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($config['texto_footer'])): ?>
            <footer id="livePreviewFooter" class="mt-8 pb-8 text-center opacity-70 text-xs font-semibold">
                <?= nl2br(htmlspecialchars($config['texto_footer'])) ?>
            </footer>
        <?php endif; ?>
    </div>

    <script>
        function updateCountdowns() {
            const timers = document.querySelectorAll('.countdown-timer');
            const now = new Date().getTime();

            timers.forEach(timer => {
                const targetDateStr = timer.getAttribute('data-date');
                const targetDate = new Date(targetDateStr.replace(/-/g, "/")).getTime();
                const distance = targetDate - now;
                const textEl = timer.querySelector('.countdown-text');

                if (distance < 0) {
                    textEl.innerHTML = "¡HOY!";
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                
                if (days > 0) {
                    textEl.innerHTML = days + "d " + hours + "h";
                } else {
                    textEl.innerHTML = hours + "h " + minutes + "m";
                }
            });
        }
        setInterval(updateCountdowns, 60000);
        updateCountdowns();
    </script>
</body>
</html>
