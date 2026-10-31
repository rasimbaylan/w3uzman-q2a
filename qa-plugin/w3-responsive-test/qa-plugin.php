<?php
/*
 Plugin Name: W3Uzman Responsive Kontrol
 Plugin Description: Web sitelerini farklı görüntü alanı ölçülerinde önizler.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

qa_register_plugin_module('page', 'responsive-page.php', 'w3_responsive_page', 'W3Uzman Responsive Kontrol');
