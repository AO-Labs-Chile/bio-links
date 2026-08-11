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

// Configurar opciones de contexto para simular un navegador
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n" .
                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n" .
                    "Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
];

$context = stream_context_create($options);

// Obtener el HTML
$html = @file_get_contents($url, false, $context);

if ($html === false) {
    echo json_encode(['error' => 'No se pudo acceder a la URL']);
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
