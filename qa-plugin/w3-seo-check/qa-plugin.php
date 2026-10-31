<?php
/*
 Plugin Name: W3Uzman Sayfa SEO Kontrolü
 Plugin Description: Genel web sayfalarının HTML, canonical, robots ve sosyal paylaşım etiketlerini inceler.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'seo-page.php', 'w3_seo_check_page', 'W3Uzman Sayfa SEO Kontrolü');
