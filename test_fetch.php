<?php
header('Content-Type: text/plain; charset=utf-8');

$url = "https://www.passline.com/eventos/mayohk-world-tour-2026-chile";

echo "=== PRUEBA DE DEPURACIÓN DE FETCH ===\n\n";
echo "URL Objetivo: $url\n\n";

// 1. Probar file_get_contents
echo "--- 1. PROBANDO file_get_contents ---\n";
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
    
    if ($html !== false) {
        echo "¡Éxito! Tamaño del HTML obtenido: " . strlen($html) . " bytes.\n";
        echo "Primeros 500 caracteres:\n";
        echo substr(strip_tags($html), 0, 500) . "\n";
    } else {
        echo "file_get_contents falló. Error: " . error_get_last()['message'] . "\n";
    }
} else {
    echo "allow_url_fopen está desactivado.\n";
}

echo "\n--- 2. PROBANDO cURL ---\n";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
    
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($html !== false) {
        echo "¡Éxito cURL! Código HTTP: $http_code. Tamaño: " . strlen($html) . " bytes.\n";
        echo "Primeros 500 caracteres del HTML de cURL:\n";
        echo substr(strip_tags($html), 0, 500) . "\n";
    } else {
        echo "cURL falló. Error: $err\n";
    }
} else {
    echo "cURL no está disponible en este servidor.\n";
}

echo "\n=== FIN ===";
?>
