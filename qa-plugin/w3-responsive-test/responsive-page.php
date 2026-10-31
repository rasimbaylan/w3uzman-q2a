<?php
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

class w3_responsive_page
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
        return array(array('title' => 'Responsive Kontrol', 'request' => 'responsive-kontrol', 'nav' => null));
    }

    public function match_request($request)
    {
        return $request === 'responsive-kontrol';
    }

    public function process_request($request)
    {
        if (!$this->match_request($request)) {
            return null;
        }

        $content = qa_content_prepare();
        $content['title'] = 'Responsive Kontrol';
        $content['description'] = 'Web sitenizin mobil, tablet ve masaüstü görünümünü farklı ekran genişliklerinde inceleyin. Özel ölçüler belirleyin ve yatay ya da dikey görünümü kontrol edin.';
        $content['canonical'] = qa_path_html('responsive-kontrol', null, qa_opt('site_url'));
        $content['w3_tool_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $content['css_src'][] = $this->url . 'responsive.css?v=3';
        $content['script'][] = '<script defer src="' . qa_html($this->url . 'responsive.js?v=3') . '"></script>';

        $html = is_readable($this->directory . 'responsive.html') ? file_get_contents($this->directory . 'responsive.html') : false;
        if ($html === false) {
            unset($content['w3_tool_page']);
            $content['error'] = 'Responsive kontrol aracı şu anda görüntülenemiyor.';
            return $content;
        }
        $content['custom'] = str_replace(
            array('{{HOME_URL}}', '{{TOOL_URL}}'),
            array(qa_path_html(''), qa_path_html('responsive-kontrol', null, qa_opt('site_url'))),
            $html
        );

        return $content;
    }
}
