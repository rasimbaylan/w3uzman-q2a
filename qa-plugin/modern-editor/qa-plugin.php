<?php
/*
    Plugin Name: Modern Editor
    Plugin Description: Quill 2 rich text editor for Question2Answer
    Plugin Version: 1.0.0
    Plugin Author: W3 Uzman
    Plugin License: GPLv2
    Plugin Minimum Question2Answer Version: 1.8
*/

if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

qa_register_plugin_module('editor', 'qa-modern-editor.php', 'qa_modern_editor', 'Modern Editor');
qa_register_plugin_module('page', 'qa-modern-upload.php', 'qa_modern_upload', 'Modern Editor Upload');

