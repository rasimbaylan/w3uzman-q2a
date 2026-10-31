<?php
/*
 Plugin Name: W3Uzman SSL Sertifika Kontrolü
 Plugin Description: HTTPS sertifikalarının tarihlerini, alan adı uyumunu ve TLS doğrulamasını kontrol eder.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'ssl-page.php', 'w3_ssl_check_page', 'W3Uzman SSL Sertifika Kontrolü');
