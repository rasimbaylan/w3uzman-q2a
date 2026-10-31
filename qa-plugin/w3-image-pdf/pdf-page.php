<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class w3_image_pdf_page
{
    private $directory;
    private $url;

    public function load_module($directory, $urltoroot) { $this->directory = $directory; $this->url = $urltoroot; }
    public function suggest_requests() { return array(array('title' => 'Resimden PDF Oluşturucu', 'request' => 'resimden-pdf', 'nav' => null)); }
    public function match_request($request) { return $request === 'resimden-pdf'; }

    public function process_request($request)
    {
        if (!$this->match_request($request)) { return null; }
        $content = qa_content_prepare();
        $content['title'] = 'Resimden PDF Oluşturucu';
        $content['description'] = 'JPG, JPEG ve PNG fotoğraflarını ücretsiz olarak tek PDF dosyasında birleştirin. Sayfaları sıralayın, döndürün ve indirin. Dosyalarınız sunucuya yüklenmez.';
        $content['canonical'] = qa_path_html('resimden-pdf', null, qa_opt('site_url'));
        $content['w3_tool_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $html = is_readable($this->directory . 'pdf.html') ? file_get_contents($this->directory . 'pdf.html') : false;
        if ($html === false) {
            unset($content['w3_tool_page']);
            $content['error'] = 'Resimden PDF aracı şu anda görüntülenemiyor.';
            return $content;
        }
        $content['css_src'][] = $this->url . 'pdf.css?v=1';
        foreach (array('vendor/pdf-lib-1.17.1.min.js', 'pdf.js') as $script) {
            $content['script'][] = '<script defer src="' . qa_html($this->url . $script . '?v=1') . '"></script>';
        }
        $content['custom'] = str_replace(array('{{HOME_URL}}', '{{TOOLS_URL}}'), array(qa_path_html(''), qa_path_html('araclar')), $html);
        return $content;
    }
}
