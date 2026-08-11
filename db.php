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
// Cargar configuración generada por el instalador
$config_file = __DIR__ . '/config.php';

if (!file_exists($config_file)) {
    // Si no existe config.php, el sistema no está instalado
    header("Location: install/index");
    exit;
}

require_once $config_file;

try {
    // Crear una nueva conexión PDO usando las constantes de config.php
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    // Configurar PDO para que lance excepciones en caso de error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configurar el modo de obtención de datos por defecto a un array asociativo
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Cargar configuración global e idioma
    try {
        $stmt_lang = $pdo->query("SELECT idioma FROM config LIMIT 1");
        $idioma_db = $stmt_lang->fetchColumn();
    } catch (Exception $e) {
        $idioma_db = 'es';
    }
    
    $lang_code = ($idioma_db === 'en') ? 'en' : 'es';
    $lang_file = __DIR__ . "/lang/{$lang_code}.php";
    $lang = file_exists($lang_file) ? require $lang_file : [];
    
    if (!function_exists('__')) {
        function __($key) {
            global $lang;
            return isset($lang[$key]) ? htmlspecialchars($lang[$key]) : $key;
        }
    }

} catch (PDOException $e) {
    // Si hay un error, mostrarlo y detener la ejecución
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
