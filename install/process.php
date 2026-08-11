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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

if (file_exists('../config.php')) {
    die("El sistema ya está instalado.");
}

$db_host = $_POST['db_host'] ?? '';
$db_name = $_POST['db_name'] ?? '';
$db_user = $_POST['db_user'] ?? '';
$db_pass = $_POST['db_pass'] ?? '';

$admin_user = $_POST['admin_user'] ?? '';
$admin_pass = $_POST['admin_pass'] ?? '';

// Opcionales
$smtp_host = $_POST['smtp_host'] ?? '';
$smtp_port = $_POST['smtp_port'] ?? '';
$smtp_user = $_POST['smtp_user'] ?? '';
$smtp_pass = $_POST['smtp_pass'] ?? '';

$google_client_id = $_POST['google_client_id'] ?? '';
$google_client_secret = $_POST['google_client_secret'] ?? '';

if (!$db_host || !$db_name || !$db_user || !$admin_user || !$admin_pass) {
    header("Location: index.php?error=" . urlencode("Todos los campos obligatorios deben ser llenados."));
    exit;
}

try {
    // Probar conexión a base de datos
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Leer el archivo SQL
    $sql_file = '../database.sql';
    if (!file_exists($sql_file)) {
        header("Location: index.php?error=" . urlencode("No se encuentra database.sql en la raíz."));
        exit;
    }
    
    $sql = file_get_contents($sql_file);
    
    // Ejecutar el SQL (crear tablas por defecto)
    $pdo->exec($sql);
    
    // Eliminar el admin por defecto que pudiera haber insertado el database.sql (si lo hubiera, aunque es más seguro vaciar la tabla)
    $pdo->exec("TRUNCATE TABLE admin");
    
    // Insertar el nuevo admin
    $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admin (usuario, email, password_hash) VALUES (:usuario, :email, :hash)");
    $stmt->execute([
        ':usuario' => $admin_user,
        ':email' => $admin_email,
        ':hash' => $hash
    ]);
    
    // Crear el archivo config.php
    $config_content = "<?php\n";
    $config_content .= "// Generado por el Instalador\n";
    $config_content .= "define('DB_HOST', " . var_export($db_host, true) . ");\n";
    $config_content .= "define('DB_NAME', " . var_export($db_name, true) . ");\n";
    $config_content .= "define('DB_USER', " . var_export($db_user, true) . ");\n";
    $config_content .= "define('DB_PASS', " . var_export($db_pass, true) . ");\n\n";

    $config_content .= "// SMTP (Opcional)\n";
    $config_content .= "define('SMTP_HOST', " . var_export($smtp_host, true) . ");\n";
    $config_content .= "define('SMTP_PORT', " . var_export($smtp_port, true) . ");\n";
    $config_content .= "define('SMTP_USER', " . var_export($smtp_user, true) . ");\n";
    $config_content .= "define('SMTP_PASS', " . var_export($smtp_pass, true) . ");\n\n";

    $config_content .= "// Google Auth (Opcional)\n";
    $config_content .= "define('GOOGLE_CLIENT_ID', " . var_export($google_client_id, true) . ");\n";
    $config_content .= "define('GOOGLE_CLIENT_SECRET', " . var_export($google_client_secret, true) . ");\n";
    
    $config_content .= "?>";
    
    $result = file_put_contents('../config.php', $config_content);
    
    if ($result === false) {
        header("Location: index.php?error=" . urlencode("No se pudo escribir el archivo config.php. Revisa los permisos de escritura."));
        exit;
    }
    
    // Generar robots.txt dinámicamente
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $path = dirname(dirname($_SERVER['REQUEST_URI']));
    if ($path === '/' || $path === '\\') {
        $path = '';
    }
    $base_url = rtrim($protocol . $domainName . $path, '/');
    
    $robots_content = "User-agent: *\nAllow: /\n\nSitemap: " . $base_url . "/sitemap.php\n";
    file_put_contents('../robots.txt', $robots_content);
    
    // Mostrar éxito
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Instalación Completada</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .container { background-color: #1e293b; padding: 40px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); text-align: center; max-width: 500px; border: 1px solid #334155; }
            .icon { font-size: 64px; color: #10b981; margin-bottom: 20px; }
            h1 { margin: 0 0 10px 0; font-size: 24px; }
            p { color: #94a3b8; margin-bottom: 25px; line-height: 1.5; }
            .alert { background-color: #f59e0b20; border: 1px solid #f59e0b50; color: #fbbf24; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; text-align: left; }
            .btn { display: inline-block; background-color: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.2s; }
            .btn:hover { background-color: #2563eb; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">✓</div>
            <h1>¡Instalación Exitosa!</h1>
            <p>El sistema se ha configurado correctamente. Ya puedes comenzar a utilizarlo.</p>
            
            <div class="alert">
                <strong>⚠️ Por seguridad:</strong> Es muy recomendable que elimines la carpeta <code>install/</code> de tu servidor para evitar que otra persona vuelva a ejecutar este proceso.
            </div>
            
            <a href="../login.php" class="btn">Ir al Panel de Administración</a>
            <a href="../index.php" class="btn" style="background-color: #475569; margin-left: 10px;">Ver mi Perfil</a>
        </div>
    </body>
    </html>
    <?php
    
} catch (PDOException $e) {
    header("Location: index.php?error=" . urlencode("Error de Base de Datos: " . $e->getMessage()));
    exit;
}
?>
