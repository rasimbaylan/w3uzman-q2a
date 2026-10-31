<?php
/*
 Plugin Name: W3Uzman Information Pages
 Plugin Description: Gizlilik, kullanım şartları ve çerez bilgilendirme sayfaları.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'info-pages.php', 'w3_info_pages', 'W3Uzman Bilgilendirme');
