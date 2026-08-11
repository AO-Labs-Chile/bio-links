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
$url = 'https://japanrevolution.cl'; // Just an example
if (isset($argv[1])) $url = $argv[1];

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
$html = @file_get_contents($url, false, $context);

echo "HTML length: " . strlen($html) . "\n";

$images_found = [];
if (preg_match_all('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
    foreach ($matches[1] as $img) $images_found[] = $img;
}
if (preg_match_all('/<meta[^>]*content=["\']([^"\']+)["\'][^>]*property=["\']og:image["\']/i', $html, $matches)) {
    foreach ($matches[1] as $img) $images_found[] = $img;
}
if (preg_match_all('/<meta[^>]*name=["\']twitter:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
    foreach ($matches[1] as $img) $images_found[] = $img;
}
if (preg_match_all('/<img[^>]*src=["\']([^"\']+)["\']/i', $html, $matches)) {
    foreach ($matches[1] as $img) $images_found[] = $img;
}

print_r($images_found);
?>
