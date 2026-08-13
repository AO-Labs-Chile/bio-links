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

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'URL inválida']);
    exit;
}

// Obtener el HTML usando cURL (más robusto que file_get_contents y compatible con hostings que bloquean allow_url_fopen)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$html = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($html === false || $http_code !== 200) {
    echo json_encode(['error' => 'No se pudo acceder a la URL (HTTP ' . $http_code . ')']);
    exit;
}

// Buscar og:image usando expresiones regulares
$image_url = '';

// Intento 1: og:image
if (preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
    $image_url = $matches[1];
} 
// Intento 2: twitter:image
elseif (preg_match('/<meta[^>]*name=["\']twitter:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
    $image_url = $matches[1];
} 
// Intento 3: la primera imagen con un tamaño decente (heurística básica)
elseif (preg_match('/<img[^>]*src=["\']([^"\']+)["\']/i', $html, $matches)) {
    $image_url = $matches[1];
}

// Si la URL es relativa, intentar hacerla absoluta
if (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
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

if (!empty($image_url)) {
    echo json_encode(['success' => true, 'image' => $image_url]);
} else {
    echo json_encode(['error' => 'No se encontró imagen en la URL']);
}
?>
