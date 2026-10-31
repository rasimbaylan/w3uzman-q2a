<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class w3_tools_directory_page
{
    private $url;

    public function load_module($directory, $urltoroot) { $this->url = $urltoroot; }
    public function suggest_requests() { return array(array('title' => 'Araçlar', 'request' => 'araclar', 'nav' => null)); }
    public function match_request($request) { return $request === 'araclar'; }

    public function process_request($request)
    {
        if (!$this->match_request($request)) { return null; }
        $content = qa_content_prepare();
        $content['title'] = 'Araçlar';
        $content['description'] = 'Domain önerileri ve sayfa SEO kontrolü; Whois, domain, DNS, SSL ve IP sorgulama; resimden PDF, QR kod, metin araçları, JSON düzenleyici ve responsive kontrol. W3Uzman araçlarını keşfedin.';
        $content['canonical'] = qa_path_html('araclar', null, qa_opt('site_url'));
        $content['w3_tool_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $content['css_src'][] = $this->url . 'directory.css?v=2';
        $content['script'][] = '<script defer src="' . qa_html($this->url . 'directory.js?v=1') . '"></script>';
        $groups = array('network' => 'Alan adı ve ağ', 'development' => 'Geliştirme', 'content' => 'İçerik');
        $tools = array(
            array('Domain Öneri Aracı', 'domain-oneri', 'Anahtar kelimeniz ve sektörünüzle alan adı fikirleri üretin; kayıt durumunu kontrol edin.', 'network', 'gold', 'domain alan adı isim öneri üretici generator marka girişim bulut ajans saas', '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5ZM20 3v4m-2-2h4"></path>'),
            array('Sayfa SEO Kontrolü', 'sayfa-seo-kontrolu', 'Başlık, açıklama, canonical, robots ve sosyal etiketleri açıklamalı inceleyin.', 'development', 'blue', 'seo site sayfa analiz title meta açıklama h1 h2 h3 canonical robots opengraph twitter arama google', '<circle cx="10" cy="10" r="7"></circle><path d="m15 15 6 6M6 12v-2m4 2V6m4 6V8"></path>'),
            array('Resimden PDF', 'resimden-pdf', 'JPG ve PNG görsellerinizi sıralayın, döndürün ve tek PDF olarak indirin.', 'content', 'orange', 'resim fotoğraf jpg jpeg png pdf dönüştür birleştir öğrenci ödev belge', '<path d="M14 2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9Z"></path><path d="M14 2v7h7M7 15h10m-10 4h7"></path>'),
            array('Whois Sorgulama', 'whois-sorgulama', 'Alan adının kayıt, sahiplik ve tarih bilgilerini inceleyin.', 'network', 'blue', 'whois domain kayıt', '<circle cx="10" cy="10" r="6"></circle><path d="m15 15 6 6M10 7v.1M10 10v3"></path>'),
            array('Domain Sorgulama', 'domain-sorgulama', '10 uzantıyı birlikte kontrol edin; boş alan adlarını bulun.', 'network', 'teal', 'alan adı müsait com ai io co', '<circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3c5 5 5 13 0 18-5-5-5-13 0-18Z"></path>'),
            array('DNS Kayıt Sorgulama', 'dns-kayit-sorgulama', 'A, AAAA, MX, TXT ve diğer DNS kayıtlarını sorgulayın.', 'network', 'purple', 'sunucu nameserver ns cname soa caa srv', '<rect x="9" y="3" width="6" height="5" rx="1"></rect><rect x="2" y="16" width="6" height="5" rx="1"></rect><rect x="16" y="16" width="6" height="5" rx="1"></rect><path d="M12 8v4M5 16v-4h14v4"></path>'),
            array('SSL Sertifika Kontrolü', 'ssl-sertifika-kontrolu', 'Sertifikanın geçerliliğini, bitiş tarihini ve alan adını kontrol edin.', 'network', 'green', 'https güvenlik tls sertifika', '<path d="m12 2 8 3v6c0 5-8 11-8 11S4 16 4 11V5Z"></path><path d="m8 11 3 3 5-5"></path>'),
            array('WhatisMyip', 'whatismyip', 'Genel IP adresinizi, servis sağlayıcınızı ve yaklaşık konumunuzu görün.', 'network', 'blue', 'ip adresim ipv4 ipv6 lokasyon konum harita', '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle>'),
            array('Responsive Kontrol', 'responsive-kontrol', 'Sitenizin mobil, tablet ve masaüstü görünümünü karşılaştırın.', 'development', 'purple', 'ekran cihaz telefon tablet web site test responsive', '<rect x="2" y="3" width="14" height="11" rx="1"></rect><path d="M5 18h7m-3-4v4"></path><rect x="15" y="10" width="7" height="12" rx="1"></rect><path d="M18 19h1"></path>'),
            array('QR Kod Oluşturucu', 'qr-kod-olusturucu', 'Bağlantı, Wi-Fi, WhatsApp ve kişi kartı için QR kod oluşturun.', 'content', 'gold', 'qr karekod png svg wifi whatsapp vcard kartvizit', '<path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h3v3h3v3h-6v-3m6-6v3M12 3v3m0 6v3m0 6v1M3 12h3m3 0h6"></path>'),
            array('Metin Araçları', 'metin-araclari', 'Kelime sayın; Türkçe harfleri dönüştürün ve metninizi temizleyin.', 'content', 'orange', 'kelime karakter sayacı büyük küçük harf slug boşluk yazı', '<path d="M3 5h12M9 5v15M4 20h10M16 10l5 10m-5 0 5-10M16 17h5"></path>'),
            array('JSON Düzenleyici', 'json-duzenleyici', 'JSON doğrulayın, biçimlendirin ve sıkıştırın; hataları bulun.', 'development', 'teal', 'json formatter prettify minify yazılım kod geliştirici', '<path d="M8 3H6a2 2 0 0 0-2 2v4a3 3 0 0 1-2 3 3 3 0 0 1 2 3v4a2 2 0 0 0 2 2h2M16 3h2a2 2 0 0 1 2 2v4a3 3 0 0 0 2 3 3 3 0 0 0-2 3v4a2 2 0 0 1-2 2h-2M10 9h.1M14 15h.1"></path>'),
        );
        $svg = function ($shape) { return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $shape . '</svg>'; };
        $html = '<section class="w3-directory" aria-labelledby="directory-title"><nav class="directory-crumb" aria-label="Sayfa konumu"><a href="' . qa_path_html('') . '">Ana sayfa</a><span aria-hidden="true">›</span><span aria-current="page">Araçlar</span></nav>';
        $html .= '<header class="directory-heading"><div><h1 id="directory-title">İşinizi kolaylaştıran araçlar</h1><p>Alan adı ve ağ kontrollerinden günlük metin işlerine; ihtiyacınız olan aracı seçin.</p></div><div class="directory-total"><strong>' . count($tools) . '</strong><span>ücretsiz araç</span></div></header>';
        $html .= '<div class="directory-toolbar"><div class="directory-filters" role="group" aria-label="Araç türü"><button type="button" data-directory-filter="all" aria-pressed="true" disabled>Tümü <span>' . count($tools) . '</span></button>';
        foreach ($groups as $key => $label) {
            $count = count(array_filter($tools, function ($tool) use ($key) { return $tool[3] === $key; }));
            $html .= '<button type="button" data-directory-filter="' . $key . '" aria-pressed="false" disabled>' . $label . ' <span>' . $count . '</span></button>';
        }
        $html .= '</div><div class="directory-search">' . $svg('<circle cx="10.5" cy="10.5" r="6.5"></circle><path d="m16 16 4.5 4.5"></path>') . '<label class="directory-reader" for="directory-search">Araçlarda ara</label><input id="directory-search" type="search" placeholder="Araç veya işlem ara…" autocomplete="off" disabled></div></div>';
        $html .= '<div class="directory-results"><span id="directory-count" role="status" aria-live="polite">' . count($tools) . ' araç</span><span>İkonuna veya adına tıklayarak başlayın</span></div><div class="directory-grid">';
        foreach ($tools as $tool) {
            $html .= '<a class="directory-card directory-tone-' . $tool[4] . '" href="' . qa_path_html($tool[1]) . '" data-directory-group="' . $tool[3] . '" data-directory-search="' . qa_html($tool[0] . ' ' . $tool[2] . ' ' . $tool[5]) . '"><span class="directory-icon">' . $svg($tool[6]) . '</span><div class="directory-copy"><h2>' . qa_html($tool[0]) . '</h2><p>' . qa_html($tool[2]) . '</p><span class="directory-category">' . $groups[$tool[3]] . '</span></div><span class="directory-arrow" aria-hidden="true">↗</span></a>';
        }
        $html .= '</div><div class="directory-empty" id="directory-empty" hidden><h2>Bu aramaya uygun araç bulunamadı</h2><p>Farklı bir kelime deneyin veya tüm araçlara dönün.</p><button type="button" id="directory-reset">Tüm araçları göster</button></div><footer class="directory-footer"><span>Tek bir yerden, doğrudan ihtiyacınız olan araca.</span><a href="' . qa_path_html('categories') . '">İçerik kategorilerini keşfet →</a></footer></section>';
        $content['custom'] = $html;
        return $content;
    }
}
