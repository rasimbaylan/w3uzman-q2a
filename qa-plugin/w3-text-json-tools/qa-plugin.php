<?php
/*
 Plugin Name: W3Uzman Metin ve JSON Araçları
 Plugin Description: Türkçe metin işlemleri ve veri kaybı olmadan JSON biçimlendirme.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}
qa_register_plugin_module('page', 'tool-page.php', 'w3_text_json_page', 'W3Uzman Metin ve JSON Araçları');
