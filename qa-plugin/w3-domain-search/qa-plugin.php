<?php
/*
 Plugin Name: W3Uzman Domain Sorgulama
 Plugin Description: Bir adı 10 uzantıda kontrol eder; kayıtlı alan adlarını Whois aracına bağlar.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'domain-page.php', 'w3_domain_search_page', 'W3Uzman Domain Sorgulama');
