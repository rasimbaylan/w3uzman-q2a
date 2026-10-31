<?php
/*
 Plugin Name: W3Uzman QR Kod Oluşturucu
 Plugin Description: Bağlantı, Wi-Fi, WhatsApp ve kişi kartı için tarayıcıda QR kod üretir.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}
qa_register_plugin_module('page', 'qr-page.php', 'w3_qr_code_page', 'W3Uzman QR Kod Oluşturucu');
