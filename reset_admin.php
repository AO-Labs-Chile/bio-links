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
    // Generate a fresh hash for admin123
    $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Clear existing admins
    $pdo->exec("TRUNCATE TABLE admin");
    
    // Insert new admin
    $stmt = $pdo->prepare("INSERT INTO admin (usuario, password_hash) VALUES ('admin', ?)");
    $stmt->execute([$new_hash]);
    
    echo "¡Administrador restablecido correctamente!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
