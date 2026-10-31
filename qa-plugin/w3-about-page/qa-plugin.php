<?php
/*
 Plugin Name: W3Uzman About Page
 Plugin Description: Hakkımızda sayfasını dosyadan sunar; özel sayfa varsa Q2A onu kullanır.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

qa_register_plugin_module('page', 'about-page.php', 'w3_about_page', 'W3Uzman Hakkımızda');
