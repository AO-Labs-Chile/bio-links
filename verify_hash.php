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
$hash = '$2y$10$EwR2P4V4Kzj9lJ4P0C.gT.T0l1hJ3IuF0C.wQ/kGk/c5wY.w1P.dC';
if (password_verify('admin123', $hash)) {
    echo "Hash matches admin123\n";
} else {
    echo "Hash does NOT match admin123\n";
}
