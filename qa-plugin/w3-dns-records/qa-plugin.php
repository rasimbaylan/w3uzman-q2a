<?php
/*
 Plugin Name: W3Uzman DNS Kayıt Sorgulama
 Plugin Description: Alan adlarının DNS kayıtlarını açıklamaları ve TTL bilgileriyle sorgular.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'dns-page.php', 'w3_dns_records_page', 'W3Uzman DNS Kayıt Sorgulama');
