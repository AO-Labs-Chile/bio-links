<?php
header('Content-Type: text/plain; charset=utf-8');

$url = "https://www.passline.com/eventos/mayohk-world-tour-2026-chile";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_COOKIEFILE, "");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$html = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "=== RESULTADO DE MAYOHK ===\n";
echo "Código HTTP: $http_code\n";
if ($err) {
    echo "Error cURL: $err\n";
} else {
    echo "HTML (primeros 1500 chars):\n";
    echo htmlspecialchars(substr($html, 0, 1500)) . "\n";
}
?>
