<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class w3_dns_records_page
{
    private $directory;
    private $url;
    public function load_module($directory, $urltoroot) { $this->directory = $directory; $this->url = $urltoroot; }
    public function suggest_requests() { return array(array('title'=>'DNS Kayıt Sorgulama', 'request'=>'dns-kayit-sorgulama', 'nav'=>null)); }
    public function match_request($request) { return $request === 'dns-kayit-sorgulama'; }
    public function process_request($request)
    {
        if (!$this->match_request($request)) { return null; }
        $input = qa_get('domain'); $selection = qa_get('type');
        $selection = $selection === null ? 'ALL' : (is_string($selection) ? strtoupper($selection) : '');
        $api = qa_get('format') === 'json'; $domain = ''; $error = ''; $service = null; $initial = null;
        if ($input !== null || $api) {
            header('Cache-Control: private, no-store, max-age=0'); header('CDN-Cache-Control: no-store'); header('X-Robots-Tag: noindex, follow'); header('Referrer-Policy: no-referrer');
        }
        if (is_readable($this->directory . 'dns-service.php')) {
            require_once $this->directory . 'dns-service.php';
            $cache = new Q2A_Storage_FileCacheDriver(array('enabled'=>true, 'dir'=>QA_BASE_DIR . 'qa-cache', 'keyprefix'=>'w3-dns-v1:'));
            $service = new W3DnsRecordsService($cache, QA_BASE_DIR . 'qa-cache');
            $types = W3DnsRecordsService::types();
            if (($selection !== 'ALL' && !isset($types[$selection])) || ($api && $selection === 'ALL')) { $error = 'Desteklenen bir DNS kayıt türü seçin.'; }
            if (($input !== null || $api) && !$error) {
                try { $domain = $service->normalize($input); }
                catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
            }
        } else { $types = array(); $error = 'DNS aracı şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.'; }
        if ($api) {
            header('Content-Type: application/json; charset=utf-8'); header('X-Content-Type-Options: nosniff');
            $result = array('domain'=>$domain, 'type'=>$selection, 'state'=>'error', 'message'=>$error, 'records'=>array());
            if (!$error && $service) { $result = $service->lookup($domain, $selection, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'); }
            http_response_code($error ? 400 : $result['http_status']);
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            qa_exit('w3-dns-json');
        }
        if ($domain && !$error && $selection !== 'ALL') { $initial = $service->lookup($domain, $selection, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'); }
        $content = qa_content_prepare();
        $content['title'] = 'DNS Kayıt Sorgulama';
        $content['description'] = 'Alan adınızın A, AAAA, CNAME, MX, NS, TXT, SOA, CAA ve SRV kayıtlarını sorgulayın. DNS değerlerini ve TTL sürelerini inceleyip kopyalayın.';
        $content['canonical'] = qa_path_html('dns-kayit-sorgulama', null, qa_opt('site_url'));
        $content['w3_tool_page'] = true; $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $content['css_src'][] = $this->url . 'dns.css?v=2';
        $content['script'][] = '<script defer src="' . qa_html($this->url . 'dns.js?v=1') . '"></script>';
        $html = '<main class="w3-dns"><header class="w3-dns-heading"><h1>DNS Kayıt Sorgulama</h1><p>Alan adınızın web, e-posta ve DNS yapılandırmasını inceleyin.</p></header>';
        $html .= '<section class="w3-dns-search" aria-label="DNS sorgusu"><form action="' . qa_path_html('dns-kayit-sorgulama') . '" method="get"><div class="w3-dns-domain-field"><label for="w3-dns-domain">Alan adı veya alt alan adı</label><input id="w3-dns-domain" name="domain" value="' . qa_html(is_string($input) ? substr($input, 0, 1024) : '') . '" placeholder="ornek.com" maxlength="253" type="text" required spellcheck="false" autocomplete="off" autocapitalize="none" aria-describedby="w3-dns-help' . ($error ? ' w3-dns-error' : '') . '"' . ($error ? ' aria-invalid="true" autofocus' : '') . '></div><div class="w3-dns-type-field"><label for="w3-dns-type">Kayıt türü</label><select id="w3-dns-type" name="type"><option value="ALL"' . ($selection === 'ALL' ? ' selected' : '') . '>Tüm kayıtlar</option>';
        foreach ($types as $type=>$number) { $html .= '<option value="' . $type . '"' . ($selection === $type ? ' selected' : '') . '>' . $type . '</option>'; }
        $html .= '</select></div><button class="w3-dns-primary" type="submit">DNS sorgula <span aria-hidden="true">→</span></button></form><p id="w3-dns-help">Örnek: <a href="' . qa_path_html('dns-kayit-sorgulama', array('domain'=>'w3uzman.com')) . '">w3uzman.com</a> · www ve diğer alt alan adları korunur. SRV için hizmet adını da yazın.</p>';
        if ($error) { $html .= '<p id="w3-dns-error" class="w3-dns-error" role="alert">' . qa_html($error) . '</p>'; }
        $html .= '</section>';
        $descriptions = $service ? W3DnsRecordsService::descriptions() : array();
        if ($domain && !$error) {
            $queryTypes = $selection === 'ALL' ? array_keys($types) : array($selection);
            $html .= '<section class="w3-dns-results" data-domain="' . qa_html($domain) . '" data-endpoint="' . qa_path_html('dns-kayit-sorgulama') . '" aria-labelledby="w3-dns-result-title"><header class="w3-dns-results-heading"><div><h2 id="w3-dns-result-title">' . qa_html($domain) . '</h2><p id="w3-dns-progress" role="status" aria-live="polite">' . ($initial ? 'Sorgu tamamlandı.' : 'DNS kayıtları sorgulanacak.') . '</p></div><button id="w3-dns-copy-all" type="button" hidden>Tümünü kopyala</button></header><p id="w3-dns-copy-status" class="w3-dns-reader-only" role="status"></p><div class="w3-dns-filters" aria-label="Kayıt türünü filtrele" role="group" hidden><button type="button" data-filter="ALL" aria-pressed="true">Tümü</button>';
            foreach ($queryTypes as $type) { $html .= '<button type="button" data-filter="' . $type . '" aria-pressed="false">' . $type . ' <span data-count="' . $type . '">…</span></button>'; }
            $html .= '</div><div class="w3-dns-groups">';
            foreach ($queryTypes as $type) { $html .= $this->groupHtml($domain, $type, $descriptions[$type], $initial); }
            $html .= '</div><noscript><p class="w3-dns-note">Tüm kayıtları birlikte görmek için JavaScript’i etkinleştirin. JavaScript kapalıyken formdan tek bir kayıt türü seçerek sorgulayabilirsiniz.</p></noscript><p class="w3-dns-source">Çözümleyici: Google Public DNS · TTL saniye cinsindedir. Sonuçlar DNS önbellekleri ve konuma göre değişebilir; bu ekran dünya çapında yayılım testi değildir.</p><div class="w3-dns-related"><a href="' . qa_path_html('whois-sorgulama', array('domain'=>$domain)) . '">Whois bilgilerini incele ↗</a><a href="' . qa_path_html('domain-sorgulama') . '">Domain müsaitliği sorgula ↗</a></div></section>';
        } else {
            $html .= '<section class="w3-dns-empty" aria-label="Desteklenen DNS kayıtları"><span class="w3-dns-empty-icon" aria-hidden="true">DNS</span><h2>Alan adınızın arkasındaki kayıtları görün.</h2><p>Bir alan adı girin; web sunucusundan e-posta kayıtlarına kadar yapılandırmasını tek ekranda inceleyin.</p><div class="w3-dns-type-tags">';
            foreach ($types as $type=>$number) { $html .= '<span>' . $type . '</span>'; }
            $html .= '</div></section>';
        }
        $html .= '<section class="w3-dns-guide" aria-label="DNS kayıtlarını anlama"><div><h2>Hangi kayıt ne işe yarar?</h2><p><strong>A / AAAA:</strong> web adresinin IPv4 / IPv6 hedefleri. <strong>CNAME:</strong> başka bir ada takma ad. <strong>MX:</strong> e-posta sunucuları. <strong>NS:</strong> yetkili DNS sunucuları. <strong>TXT:</strong> doğrulama ve SPF gibi metinler.</p><p><strong>SOA:</strong> bölge yönetimi. <strong>CAA:</strong> sertifika düzenleme yetkileri. <strong>SRV:</strong> hizmet sunucusu ve portu.</p></div><div><h2>Bir kayıt görünmüyorsa</h2><p>Her kayıt türü her alan adında bulunmaz. DKIM, DMARC ve SRV kayıtları için doğru alt alan adını sorgulamak gerekir; örneğin <code>_dmarc.ornek.com</code>. DNS hatası ile kaydın bulunmaması farklı durumlardır.</p><p>TXT değerleri çözümleyicinin döndürdüğü metin biçimiyle gösterilir. DNSSEC etiketi yalnızca çözümleyicinin o yanıt için doğrulama bildirdiği durumda görünür.</p></div></section><p class="w3-dns-privacy">Sorgulanan ad Google Public DNS’e gönderilir; ziyaretçinin IP adresi sorguya eklenmez. <a href="https://developers.google.com/speed/public-dns/docs/doh/json" target="_blank" rel="noopener noreferrer">Veri kaynağı ve API açıklaması ↗</a></p></main>';
        $content['custom'] = $html;
        return $content;
    }
    private function groupHtml($domain, $type, $description, $result)
    {
        $records = $result ? $result['records'] : array();
        $html = '<details class="w3-dns-group" data-type="' . $type . '" data-state="' . ($result ? qa_html($result['state']) : 'pending') . '"' . ($records || ($result && $result['state'] === 'error') ? ' open' : '') . '><summary><span class="w3-dns-type">' . $type . '</span><span class="w3-dns-group-description">' . qa_html($description) . '</span><span class="w3-dns-group-count">' . ($result ? count($records) . ' kayıt' : 'Bekliyor') . '</span><span class="w3-dns-chevron" aria-hidden="true"></span></summary><div class="w3-dns-group-body"><p class="w3-dns-group-message">' . qa_html($result ? $result['message'] : 'DNS kaydı sorgulanıyor…') . '</p><div class="w3-dns-table-wrap"' . (!$records ? ' hidden' : '') . '><table><caption class="w3-dns-reader-only">' . qa_html($domain . ' ' . $type . ' sorgu sonuçları') . '</caption><thead><tr><th scope="col">Kayıt adı / tür</th><th scope="col">Değer</th><th scope="col">TTL</th><th scope="col"><span class="w3-dns-reader-only">Kopyala</span></th></tr></thead><tbody>';
        foreach ($records as $record) {
            $html .= '<tr><td class="w3-dns-record-name">' . qa_html($record['name']) . '<small>' . qa_html($record['type']) . '</small></td><td class="w3-dns-record-value"><code>' . qa_html($record['data']) . '</code></td><td class="w3-dns-record-ttl">' . qa_html($record['ttl']) . '<small>sn</small></td><td class="w3-dns-record-action"><button type="button" class="w3-dns-copy-value" aria-label="' . qa_html($type . ' kayıt değerini kopyala') . '" hidden>Kopyala</button></td></tr>';
        }
        $html .= '</tbody></table></div><p class="w3-dns-group-meta">' . ($result ? qa_html('Google Public DNS · ' . gmdate('H:i:s', $result['checked']) . ' UTC' . ($result['cached'] ? ' · Önbellekten' : '') . (!empty($result['dnssec']) ? ' · DNSSEC doğrulandı' : '')) : '') . '</p><button class="w3-dns-retry" type="button"' . (!$result || $result['state'] !== 'error' ? ' hidden' : '') . '>Tekrar dene</button></div></details>';
        if ($result) { $html .= '<script type="application/json" class="w3-dns-initial">' . json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>'; }
        return $html;
    }
}
