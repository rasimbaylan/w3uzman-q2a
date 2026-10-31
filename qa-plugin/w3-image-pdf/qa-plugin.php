<?php
/*
 Plugin Name: W3Uzman Resimden PDF
 Plugin Description: JPG ve PNG görsellerinden, dosyaları sunucuya göndermeden PDF oluşturur.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'pdf-page.php', 'w3_image_pdf_page', 'W3Uzman Resimden PDF');
