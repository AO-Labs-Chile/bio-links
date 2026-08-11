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
if (file_exists('../config.php')) {
    header("Location: ../index.php");
    exit;
}

$error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador del Sistema</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --border-color: #334155;
            --error-bg: #ef444420;
            --error-text: #ef4444;
            --input-bg: #0f172a;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(168, 85, 247, 0.15), transparent 25%);
        }

        .container {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 500px;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(to right, #60a5fa, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            margin: 10px 0 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
            color: #e2e8f0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-main);
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .error-message {
            background-color: var(--error-bg);
            color: var(--error-text);
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            text-align: center;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .footer-credits {
            text-align: center;
            margin-top: 30px;
            font-size: 13px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }

        .footer-credits a {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-credits a:hover {
            color: var(--primary-hover);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Instalación del Sistema</h1>
        <p>Configura tu base de datos y administrador para empezar.</p>
    </div>

    <?php if ($error): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="process.php" method="POST">
        <div class="section-title">Base de Datos</div>
        
        <div class="form-group">
            <label for="db_host">Servidor (Host)</label>
            <input type="text" id="db_host" name="db_host" value="localhost" required placeholder="ej. localhost">
        </div>

        <div class="form-group">
            <label for="db_name">Nombre de la Base de Datos</label>
            <input type="text" id="db_name" name="db_name" required placeholder="ej. Bio Links_db">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="db_user">Usuario DB</label>
                <input type="text" id="db_user" name="db_user" required placeholder="ej. root">
            </div>

            <div class="form-group">
                <label for="db_pass">Contraseña DB</label>
                <input type="password" id="db_pass" name="db_pass" placeholder="Contraseña de DB">
            </div>
        </div>

        <div class="section-title">Cuenta Administrador</div>

        <div class="form-group">
            <label for="admin_user">Usuario Administrador</label>
            <input type="text" id="admin_user" name="admin_user" required placeholder="ej. admin">
        </div>

        <div class="form-group">
            <label for="admin_pass">Contraseña Administrador</label>
            <input type="password" id="admin_pass" name="admin_pass" required placeholder="Ingresa una contraseña segura">
        </div>

        <div class="section-title">Configuración de Correos SMTP (Opcional)</div>
        <p style="font-size: 12px; color: var(--text-muted); margin-top: -10px; margin-bottom: 15px;">Necesario para que funcione la recuperación de contraseñas.</p>

        <div class="grid-2">
            <div class="form-group">
                <label for="smtp_host">Servidor SMTP</label>
                <input type="text" id="smtp_host" name="smtp_host" placeholder="ej. mail.midominio.com">
            </div>
            <div class="form-group">
                <label for="smtp_port">Puerto SMTP</label>
                <input type="text" id="smtp_port" name="smtp_port" placeholder="ej. 465">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label for="smtp_user">Usuario SMTP</label>
                <input type="text" id="smtp_user" name="smtp_user" placeholder="Correo electrónico">
            </div>
            <div class="form-group">
                <label for="smtp_pass">Contraseña SMTP</label>
                <input type="password" id="smtp_pass" name="smtp_pass" placeholder="Contraseña de correo">
            </div>
        </div>

        <div class="section-title">Inicio de Sesión con Google (Opcional)</div>
        <p style="font-size: 12px; color: var(--text-muted); margin-top: -10px; margin-bottom: 15px;">Necesario para permitir acceso directo con Google.</p>

        <div class="form-group">
            <label for="google_client_id">Google Client ID</label>
            <input type="text" id="google_client_id" name="google_client_id" placeholder="Client ID de Google Cloud Console">
        </div>
        <div class="form-group">
            <label for="google_client_secret">Google Client Secret</label>
            <input type="password" id="google_client_secret" name="google_client_secret" placeholder="Client Secret">
        </div>

        <button type="submit" class="btn-submit">Instalar Ahora</button>
    </form>
    
    <div class="footer-credits">
        Desarrollado por <strong>AO Labs Chile</strong><br>
        Contacto: <a href="mailto:contacto@aolabs.cl">contacto@aolabs.cl</a>
    </div>
</div>

</body>
</html>
