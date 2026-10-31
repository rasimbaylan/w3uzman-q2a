<?php
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

class w3_qr_code_page
{
    private $directory;
    private $url;

    public function load_module($directory, $urltoroot)
    {
        $this->directory = $directory;
        $this->url = $urltoroot;
    }

    public function suggest_requests()
    {
        return array(array('title' => 'QR Kod Oluşturucu', 'request' => 'qr-kod-olusturucu', 'nav' => null));
    }

    public function match_request($request)
    {
        return $request === 'qr-kod-olusturucu';
    }

    public function process_request($request)
    {
        if (!$this->match_request($request)) {
            return null;
        }
        $content = qa_content_prepare();
        $content['title'] = 'QR Kod Oluşturucu';
        $content['description'] = 'Bağlantı, Wi-Fi, WhatsApp ve kişi bilgileri için ücretsiz QR kod oluşturun. Canlı önizleyin, PNG veya SVG olarak indirin. Bilgiler tarayıcınızda işlenir.';
        $content['canonical'] = qa_path_html('qr-kod-olusturucu', null, qa_opt('site_url'));
        $content['w3_tool_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $html = is_readable($this->directory . 'qr.html') ? file_get_contents($this->directory . 'qr.html') : false;
        if ($html === false) {
            unset($content['w3_tool_page']);
            $content['error'] = 'QR kod aracı şu anda görüntülenemiyor.';
            return $content;
        }
        $content['css_src'][] = $this->url . 'qr.css?v=2';
        foreach (array('vendor/qrcodegen.js', 'qr-model.js', 'qr.js') as $script) {
            $content['script'][] = '<script defer src="' . qa_html($this->url . $script . '?v=2') . '"></script>';
        }
        $content['custom'] = str_replace('{{HOME_URL}}', qa_path_html(''), $html);
        return $content;
    }
}
