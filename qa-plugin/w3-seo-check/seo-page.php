<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class w3_seo_check_page
{
    private $directory;
    private $url;
    public function load_module($directory,$urltoroot) { $this->directory=$directory; $this->url=$urltoroot; }
    public function suggest_requests() { return array(array('title'=>'Sayfa SEO Kontrolü','request'=>'sayfa-seo-kontrolu','nav'=>null)); }
    public function match_request($request) { return $request==='sayfa-seo-kontrolu'; }
    protected function service() { require_once $this->directory.'seo-service.php'; return new W3SeoService(QA_BASE_DIR.'qa-cache'); }
    private function h($value) { return qa_html((string)$value); }
    private function postText($key) { return isset($_POST[$key]) && !is_string($_POST[$key]) ? null : qa_post_text($key); }
    private function label($state) { $labels=array('critical'=>'Öncelikli','warning'=>'Öneri','info'=>'Bilgi','pass'=>'Uygun'); return isset($labels[$state])?$labels[$state]:'Bilgi'; }

    public function process_request($request)
    {
        if (!$this->match_request($request)) { return null; }
        header('Cache-Control: private, no-store, max-age=0'); header('CDN-Cache-Control: no-store'); header('Referrer-Policy: no-referrer');
        $posted=isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']==='POST';
        $input=$posted?$this->postText('seo_url'):''; $input=is_string($input)?substr($input,0,4096):''; $error=''; $report=null;
        if ($posted) {
            header('X-Robots-Tag: noindex, follow');
            if (!qa_check_form_security_code('w3_seo_check',$this->postText('code'))) { $error='Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.'; }
            else { try { $report=$this->service()->analyze($input,isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'unknown'); } catch (InvalidArgumentException $e) { $error=$e->getMessage(); } catch (RuntimeException $e) { $error=$e->getMessage(); } }
        }
        $content=qa_content_prepare(); $content['title']='Site ve Sayfa SEO Kontrolü';
        $content['description']='Bir web sayfasının title, meta açıklama, H1-H6 başlıkları, canonical, robots ve sosyal paylaşım etiketlerini inceleyin. Açıklamalı Türkçe öneriler alın.';
        $content['canonical']=qa_path_html('sayfa-seo-kontrolu',null,qa_opt('site_url'));
        $content['w3_tool_page']=true; $content['widgets']=array(); $content['sidebar']=$content['sidepanel']=$content['suggest_next']='';
        unset($content['navigation']['cat'],$content['navigation']['sub']);
        $content['css_src'][]=$this->url.'seo.css?v=2'; $content['script'][]='<script defer src="'.$this->h($this->url.'seo.js?v=1').'"></script>';
        $html='<section class="w3-seo'.($report?' seo-has-report':'').'" aria-labelledby="seo-title"><nav class="seo-crumb" aria-label="Sayfa konumu"><a href="'.qa_path_html(''). '">Ana sayfa</a><span aria-hidden="true">›</span><a href="'.qa_path_html('araclar').'">Araçlar</a><span aria-hidden="true">›</span><span aria-current="page">SEO Kontrolü</span></nav>';
        $html.='<header class="seo-heading"><div><h1 id="seo-title">Sayfa SEO Kontrolü</h1><p>Başlık, açıklama, canonical, robots ve sosyal etiketleri tek raporda inceleyin.</p></div></header>';
        $html.='<section class="seo-input-card" aria-label="Sayfa adresi"><form id="seo-form" method="post" action="'.qa_path_html('sayfa-seo-kontrolu').'"><label for="seo-url">Sayfa adresi</label><div class="seo-input-row"><input id="seo-url" name="seo_url" type="text" inputmode="url" value="'.$this->h($input).'" placeholder="https://ornek.com/sayfa" maxlength="2048" required autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="seo-input-help'.($error?' seo-error':'').'"'.($error?' aria-invalid="true"':'').'><button id="seo-submit" type="submit">Kontrol et <span aria-hidden="true">→</span></button></div><input type="hidden" name="code" value="'.$this->h(qa_get_form_security_code('w3_seo_check')).'"><p id="seo-input-help">Yalnızca genel HTTP/HTTPS adresleri. Şifre, kişisel bilgi veya erişim anahtarı içeren bağlantı göndermeyin.</p><p id="seo-busy" role="status" hidden>Sayfa kontrol ediliyor… Ağ koşullarına göre 25 saniyeye kadar sürebilir.</p></form>';
        if ($error) { $html.='<p class="seo-error" id="seo-error" role="alert">'.$this->h($error).'</p>'; }
        $html.='</section>';
        if ($report) { $html.=$this->reportHtml($report); }
        else { $html.='<details class="seo-intro-panel"><summary>Bu kontrol neleri kapsar?</summary><div class="seo-intro-grid"><section><h2>Ne kontrol edilir?</h2><p>Sunucunun döndürdüğü HTML, HTTP yanıtı, robots.txt ve temel paylaşım bildirimleri incelenir.</p></section><section><h2>Sonuç nasıl okunur?</h2><p>Öncelikli, öneri, bilgi ve uygun etiketleriyle bulguları ayırın. Her bulgunun ne anlama geldiğini okuyun.</p></section><section><h2>Ne ölçülmez?</h2><p>Google sıralaması, gerçek indeks durumu, backlink, hız veya tüm site taraması yapılmaz. JavaScript çalıştırılmaz.</p></section></div></details>'; }
        $html.='<details class="seo-method"><summary id="seo-method-title">Yöntem, kaynaklar ve gizlilik</summary><div class="seo-method-body"><p>Bu araç Google ürünü veya sıralama puanı değildir. Bir yanıtın kaynak HTML’sini inceler; Googlebot’un işlenmiş görünümü ve CSS görünürlüğü farklı olabilir. Başlık/açıklama için katı karakter kotası uygulanmaz. Meta keywords veya “anahtar kelime yoğunluğu” puanlanmaz. Uygun bulgular dizine ekleme, AdSense veya sıralama garantisi vermez.</p><div class="seo-source-links"><a href="https://developers.google.com/search/docs/appearance/title-link" target="_blank" rel="noopener noreferrer">Google: Başlıklar ↗</a><a href="https://developers.google.com/search/docs/appearance/snippet" target="_blank" rel="noopener noreferrer">Meta açıklamalar ↗</a><a href="https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls" target="_blank" rel="noopener noreferrer">Canonical ↗</a><a href="https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag" target="_blank" rel="noopener noreferrer">Robots kuralları ↗</a><a href="https://developers.google.com/search/docs/crawling-indexing/robots/robots_txt" target="_blank" rel="noopener noreferrer">robots.txt ↗</a><a href="https://ogp.me/" target="_blank" rel="noopener noreferrer">Open Graph ↗</a></div><p class="seo-privacy">Kontrol için hedef sunucuya istek gönderilir; alan adı çözümlemesinde Google Public DNS kullanılır. Tam adres, HTML veya sonuçlar rapor önbelleğinde saklanmaz. İstek sınırı için IP adresinin özeti kısa süreli tutulur. Yerel ağ hedefleri, özel IP adresleri ve standart dışı portlar engellenir. Yanıt boyutu, süre ve yönlendirme sınırları uygulanır.</p></div></details></section>';
        $content['custom']=$html; return $content;
    }

    private function reportHtml($r)
    {
        $html='<section class="seo-report" aria-labelledby="seo-report-title"><header class="seo-report-heading"><div><h2 id="seo-report-title">Kontrol sonuçları</h2><p><a href="'.$this->h($r['url']).'" target="_blank" rel="noopener noreferrer nofollow">'.$this->h($r['url']).' ↗</a></p></div><span>'.gmdate('d.m.Y H:i',$r['checked']).' UTC</span></header><div class="seo-counts">';
        foreach ($r['counts'] as $state=>$count) { $html.='<div class="seo-count seo-state-'.$state.'"><strong>'.$count.'</strong><span>'.$this->label($state).'</span></div>'; }
        $html.='</div><div class="seo-report-layout"><div class="seo-groups">'; $groups=array();
        foreach ($r['checks'] as $check) { $groups[$check['group']][]=$check; }
        $ordered=array();
        foreach (array('Başlık ve açıklama','Robots','Canonical','Başlık hiyerarşisi','Sosyal paylaşım','Erişim') as $name) { if (isset($groups[$name])) { $ordered[$name]=$groups[$name]; } }
        $groups=$ordered+$groups;
        foreach ($groups as $name=>$checks) {
            $html.='<section class="seo-group"><h3>'.$this->h($name).'</h3><ul>';
            foreach ($checks as $check) {
                $html.='<li data-seo-check="'.$this->h($check['id']).'" data-state="'.$this->h($check['state']).'"><div class="seo-check-title"><h4>'.$this->h($check['label']).'</h4><span class="seo-badge seo-state-'.$this->h($check['state']).'">'.$this->label($check['state']).'</span></div>';
                if ($check['value']!=='') { $html.='<code>'.$this->h($check['value']).'</code>'; }
                $html.='<p>'.$this->h($check['detail']).'</p></li>';
            }
            $html.='</ul></section>';
        }
        if ($r['parsed']) {
            $html.='<details class="seo-detail"><summary>Başlık yapısı <span>H1–H6</span></summary><div class="seo-heading-counts">';
            foreach ($r['heading_counts'] as $level=>$count) { $html.='<span>H'.$level.' <b>'.$count.'</b></span>'; }
            $html.='</div><ol class="seo-heading-tree">';
            foreach ($r['headings'] as $heading) { $html.='<li class="seo-level-'.$heading['level'].'"><b>H'.$heading['level'].'</b><span>'.$this->h($heading['text']!==''?$heading['text']:'(Boş başlık)').'</span></li>'; }
            $html.='</ol><p class="seo-detail-note">En fazla ilk 50 başlık gösterilir. Sayaçlar kaynak HTML’deki tüm başlıkları kapsar.</p></details>';
            $html.='<details class="seo-detail"><summary>Canonical ve robots bildirimleri</summary><dl class="seo-raw-list">';
            foreach ($r['canonicals'] as $canonical) { $html.='<div><dt>Canonical · '.$this->h($canonical['source']).'</dt><dd>'.$this->h($canonical['raw']).'</dd></div>'; }
            foreach ($r['robots'] as $rule) { $html.='<div><dt>'.$this->h($rule['source']).'</dt><dd>'.$this->h($rule['value']).'</dd></div>'; }
            if (!$r['canonicals'] && !$r['robots']) { $html.='<div><dt>Bildirim</dt><dd>Açık canonical veya robots meta/header bildirimi bulunamadı.</dd></div>'; }
            $html.='</dl></details><details class="seo-detail"><summary>Sosyal paylaşım etiketleri</summary><dl class="seo-raw-list">';
            foreach ($r['social'] as $key=>$values) { foreach ($values as $value) { $html.='<div><dt>'.$this->h($key).'</dt><dd>'.$this->h($value).'</dd></div>'; } }
            if (!$r['social']) { $html.='<div><dt>Bildirim</dt><dd>Open Graph veya X/Twitter etiketi bulunamadı.</dd></div>'; }
            $html.='</dl><p class="seo-detail-note">Uzak paylaşım görselleri indirilmez. Buradaki kontrol, etiketlerin varlığı ve adres biçimiyle sınırlıdır.</p></details>';
        }
        $html.='<details class="seo-detail"><summary>HTTP yanıtları ve yönlendirmeler</summary><ol class="seo-chain">';
        foreach ($r['chain'] as $hop) { $html.='<li><b>'.$hop['status'].'</b><span>'.$this->h($hop['url']).'</span></li>'; }
        $html.='</ol></details></div><aside class="seo-side">';
        if ($r['parsed']) { $html.='<section class="seo-preview"><h3>Temsilî arama görünümü</h3><span class="seo-preview-url">'.$this->h($r['url']).'</span><strong>'.$this->h($r['title']!==''?$r['title']:'Başlık bildirimi bulunamadı').'</strong><p>'.$this->h($r['description']!==''?$r['description']:'Meta açıklama bildirimi bulunamadı. Google sayfa içeriğinden farklı bir açıklama oluşturabilir.').'</p><small>Google’ın gerçek sonucu değildir. Başlık ve açıklama sorguya, cihaza ve Google’ın seçimine göre değişebilir.</small></section>'; }
        $html.='<details class="seo-reading"><summary>Sonuçları nasıl okumalı?</summary><p><b>Öncelikli:</b> Önce gözden geçirin. noindex gibi kurallar bilinçli olabilir.</p><p><b>Öneri:</b> İçerik yapısı veya gösterim için iyileştirme alanı.</p><p><b>Bilgi:</b> Bağlama göre yorumlanmalı; otomatik hata değildir.</p><p><b>Uygun:</b> Bu yanıtta ilgili kontrol sağlandı; başarı garantisi değildir.</p><a href="https://search.google.com/search-console/about" target="_blank" rel="noopener noreferrer">Gerçek indeks durumunu Search Console’da kontrol edin ↗</a></details></aside></div></section>';
        return $html;
    }
}
