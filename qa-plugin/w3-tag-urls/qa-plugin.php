<?php
/*
 Plugin Name: W3Uzman Turkish Tag URLs
 Plugin Description: Türkçe etiket, iletişim ve haber sıralama adresleri; eski adreslere kalıcı yönlendirme.
 Plugin Version: 1.1
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

// Q2A loads this before request parsing, including sitemap and AJAX requests.
qa_register_plugin_overrides('w3-tag-url-overrides.php');
