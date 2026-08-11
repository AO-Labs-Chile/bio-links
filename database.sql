-- Script SQL consolidado para el clon de Bio Links (Versión Final)

-- Tabla de configuración del perfil
DROP TABLE IF EXISTS config;
CREATE TABLE IF NOT EXISTS config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_sitio VARCHAR(255) NOT NULL,
    biografia TEXT,
    url_logo VARCHAR(255),
    color_fondo VARCHAR(20) DEFAULT '#4c1d95',
    color_boton VARCHAR(20) DEFAULT '#4ade80',
    color_boton_texto VARCHAR(20) DEFAULT '#111827',
    color_texto VARCHAR(20) DEFAULT '#f3f4f6',
    patron_fondo VARCHAR(50) DEFAULT 'ninguno',
    color_fondo_secundario VARCHAR(20) DEFAULT '',
    angulo_gradiente INT DEFAULT 180,
    tipo_gradiente VARCHAR(30) DEFAULT 'lineal_vertical',
    seo_titulo VARCHAR(255) DEFAULT '',
    seo_descripcion TEXT,
    seo_imagen VARCHAR(255) DEFAULT '',
    analytics_id VARCHAR(50) DEFAULT '',
    facebook_pixel VARCHAR(50) DEFAULT '',
    fuente_texto VARCHAR(50) DEFAULT 'Inter',
    estilo_boton VARCHAR(20) DEFAULT 'solid',
    redondeo_boton VARCHAR(20) DEFAULT 'rounded-xl',
    sombra_boton VARCHAR(20) DEFAULT 'shadow-none',
    forma_imagen VARCHAR(20) DEFAULT 'rounded-lg',
    url_fondo VARCHAR(255) DEFAULT '',
    texto_footer TEXT,
    idioma VARCHAR(10) DEFAULT 'es'
);

-- Insertar configuración inicial por defecto
INSERT INTO config (nombre_sitio, biografia, url_logo) 
VALUES ('Mi Bio Links', 'Bienvenido a mi perfil de enlaces.', 'assets/avatar.png');

-- Tabla de enlaces (Botones)
DROP TABLE IF EXISTS links;
CREATE TABLE IF NOT EXISTS links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    orden INT DEFAULT 0,
    estado ENUM('visible', 'oculto') DEFAULT 'visible',
    tipo ENUM('link', 'header', 'expandable', 'folder', 'concert', 'social', 'event') DEFAULT 'link',
    contenido_extra TEXT,
    url_imagen VARCHAR(255),
    subtitulo VARCHAR(255),
    fecha_concierto DATETIME,
    productora VARCHAR(255),
    btn_texto VARCHAR(255),
    btn_icono VARCHAR(50) DEFAULT '',
    clics INT DEFAULT 0,
    icono VARCHAR(50) DEFAULT '',
    destacado TINYINT(1) DEFAULT 0,
    parent_id INT DEFAULT NULL
);

-- Insertar un enlace de ejemplo
INSERT INTO links (titulo, url, orden, estado, tipo) 
VALUES ('Bio Links Oficial', 'https://japanrevolution.cl', 1, 'visible', 'link');

-- Tabla de administradores
DROP TABLE IF EXISTS admin;
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    google_id VARCHAR(255) DEFAULT NULL
);
