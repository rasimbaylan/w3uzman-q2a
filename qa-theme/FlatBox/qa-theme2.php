<?php
// Temporary recovery theme: use Q2A's built-in renderer without custom helpers.
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

class qa_html_theme extends qa_html_theme_base
{
}
