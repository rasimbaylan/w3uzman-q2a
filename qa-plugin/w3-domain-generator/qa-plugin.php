<?php
/*
 Plugin Name: W3Uzman Domain Öneri Aracı
 Plugin Description: Anahtar kelime ve sektöre göre alan adı fikirleri üretir.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'generator-page.php', 'w3_domain_generator_page', 'W3Uzman Domain Öneri Aracı');
