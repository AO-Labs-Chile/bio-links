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
return [
    // General
    'csrf_error' => 'Error de seguridad (CSRF). Por favor recarga la página.',
    'save' => 'Guardar',
    'cancel' => 'Cancelar',
    'delete' => 'Eliminar',
    'edit' => 'Editar',
    'yes' => 'Sí',
    'no' => 'No',
    'optional' => 'Opcional',
    
    // Login & Password
    'login_title' => 'Iniciar Sesión',
    'login_username' => 'Usuario',
    'login_password' => 'Contraseña',
    'login_btn' => 'Ingresar',
    'login_google' => 'Continuar con Google',
    'login_forgot' => '¿Olvidaste tu contraseña?',
    'login_go_to_site' => 'Ir a Links',
    'login_error' => 'Usuario, correo o contraseña incorrectos.',
    'pass_reset_title' => 'Recuperar Contraseña',
    'pass_reset_desc' => 'Ingresa tu correo para recibir un enlace.',
    'pass_reset_email' => 'Correo Electrónico',
    'pass_reset_btn' => 'Enviar enlace',
    'pass_reset_back' => 'Volver al Login',
    'pass_reset_success' => 'Si el correo existe, hemos enviado un enlace de recuperación.',
    
    // Admin Nav
    'nav_links' => 'Links',
    'nav_design' => 'Diseño',
    'nav_view_site' => 'Ver Sitio',
    'nav_logout' => 'Salir',
    
    // Admin Links
    'admin_add_new' => 'Agregar Nuevo Enlace',
    'admin_social_title' => 'Redes Sociales (Barra Superior)',
    'admin_social_empty' => 'No tienes botones sociales. Agrega uno con el botón de arriba.',
    'admin_links_title' => 'Enlaces y Contenido',
    'admin_links_empty' => 'No tienes enlaces regulares.',
    'admin_clicks' => 'clics',
    'admin_event' => 'Evento',
    'admin_child_of' => 'Hijo de:',
    'admin_confirm_delete' => '¿Borrar?',
    
    // Admin Modal
    'modal_add_title' => 'Agregar Elemento',
    'modal_edit_title' => 'Editar Elemento',
    'modal_type' => 'Tipo',
    'type_link' => 'Enlace Normal',
    'type_social' => 'Botón Red Social (Arriba)',
    'type_header' => 'Encabezado Separador',
    'type_folder' => 'Acordeón Desplegable (Carpeta Padre)',
    'type_concert' => 'Evento / Concierto',
    'modal_parent_folder' => 'Carpeta Padre (Opcional)',
    'modal_parent_none' => '-- Ninguna (Enlace Principal) --',
    'modal_title_text' => 'Título / Texto',
    'modal_url' => 'URL Destino',
    'modal_icon' => 'Icono (FontAwesome)',
    'modal_thumb' => 'Miniatura (Subir o Extraer de URL)',
    'modal_upload' => 'Subir Archivo',
    'modal_or' => 'O',
    'modal_fetch_url' => 'Extraer de URL',
    'modal_subtitle' => 'Subtítulo',
    'modal_folder_text' => 'Texto Desplegable',
    'modal_date' => 'Fecha y Hora Concierto',
    'modal_producer' => 'Productora',
    'modal_highlight' => 'Destacar (Fijar arriba)',
    'modal_save_btn' => 'Guardar Elemento',
    'msg_saved' => 'Elemento guardado correctamente.',
    'msg_deleted' => 'Elemento eliminado.',
    
    // Settings
    'settings_title' => 'Diseño y Perfil',
    'settings_design_tab' => 'Diseño y Apariencia',
    'settings_bg_color' => 'Color de Fondo',
    'settings_bg_color2' => 'Color Secundario (Gradiente)',
    'settings_bg_type' => 'Tipo de Gradiente',
    'grad_linear_v' => 'Lineal Vertical',
    'grad_linear_h' => 'Lineal Horizontal',
    'grad_radial' => 'Radial',
    'settings_bg_image' => 'URL Imagen de Fondo',
    'settings_text_color' => 'Color del Texto',
    'settings_btn_color' => 'Color de Botones',
    'settings_btn_text' => 'Color Texto Botón',
    'settings_btn_style' => 'Estilo Botones',
    'style_solid' => 'Sólido',
    'style_outline' => 'Bordeado',
    'style_glass' => 'Cristal (Transparente)',
    'settings_btn_radius' => 'Redondeo Botones',
    'radius_square' => 'Cuadrado',
    'radius_rounded' => 'Redondeado',
    'radius_pill' => 'Píldora',
    'settings_font' => 'Fuente del Texto',
    'settings_save_design' => 'Guardar Configuración',
    'settings_account_tab' => 'Cuenta y Seguridad',
    'settings_user' => 'Usuario Administrador',
    'settings_email' => 'Correo Electrónico',
    'settings_pass' => 'Nueva Contraseña (dejar en blanco para no cambiar)',
    'settings_save_account' => 'Actualizar Cuenta',
    'settings_connections' => 'Conexiones',
    'google_connected' => 'Conectado con Google',
    'google_linked' => 'Vinculado',
    'google_unlink' => 'Desvincular',
    'google_not_connected' => 'No conectado',
    'google_not_linked' => 'No vinculado',
    'google_link' => 'Vincular Gmail',
    
    // Index (Public)
    'public_go_link' => 'Ir al enlace',
    'public_tickets' => 'Tickets / Más Info',
    'public_tickets_short' => 'Tickets',
    'public_time_left' => 'Faltan',
    'public_no_links' => 'Sin enlaces',
    
    // Install
    'install_title' => 'Instalación de Links',
    'install_step1' => 'Configuración de Base de Datos',
    'install_db_host' => 'Host (ej. localhost)',
    'install_db_name' => 'Nombre de la Base de Datos',
    'install_db_user' => 'Usuario de BD',
    'install_db_pass' => 'Contraseña de BD',
    'install_step2' => 'Cuenta Administrador',
    'install_admin_user' => 'Usuario Administrador',
    'install_admin_pass' => 'Contraseña',
    'install_admin_email' => 'Correo Electrónico (para recuperar pass)',
    'install_step3' => 'Configuración de Correos SMTP (Opcional)',
    'install_smtp_host' => 'Servidor SMTP (ej. smtp.gmail.com)',
    'install_smtp_port' => 'Puerto SMTP (ej. 465 o 587)',
    'install_smtp_user' => 'Usuario / Correo SMTP',
    'install_smtp_pass' => 'Contraseña SMTP',
    'install_step4' => 'Inicio de Sesión con Google (Opcional)',
    'install_google_client' => 'Client ID de Google',
    'install_google_secret' => 'Client Secret de Google',
    'install_btn' => 'Comenzar Instalación',
    'install_success' => '¡Instalación exitosa!',
    'install_go_admin' => 'Ir al Panel de Administración'
];
