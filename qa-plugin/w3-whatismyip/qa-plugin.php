<?php
/*
 Plugin Name: W3Uzman WhatIsMyIP
 Plugin Description: Ziyaretçinin bağlantı IP adresini ve IP sürümünü gösterir.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

qa_register_plugin_module('page', 'ip-page.php', 'w3_whatismyip_page', 'W3Uzman WhatIsMyIP');
