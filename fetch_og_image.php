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
// fetch_og_image.php
header('Content-Type: application/json');

// Permitir solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$url = $_POST['url'] ?? '';

// Decodificar Base64 si viene codificado (para evadir reglas SSRF de WAF/ModSecurity)
if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
    $decoded = @base64_decode($url, true);
    if ($decoded !== false && filter_var($decoded, FILTER_VALIDATE_URL)) {
        $url = $decoded;
    }
}

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'URL inválida']);
    exit;
}

// Función híbrida para descargar HTML (más compatible)
function fetch_html($url) {
    // Intento 1: cURL (con cabeceras completas y cookies en memoria para evitar bucles de salas de espera)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
        
        // Habilitar el motor de cookies en memoria para saltar protecciones tipo Queue-it
        curl_setopt($ch, CURLOPT_COOKIEFILE, "");
        
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($html !== false && !empty($html) && $http_code === 200) {
            return $html;
        }
    }
    
    // Intento 2: file_get_contents (si cURL fallara o no estuviera, como respaldo básico)
    if (ini_get('allow_url_fopen')) {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36\r\n" .
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n" .
                            "Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3\r\n" .
                            "Connection: close\r\n",
                'timeout' => 8
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
        $context = stream_context_create($options);
        $html = @file_get_contents($url, false, $context);
        if ($html !== false && !empty($html)) {
            return $html;
        }
    }
    
    return false;
}

$html = fetch_html($url);

if ($html === false) {
    echo json_encode(['error' => 'No se pudo acceder a la URL (Verifica la conexión o el bloqueo del sitio)']);
    exit;
}

// Buscar og:image y cualquier otra imagen
$images = [];

// 1. og:image
if (preg_match_all('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
    foreach ($matches[1] as $img) {
        if (!empty($img)) $images[] = $img;
    }
}

// 2. twitter:image
if (preg_match_all('/<meta[^>]*name=["\']twitter:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
    foreach ($matches[1] as $img) {
        if (!empty($img)) $images[] = $img;
    }
}

// 3. Imágenes de etiqueta img
if (preg_match_all('/<img[^>]*src=["\']([^"\']+)["\']/i', $html, $matches)) {
    foreach ($matches[1] as $img) {
        if (!empty($img) && strpos($img, 'spacer') === false && strpos($img, 'pixel') === false && strpos($img, 'tracker') === false) {
            $images[] = $img;
        }
    }
}

// Limpiar y validar URLs
$valid_images = [];
$seen = [];
foreach ($images as $image_url) {
    $image_url = trim($image_url);
    if (empty($image_url)) continue;
    
    if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
        $parsed_url = parse_url($url);
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        
        if (strpos($image_url, '//') === 0) {
            $image_url = $parsed_url['scheme'] . ':' . $image_url;
        } elseif (strpos($image_url, '/') === 0) {
            $image_url = $base_url . $image_url;
        } else {
            $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
            $dir = dirname($path);
            if ($dir == '\\' || $dir == '/') $dir = '';
            $image_url = $base_url . $dir . '/' . $image_url;
        }
    }
    
    if (filter_var($image_url, FILTER_VALIDATE_URL)) {
        if (!in_array($image_url, $seen)) {
            $seen[] = $image_url;
            $valid_images[] = $image_url;
        }
    }
}

// Limitar a las mejores 15 imágenes
$valid_images = array_slice($valid_images, 0, 15);

if (!empty($valid_images)) {
    echo json_encode([
        'success' => true, 
        'image' => $valid_images[0], // Retrocompatibilidad para JS en caché
        'images' => $valid_images
    ]);
} else {
    echo json_encode(['error' => 'No se encontraron imágenes elegibles en la URL']);
}
?>
