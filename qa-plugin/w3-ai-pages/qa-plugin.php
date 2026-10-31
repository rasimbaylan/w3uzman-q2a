<?php
/*
 Plugin Name: W3Uzman AI Rehber Sayfaları
 Plugin Description: ChatGPT, Gemini, Claude, Midjourney ve GitHub Copilot tanıtımları ve ilgili haberler.
 Plugin Version: 1.0
 Plugin Minimum Question2Answer Version: 1.8
*/
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
qa_register_plugin_module('page', 'ai-pages.php', 'w3_ai_pages', 'W3Uzman Yapay Zekâ Rehberleri');
