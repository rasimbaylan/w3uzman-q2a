<?php
/*
 Plugin Name: W3Uzman Araçlar
 Plugin Description: W3Uzman araçlarını ikonlu ve filtrelenebilir bir sayfada listeler.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'tools-page.php', 'w3_tools_directory_page', 'W3Uzman Araçlar');
