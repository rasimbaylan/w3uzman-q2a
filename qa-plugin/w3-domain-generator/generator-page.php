<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class w3_domain_generator_page
{
    private $url;
    public function load_module($directory, $urltoroot) { $this->url = $urltoroot; }
    public function suggest_requests() { return array(array('title' => 'Domain Öneri Aracı', 'request' => 'domain-oneri', 'nav' => null)); }
    public function match_request($request) { return $request === 'domain-oneri'; }
    public function process_request($request)
    {
        if (!$this->match_request($request)) { return null; }
        $content = qa_content_prepare();
        $content['title'] = 'Domain Öneri Aracı';
        $content['description'] = 'Anahtar kelimenize ve sektörünüze uygun alan adı fikirleri üretin. Önerileri kopyalayın ve Domain Sorgulama aracıyla kayıt durumlarını kontrol edin.';
        $content['canonical'] = qa_path_html('domain-oneri', null, qa_opt('site_url'));
        $content['w3_tool_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $content['css_src'][] = $this->url . 'generator.css?v=2';
        foreach (array('generator-model.js', 'generator.js') as $script) {
            $content['script'][] = '<script defer src="' . qa_html($this->url . $script . '?v=2') . '"></script>';
        }
        $html = '<section class="w3-generator" aria-labelledby="generator-title" data-lookup="' . qa_path_html('domain-sorgulama') . '"><nav class="generator-crumb" aria-label="Sayfa konumu"><a href="' . qa_path_html('') . '">Ana sayfa</a><span aria-hidden="true">›</span><a href="' . qa_path_html('araclar') . '">Araçlar</a><span aria-current="page"><span aria-hidden="true">› </span>Domain Öneri</span></nav>';
        $html .= '<header class="generator-heading"><h1 id="generator-title">Projenize bir isim bulun.</h1><p>Bir veya iki anahtar kelime yazın; sektörünüze uygun alan adı fikirlerini keşfedin.</p></header>';
        $html .= '<section class="generator-form-card" aria-label="Domain önerisi oluştur"><form id="generator-form"><div class="generator-fields"><div class="generator-keyword"><label for="generator-keyword">Anahtar kelime</label><input id="generator-keyword" type="text" maxlength="60" placeholder="Örn: bulut veya bulut yazılım" autocomplete="off" autocapitalize="none" spellcheck="false" required aria-describedby="generator-input-help generator-error"></div><div><label for="generator-sector">Sektör</label><select id="generator-sector"><option value="general">Genel</option><option value="ecommerce">E-ticaret</option><option value="saas">SaaS / Girişim</option><option value="blog">Blog / İçerik</option><option value="software">Yazılım</option><option value="agency">Ajans / Tasarım</option></select></div><div><label for="generator-extension">Uzantı</label><select id="generator-extension"><option value="com">.com</option><option value="com.tr">.com.tr</option><option value="net">.net</option><option value="org">.org</option><option value="io">.io</option><option value="ai">.ai</option></select></div><button class="generator-primary" id="generator-submit" type="submit" disabled>İsim üret <span aria-hidden="true">→</span></button></div><div class="generator-form-bottom"><p id="generator-input-help">Türkçe karakterler sadeleştirilir: ş → s, ü → u, ı → i.</p><div class="generator-examples" aria-label="Örnek anahtar kelimeler"><span>Deneyin:</span><button type="button" data-example="bulut" disabled>bulut</button><button type="button" data-example="tech" disabled>tech</button><button type="button" data-example="ajans" disabled>ajans</button></div></div><p class="generator-error" id="generator-error" role="alert" hidden></p></form><noscript><p class="generator-error">İsim üretmek için JavaScript’i etkinleştirin. Öneriler tarayıcınızda hazırlanır.</p></noscript></section>';
        $html .= '<p id="generator-status" class="generator-reader" role="status" aria-live="polite" aria-atomic="true"></p><section class="generator-results" id="generator-results" aria-labelledby="generator-results-title" hidden><header class="generator-results-heading"><div><h2 id="generator-results-title">İsim önerileri</h2><p id="generator-result-summary"></p></div><div class="generator-actions"><button type="button" id="generator-more">Farklı öneriler</button><button type="button" id="generator-copy-all">Listeyi kopyala</button></div></header><p class="generator-notice"><span aria-hidden="true">•</span> Bunlar isim fikirleridir; müsaitlik henüz kontrol edilmedi. Kontrol düğmesi, ismi mevcut araçta 10 uzantıda sorgular.</p><ol class="generator-grid" id="generator-grid" role="list" aria-label="Üretilen alan adı fikirleri"></ol></section>';
        $html .= '<section id="generator-copy-panel" class="generator-copy-panel" aria-label="Manuel kopyalama" hidden><label for="generator-copy-text">Kopyalanacak metin</label><textarea id="generator-copy-text" readonly rows="4"></textarea><button type="button" id="generator-copy-close">Kapat</button></section>';
        $html .= '<details class="generator-about"><summary>Öneriler nasıl üretiliyor?</summary><div><p>Ön ekler, son ekler ve sektörel kelime listeleri anahtar kelimenizle birleştirilir. İki kelime yazarsanız birlikte kullanıldıkları alternatifler de üretilir. Yinelenen ve aşırı uzun öneriler elenir.</p><p>Bu sürüm yapay zekâ veya ücretli API kullanmaz. Anahtar kelimeleriniz bu araç tarafından sunucuya gönderilmez ya da kaydedilmez. “Müsaitliğini kontrol et” bağlantısına tıkladığınızda seçtiğiniz isim Domain Sorgulama aracına iletilir.</p><p>Öneriler marka tescili, kullanım hakkı, satın alınabilirlik veya SEO başarısı garantisi değildir. Karar vermeden önce kayıt durumunu ve ilgili marka haklarını ayrıca kontrol edin.</p></div></details></section>';
        $content['custom'] = $html;
        return $content;
    }
}
