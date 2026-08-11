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
 header("Content-Type: application/xml; charset=utf-8"); ?>
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
  // Determinar la URL base dinámicamente
  $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
  $domain = $_SERVER['HTTP_HOST'];
  $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  $base_url = $protocol . "://" . $domain . $path . "/";
?>
  <url>
    <loc><?= htmlspecialchars($base_url) ?></loc>
    <lastmod><?= date('Y-m-d') ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
