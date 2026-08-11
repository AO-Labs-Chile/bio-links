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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Aumentar clics
    $stmt = $pdo->prepare("UPDATE links SET clics = clics + 1 WHERE id = ?");
    $stmt->execute([$id]);

    // Obtener URL
    $stmt = $pdo->prepare("SELECT url FROM links WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $link = $stmt->fetch();

    if ($link && !empty($link['url'])) {
        header("Location: " . $link['url']);
        exit;
    }
}

// Si falla, volver al index
header("Location: index");
exit;
?>
