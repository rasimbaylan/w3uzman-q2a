<?php
/*
 Plugin Name: W3Uzman Whois
 Plugin Description: Alan adı kayıt bilgilerini RDAP ve TRABIS üzerinden sorgular.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'w3-whois-page.php', 'w3_whois_page', 'W3Uzman Whois');
