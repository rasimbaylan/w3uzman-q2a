<?php
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

class w3_text_json_page
{
    private $directory;
    private $url;

    private function pages()
    {
        return array(
            'metin-araclari' => array('Metin Araçları', 'text', 'Kelime ve karakter sayın; Türkçe büyük-küçük harf, başlık ve slug dönüşümü yapın. Fazla boşlukları ve yinelenen satırları temizleyin.'),
            'json-duzenleyici' => array('JSON Düzenleyici', 'json', 'JSON verisini doğrulayın, biçimlendirin ve sıkıştırın. Hataları satır ve sütun bilgisiyle bulun; büyük sayıları ve anahtar sırasını koruyun.'),
        );
    }

    public function load_module($directory, $urltoroot)
    {
        $this->directory = $directory;
        $this->url = $urltoroot;
    }

    public function suggest_requests()
    {
        $requests = array();
        foreach ($this->pages() as $request => $page) {
            $requests[] = array('title' => $page[0], 'request' => $request, 'nav' => null);
        }
        return $requests;
    }

    public function match_request($request)
    {
        return isset($this->pages()[$request]);
    }

    public function process_request($request)
    {
        $pages = $this->pages();
        if (!isset($pages[$request])) {
            return null;
        }
        $page = $pages[$request];
        $content = qa_content_prepare();
        $content['title'] = $page[0];
        $content['description'] = $page[2];
        $content['canonical'] = qa_path_html($request, null, qa_opt('site_url'));
        $content['w3_tool_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $html = is_readable($this->directory . $page[1] . '.html') ? file_get_contents($this->directory . $page[1] . '.html') : false;
        if ($html === false) {
            unset($content['w3_tool_page']);
            $content['error'] = 'Araç şu anda görüntülenemiyor.';
            return $content;
        }
        $content['css_src'][] = $this->url . 'tools.css?v=2';
        foreach (array('common.js', $page[1] . '-model.js', $page[1] . '.js') as $script) {
            $content['script'][] = '<script defer src="' . qa_html($this->url . $script . '?v=2') . '"></script>';
        }
        $content['custom'] = str_replace(
            array('{{HOME_URL}}', '{{TEXT_URL}}', '{{JSON_URL}}'),
            array(qa_path_html(''), qa_path_html('metin-araclari'), qa_path_html('json-duzenleyici')),
            $html
        );
        return $content;
    }
}
