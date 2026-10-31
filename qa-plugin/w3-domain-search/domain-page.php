<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class w3_domain_search_page
{
    private $directory;
    private $url;
    public function load_module($directory, $urltoroot) { $this->directory = $directory; $this->url = $urltoroot; }
    public function suggest_requests() { return array(array('title' => 'Domain Sorgulama', 'request' => 'domain-sorgulama', 'nav' => null)); }
    public function match_request($request) { return $request === 'domain-sorgulama'; }

    public function process_request($request)
    {
        if (!$this->match_request($request)) { return null; }
        $input = qa_get('name');
        $name = ''; $error = ''; $service = null;
        $api = qa_get('format') === 'status';
        if ($input !== null || $api) {
            header('Cache-Control: private, no-store, max-age=0');
            header('CDN-Cache-Control: no-store');
            header('X-Robots-Tag: noindex, follow');
            header('Referrer-Policy: no-referrer');
        }
        if (is_readable($this->directory . 'domain-service.php')) {
            require_once $this->directory . 'domain-service.php';
            $cache = new Q2A_Storage_FileCacheDriver(array('enabled' => true, 'dir' => QA_BASE_DIR . 'qa-cache', 'keyprefix' => 'w3-domain-v1:'));
            $service = new W3DomainSearchService($cache, QA_BASE_DIR . 'qa-cache');
            if ($input !== null || $api) {
                try { $name = $service->normalize($input); }
                catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
            }
        } else { $error = 'Domain sorgulama aracı şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.'; }

        if ($api) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            $result = array('state' => 'unknown', 'message' => $error, 'domain' => '', 'label' => 'Doğrulanamadı');
            if ($service && !$error) {
                try { $result = $service->lookup($name, qa_get('extension'), isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'); }
                catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); $result['message'] = $error; }
            }
            http_response_code($error ? 400 : (isset($result['http_status']) ? $result['http_status'] : 200));
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            qa_exit('w3-domain-status');
        }

        $content = qa_content_prepare();
        $content['title'] = 'Domain Sorgulama';
        $content['description'] = 'Bir alan adını .com, .net, .org, .com.tr, .tr, .info, .biz, .ai, .io ve .co uzantılarında kontrol edin. Kayıtlı alan adlarının Whois bilgilerini inceleyin.';
        $content['canonical'] = qa_path_html('domain-sorgulama', null, qa_opt('site_url'));
        $content['w3_tool_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $content['css_src'][] = $this->url . 'domain.css?v=2';
        $content['script'][] = '<script defer src="' . qa_html($this->url . 'domain.js?v=2') . '"></script>';
        $html = '<main class="w3-domain"><header class="w3-domain-heading"><div><h1>Domain Sorgulama</h1><p>Bir isim yazın, 10 popüler uzantıyı birlikte kontrol edin.</p></div><span class="w3-domain-heading-mark" aria-hidden="true">www<span>.</span></span></header>';
        $html .= '<section class="w3-domain-search" aria-label="Alan adı sorgulama"><form action="' . qa_path_html('domain-sorgulama') . '" method="get"><label for="w3-domain-name">Hangi alan adını arıyorsunuz?</label><div class="w3-domain-field"><input id="w3-domain-name" name="name" type="text" value="' . qa_html(is_string($input) ? substr($input, 0, 512) : '') . '" placeholder="Markanız veya projenizin adı" maxlength="253" autocomplete="off" autocapitalize="none" spellcheck="false" required aria-describedby="w3-domain-help' . ($error ? ' w3-domain-error' : '') . '"' . ($error ? ' aria-invalid="true" autofocus' : '') . '><button type="submit">Domain sorgula <span aria-hidden="true">→</span></button></div><p id="w3-domain-help">Örnek: <a href="' . qa_path_html('domain-sorgulama', array('name' => 'w3uzman')) . '">w3uzman</a> · Uzantı yazsanız da aynı adı 10 uzantıda kontrol ederiz.</p></form>';
        if ($error) { $html .= '<p id="w3-domain-error" class="w3-domain-error" role="alert">' . qa_html($error) . '</p>'; }
        $html .= '</section>';
        if ($name && !$error) {
            $html .= '<section class="w3-domain-results" data-name="' . qa_html($name) . '" data-endpoint="' . qa_path_html('domain-sorgulama') . '" aria-labelledby="w3-domain-results-title"><header class="w3-domain-result-heading"><h2 id="w3-domain-results-title">' . qa_html($name) . ' <span>için sonuçlar</span></h2><p id="w3-domain-progress" role="status" aria-live="polite">10 uzantı kontrol edilecek.</p></header><div class="w3-domain-filters" role="group" aria-label="Sonuçları filtrele" hidden><button type="button" data-filter="all" aria-pressed="true">Tümü <span data-count="all">10</span></button><button type="button" data-filter="available" aria-pressed="false">Boş <span data-count="available">0</span></button><button type="button" data-filter="registered" aria-pressed="false">Dolu <span data-count="registered">0</span></button><button type="button" data-filter="unknown" aria-pressed="false">Belirsiz <span data-count="unknown">0</span></button></div><ul class="w3-domain-list" aria-label="Alan adı sonuçları">';
            foreach (W3DomainSearchService::extensions() as $extension) {
                $domain = $name . '.' . $extension;
                $html .= '<li class="w3-domain-row" data-extension="' . qa_html($extension) . '" data-state="pending"><span class="w3-domain-indicator" aria-hidden="true">…</span><div class="w3-domain-row-name"><strong>' . qa_html($name) . '<b>.' . qa_html($extension) . '</b></strong><small class="w3-domain-detail">Kayıt servisi bekleniyor</small></div><span class="w3-domain-status">Bekliyor</span><a class="w3-domain-whois" href="' . qa_path_html('whois-sorgulama', array('domain' => $domain)) . '" aria-label="' . qa_html($domain) . ' Whois bilgileri" hidden>Whois <span aria-hidden="true">↗</span></a><button class="w3-domain-retry" type="button" aria-label="' . qa_html($domain) . ' tekrar kontrol et" hidden>Tekrar dene</button></li>';
            }
            $html .= '</ul><p class="w3-domain-filter-empty" hidden>Bu filtreye uygun sonuç yok.</p><noscript><p class="w3-domain-note">10 uzantıyı birlikte kontrol etmek için JavaScript’i etkinleştirin. Tek bir alan adını <a href="' . qa_path_html('whois-sorgulama', array('domain' => $name . '.com')) . '">Whois aracında sorgulayabilirsiniz</a>.</p></noscript><p class="w3-domain-note"><strong>Boş:</strong> kayıt servisinde kayıt bulunamadı. Rezerve veya özel tahsisli adlar olabilir; satın almadan önce kayıt kuruluşunda doğrulayın. Sonuçlar anlıktır, en fazla 5 dakika önbellek kullanılabilir.</p></section>';
        } else {
            $html .= '<section class="w3-domain-empty" aria-label="Desteklenen uzantılar"><h2>Projenize uygun alan adını bulun.</h2><p>Sonuçları tek ekranda karşılaştırın. Kayıtlı adların detaylarına Whois ile ulaşın.</p><div class="w3-domain-extensions">';
            foreach (array('com', 'net', 'org', 'com.tr', 'tr', 'info', 'biz', 'ai', 'io', 'co') as $extension) { $html .= '<span>.' . $extension . '</span>'; }
            $html .= '</div><div class="w3-domain-legend"><span><i class="is-available"></i>Boş</span><span><i class="is-registered"></i>Dolu</span><span><i class="is-unknown"></i>Doğrulanamadı</span></div></section>';
        }
        $html .= '<section class="w3-domain-info" aria-label="Domain sorgulama hakkında"><div><h2>Sonuçları nasıl kontrol ediyoruz?</h2><p>Alan adı kayıt servislerine başvururuz; yalnızca DNS veya sitenin açılıp açılmadığına bakmayız. Servis yanıt vermediğinde sonucu “Doğrulanamadı” olarak gösteririz.</p><p class="w3-domain-sources">Kaynaklar: <a href="https://data.iana.org/rdap/dns.json" target="_blank" rel="noopener noreferrer">IANA / RDAP</a> · <a href="https://www.trabis.gov.tr/whois" target="_blank" rel="noopener noreferrer">TRABİS</a> · Yetkili Whois servisleri</p></div><div><h2>Domain sorgulama ile Whois farkı</h2><p>Bu araç, aynı ismin farklı uzantılardaki kayıt durumunu karşılaştırır. Whois ise tek bir alan adının kayıt firması, tarihleri ve nameserver bilgilerini gösterir.</p><a class="w3-domain-info-link" href="' . qa_path_html('whois-sorgulama') . '">Whois sorgulama aracına git →</a></div></section></main>';
        $content['custom'] = $html;
        return $content;
    }
}
