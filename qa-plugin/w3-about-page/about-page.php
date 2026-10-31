<?php
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

class w3_about_page
{
    private $directory;

    public function load_module($directory, $urltoroot)
    {
        $this->directory = $directory;
    }

    public function suggest_requests()
    {
        return array(array('title' => 'Hakkımızda', 'request' => 'hakkimizda', 'nav' => null));
    }

    public function match_request($request)
    {
        return $request === 'hakkimizda';
    }

    public function process_request($request)
    {
        if (!$this->match_request($request)) {
            return null;
        }

        $content = qa_content_prepare();
        $content['title'] = 'Hakkımızda';
        $content['description'] = 'W3Uzman; teknoloji, yazılım ve yapay zekâ üzerine Türkçe içerikler ve ortak bir öğrenme alanı sunan bilgi ve paylaşım platformudur.';
        $content['canonical'] = qa_path_html('hakkimizda', null, qa_opt('site_url'));

        $file = $this->directory . 'about.html';
        $html = is_readable($file) ? file_get_contents($file) : false;
        if ($html === false) {
            $content['error'] = 'Hakkımızda içeriği şu anda görüntülenemiyor.';
            return $content;
        }

        $content['custom'] = $html;
        $content['w3_about_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);

        return $content;
    }
}
