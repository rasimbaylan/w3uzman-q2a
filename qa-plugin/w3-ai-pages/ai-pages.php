<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class w3_ai_pages
{
    private $url;
    public function load_module($directory, $urltoroot) { $this->url = $urltoroot; }
    private function documents()
    {
        // Concise editorial summaries checked against the linked official sources.
        // No price, model-version, ranking or guaranteed-capability claims are stored here.
        return array(
            'chatgpt' => array('name'=>'ChatGPT','maker'=>'OpenAI','mark'=>'C','intro'=>'ChatGPT; bir fikri geliştirmek, bir görevi açıklığa kavuşturmak ve kullanılabilir bir taslak hazırlamak için doğal dille etkileşim kurabileceğiniz bir yapay zekâ asistanıdır.','uses'=>array('Bir yazı veya proje için ilk taslağı ve ana hatları oluşturma.','Bir konuyu soru-cevap yoluyla adım adım inceleme.','Verdiğiniz bağlama göre metni yeniden düzenleme.'),'prompt'=>'Bir teknoloji yazısının girişini hazırlamama yardım et. Hedef kitle yeni başlayanlar; önce eksik bağlamı sor, sonra kısa bir taslak öner.','source'=>'https://learn.chatgpt.com/docs/overview','terms'=>array('ChatGPT','OpenAI')),
            'gemini' => array('name'=>'Gemini','maker'=>'Google','mark'=>'G','intro'=>'Gemini, Google’ın üretken yapay zekâyla doğrudan etkileşim kurmaya yönelik asistanıdır. Yazı hazırlama, fikir geliştirme, öğrenme ve kodla ilgili sorularda yardımcı olabilir.','uses'=>array('Bir metni özetleme veya yazının ana hatlarını çıkarma.','Bir kavram için açıklama ve örnek isteme.','Kodla ilgili bir sorunu tarif edip olası nedenleri inceleme.'),'prompt'=>'Bu konuyu yeni öğrenen biri için açıkla. Üç temel kavram, bir örnek ve bilgiyi doğrulamak için bakılabilecek kaynak türlerini sırala.','source'=>'https://gemini.google/overview/','terms'=>array('Gemini')),
            'claude' => array('name'=>'Claude','maker'=>'Anthropic','mark'=>'C','intro'=>'Claude, Anthropic’in fikirleri geliştirme, bilgiyi bir araya getirme ve düzenlenebilir taslaklar hazırlama gibi işlerde kullanılabilen yapay zekâ asistanıdır.','uses'=>array('Bir proje fikrini daha açık ve uygulanabilir adımlara bölme.','Sağladığınız notları bir taslakta bir araya getirme.','Bir metnin yapısını ve anlatımını gözden geçirme.'),'prompt'=>'Bu proje notlarını incele. Önce belirsiz noktaları belirt; ardından amacı, yapılacak işleri ve açık kalan soruları ayrı başlıklarda düzenle.','source'=>'https://claude.com/product/overview','terms'=>array('Claude','Anthropic')),
            'midjourney' => array('name'=>'Midjourney','maker'=>'Midjourney','mark'=>'M','intro'=>'Midjourney, metin açıklamalarından görsel üretmeye ve görsel fikirleri geliştirmeye yönelik bir üretken yapay zekâ hizmetidir. Oluşturulan görseller üzerinde varyasyon ve düzenleme seçenekleri sunar.','uses'=>array('Bir görsel fikri için farklı kompozisyonlar deneme.','Işık, renk ve stil tarifiyle bir sahne tasarlama.','Üretilen görsellerin alternatiflerini geliştirme.'),'prompt'=>'Sabah ışığında sakin bir çalışma masası; sade kompozisyon, doğal ahşap dokusu, yumuşak gölgeler, yazı veya logo olmadan.','source'=>'https://docs.midjourney.com/hc/en-us/articles/33329261836941-Getting-Started-Guide','terms'=>array('Midjourney')),
            'github-copilot' => array('name'=>'GitHub Copilot','maker'=>'GitHub','mark'=>'</>','intro'=>'GitHub Copilot, yazılım geliştirme akışında kod yazma, kodu anlama ve değişiklikleri inceleme görevlerine yardımcı olan bir yapay zekâ asistanıdır.','uses'=>array('Kod tabanındaki bir işlevin ne yaptığını açıklama.','Kod önerileri ve test senaryoları üzerinde çalışma.','Bir değişiklikte kontrol edilmesi gereken noktaları belirleme.'),'prompt'=>'Bu işlevi açıkla. Hatalı girişleri ve sınır durumlarını belirle, ardından mevcut davranışı doğrulayan test senaryoları öner.','source'=>'https://docs.github.com/en/copilot/get-started/about-github-copilot','terms'=>array('Copilot')),
        );
    }
    public function suggest_requests()
    {
        $pages = array();
        foreach ($this->documents() as $slug=>$doc) { $pages[] = array('title'=>$doc['name'],'request'=>$slug,'nav'=>null); }
        return $pages;
    }
    public function match_request($request) { $docs = $this->documents(); return isset($docs[$request]); }
    public function process_request($request)
    {
        $docs = $this->documents();
        if (!isset($docs[$request])) { return null; }
        $doc = $docs[$request];
        $content = qa_content_prepare();
        $content['title'] = $doc['name'] . ' Nedir? Kullanım Alanları ve Haberler';
        $content['description'] = $doc['intro'];
        $content['canonical'] = qa_path_html($request, null, qa_opt('site_url'));
        $content['w3_tool_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $content['css_src'][] = $this->url . 'ai-pages.css?v=1';
        $html = '<main class="w3-ai-guide"><nav class="ai-breadcrumb" aria-label="İçerik yolu"><a href="'.qa_path_html(''). '">Ana sayfa</a><span aria-hidden="true">›</span><a href="'.qa_path_html('questions').'">Haberler</a><span aria-hidden="true">›</span><span>'.qa_html($doc['name']).'</span></nav>';
        $html .= '<header class="ai-hero"><span class="ai-mark" aria-hidden="true">'.qa_html($doc['mark']).'</span><div><p class="ai-eyebrow">'.qa_html($doc['maker']).' · Yapay zekâ rehberi</p><h1>'.qa_html($doc['name']).' nedir?</h1><p>'.qa_html($doc['intro']).'</p></div></header>';
        $html .= '<div class="ai-guide-grid"><section class="ai-card" aria-labelledby="ai-use-title"><h2 id="ai-use-title">Neler için kullanılabilir?</h2><ul>';
        foreach ($doc['uses'] as $use) { $html .= '<li>'.qa_html($use).'</li>'; }
        $html .= '</ul></section><section class="ai-card ai-example" aria-labelledby="ai-example-title"><h2 id="ai-example-title">Bir başlangıç örneği</h2><p>'.qa_html($doc['prompt']).'</p><small>Bu bir örnek istemdir; çıktı veya sonuç garantisi değildir.</small></section></div>';
        $html .= '<aside class="ai-note" aria-label="Kullanım notu"><strong>Sonucu gözden geçirin.</strong> Yapay zekâ çıktıları hata içerebilir. Önemli bilgileri birincil kaynaklardan doğrulayın; hassas kişisel verileri ve erişim anahtarlarını paylaşmayın. Özellikler ve erişim koşulları hesaba, bölgeye ve plana göre değişebilir.</aside>';
        $html .= '<section class="ai-card ai-news" aria-labelledby="ai-news-title"><header><h2 id="ai-news-title">'.qa_html($doc['name']).' ile ilgili haberler</h2><a href="'.qa_path_html('search',array('q'=>$doc['terms'][0])).'">Tüm sonuçlar →</a></header>';
        $conditions = array(); $parameters = array();
        foreach ($doc['terms'] as $term) { $conditions[] = '(title LIKE $ OR tags LIKE $)'; $parameters[] = '%'.$term.'%'; $parameters[] = '%'.$term.'%'; }
        $query = "SELECT postid,title,UNIX_TIMESTAMP(created) AS created FROM ^posts WHERE type='Q' AND (".implode(' OR ', $conditions).') ORDER BY created DESC,postid DESC LIMIT 6';
        $rows = qa_db_read_all_assoc(call_user_func_array('qa_db_query_sub', array_merge(array($query),$parameters)));
        if ($rows) {
            $html .= '<ul>';
            foreach ($rows as $post) { $html .= '<li><a href="'.qa_q_path_html($post['postid'],$post['title']).'">'.qa_html($post['title']).'</a><time datetime="'.gmdate('Y-m-d',(int)$post['created']).'">'.gmdate('d.m.Y',(int)$post['created']).'</time></li>'; }
            $html .= '</ul>';
        } else { $html .= '<p>Bu konu için henüz yayınlanmış haber bulunmuyor. <a href="'.qa_path_html('questions').'">Diğer haberleri keşfedin →</a></p>'; }
        $html .= '</section><footer class="ai-source"><p>Kaynak: <a href="'.qa_html($doc['source']).'" target="_blank" rel="noopener noreferrer">'.qa_html($doc['name']).' resmî tanıtım ve belgeleri</a> · Kontrol: <time datetime="2026-10-21">21 Ekim 2026</time></p><p>W3Uzman bağımsız bir bilgi platformudur; bu sayfa hizmet sağlayıcısının resmî sitesi değildir.</p></footer><nav class="ai-related" aria-label="Diğer yapay zekâ rehberleri">';
        foreach ($docs as $slug=>$item) { if ($slug !== $request) { $html .= '<a href="'.qa_path_html($slug).'">'.qa_html($item['name']).' →</a>'; } }
        $content['custom'] = $html . '</nav></main>';
        return $content;
    }
}
