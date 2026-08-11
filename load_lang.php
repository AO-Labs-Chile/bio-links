<?php
// load_lang.php
// Asume que $config ha sido cargado desde la base de datos
$lang_code = isset($config['idioma']) && $config['idioma'] === 'en' ? 'en' : 'es';
$lang_file = __DIR__ . "/lang/{$lang_code}.php";
$lang = file_exists($lang_file) ? require $lang_file : [];

if (!function_exists('__')) {
    function __($key) {
        global $lang;
        return isset($lang[$key]) ? htmlspecialchars($lang[$key]) : $key;
    }
}
?>
