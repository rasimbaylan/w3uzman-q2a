<?php
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

class w3_whatismyip_page
{
    private $url;

    public function load_module($directory, $urltoroot)
    {
        $this->url = $urltoroot;
    }

    public function suggest_requests()
    {
        return array(array('title' => 'WhatisMyip', 'request' => 'whatismyip', 'nav' => null));
    }

    public function match_request($request)
    {
        return $request === 'whatismyip';
    }

    private function service()
    {
        if (!class_exists('w3_ip_lookup_service', false)) {
            $file = __DIR__ . '/ip-service.php';
            if (!is_readable($file)) { return null; }
            require_once $file;
        }
        return new w3_ip_lookup_service();
    }

    /** Proxy headers are ignored; configure trusted proxies in the web server. */
    public function connection_address($server)
    {
        $service = $this->service();
        return $service ? $service->address(isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '')
            : array('address' => '', 'version' => '', 'local' => false);
    }

    private function field($label, $value, $wide = false, $mono = false)
    {
        return '<div class="w3-ip-field' . ($wide ? ' w3-ip-field-wide' : '') . '"><dt>' . qa_html($label)
            . '</dt><dd' . ($mono ? ' class="w3-ip-mono"' : '') . '>'
            . ($value === '' ? '<span class="w3-ip-unavailable">Bilgi yok</span>' : qa_html($value)) . '</dd></div>';
    }

    private function icon($name, $size = 18)
    {
        $paths = array(
            'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>',
            'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.8"/>',
            'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
            'globe' => '<circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18M5 6h14M5 18h14"/>',
        );
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
    }

    public function process_request($request)
    {
        if (!$this->match_request($request)) {
            return null;
        }
        header('Cache-Control: private, no-store, no-cache, max-age=0, must-revalidate');
        header('CDN-Cache-Control: no-store');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        $service = $this->service();
        if (!$service) {
            $content = qa_content_prepare();
            $content['title'] = 'IP Adresim Nedir?';
            $content['error'] = 'IP sorgulama aracı şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.';
            return $content;
        }
        $connection = $this->connection_address($_SERVER);
        $ip = $connection;
        $query = isset($_GET['ip']) && is_string($_GET['ip']) ? trim($_GET['ip']) : '';
        $error = '';
        $isLookup = false;
        if (isset($_GET['ip'])) {
            header('X-Robots-Tag: noindex, follow');
            $candidate = $service->address($query);
            if (!is_string($_GET['ip']) || ($query !== '' && $candidate['address'] === '')) {
                $error = 'Geçerli bir IPv4 veya IPv6 adresi girin. Örnek: 8.8.8.8';
            } elseif ($query !== '' && $candidate['local']) {
                $error = 'Konum sorgulamak için genel bir internet IP adresi girin. Yerel ve ayrılmış adresler için konum bulunmaz.';
            } elseif ($query !== '') {
                $ip = $candidate;
                $isLookup = true;
            }
        }
        $geo = $service->lookup($ip);
        $hasMap = $geo['latitude'] !== null && $geo['longitude'] !== null;
        $coordinates = $hasMap ? number_format($geo['latitude'], 4, '.', '') . ', ' . number_format($geo['longitude'], 4, '.', '') : '';
        $mapLink = $hasMap ? 'https://www.openstreetmap.org/?mlat=' . rawurlencode((string) $geo['latitude'])
            . '&mlon=' . rawurlencode((string) $geo['longitude']) . '#map=10/'
            . rawurlencode((string) $geo['latitude']) . '/' . rawurlencode((string) $geo['longitude']) : '';
        $place = implode(', ', array_filter(array($geo['city'], $geo['country'])));
        $content = qa_content_prepare();
        $content['title'] = 'IP Adresim Nedir? | WhatisMyip';
        $content['description'] = 'IP adresinizi, yaklaşık konumunuzu ve ağ sağlayıcınızı öğrenin. IPv4 ve IPv6 adreslerini ücretsiz sorgulayın, IP konumunu haritada görüntüleyin.';
        $content['canonical'] = qa_path_html('whatismyip', null, qa_opt('site_url'));
        $content['w3_tool_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $content['css_src'][] = $this->url . 'ip.css?v=6';
        $content['script'][] = '<script defer src="' . qa_html($this->url . 'ip.js?v=5') . '"></script>';

        $html = '<div class="w3-ip" id="w3-ip-tool">';
        $html .= '<header class="w3-ip-heading"><div><h1>IP adresim nedir?</h1><p>IP adresinizi, yaklaşık konumunuzu ve ağ bilgilerinizi görün.</p></div></header>';
        if ($isLookup && $connection['address'] !== '') {
            $html .= '<p class="w3-ip-connection">Bağlantınızın IP adresi: <strong dir="ltr">' . qa_html($connection['address']) . '</strong><span>' . $connection['version'] . ($connection['local'] ? ' · Yerel / ayrılmış adres' : '') . '</span><a href="' . qa_path_html('whatismyip') . '">Kendi IP adresime dön <span aria-hidden="true">↗</span></a></p>';
        }
        $html .= '<section class="w3-ip-dashboard" aria-labelledby="w3-ip-label"><div class="w3-ip-result"><div class="w3-ip-card-top"><h2 id="w3-ip-label">' . ($isLookup ? 'Sorgulanan IP adresi' : 'Bağlantınızın IP adresi') . '</h2>';
        if ($ip['version'] !== '') {
            $html .= '<span class="w3-ip-version">' . $ip['version'] . '</span>';
        }
        $html .= '</div>';
        if ($ip['address'] !== '') {
            $html .= '<output class="w3-ip-address" id="w3-ip-address" aria-label="' . ($isLookup ? 'Sorgulanan IP adresi' : 'IP adresiniz') . '" dir="ltr">' . qa_html($ip['address']) . '</output>';
            $html .= '<p class="w3-ip-address-caption">' . ($isLookup ? 'Bu adres için bulunan konum ve ağ bilgileri.' : 'Bu siteye bağlanırken görünen adresiniz.') . '</p>';
            $html .= '<div class="w3-ip-actions"><button type="button" id="w3-ip-copy" hidden>' . $this->icon('copy') . '<span>IP adresini kopyala</span></button><a class="w3-ip-refresh" href="' . qa_path_html('whatismyip', $isLookup ? array('ip' => $ip['address']) : null) . '" rel="nofollow">Yeniden kontrol et <span aria-hidden="true">↻</span></a></div><p class="w3-ip-status" id="w3-ip-status" role="status" aria-live="polite"></p>';
        } else {
            $html .= '<p class="w3-ip-error" role="status">Bağlantınızın IP adresi belirlenemedi. Sayfayı yenileyebilir veya aşağıdan bir IP adresi sorgulayabilirsiniz.</p>';
        }
        if ($geo['status'] === 'private') {
            $html .= '<div class="w3-ip-notice"><strong>Yerel / ayrılmış ağ adresi</strong><p>Bu adresin internette bir konumu yok. İnternet çıkış IP adresiniz farklı olabilir. Genel bir IP adresini aşağıdan sorgulayabilirsiniz.</p></div>';
        } else {
            $html .= '<dl class="w3-ip-fields">' . $this->field('Ülke', $geo['country']) . $this->field('Bölge / İl', $geo['region']) . $this->field('Şehir', $geo['city']) . $this->field('ASN', $geo['asn'], false, true) . $this->field('Ağ sağlayıcısı', $geo['provider'], true) . $this->field('Yaklaşık koordinatlar', $coordinates, true, true);
            if ($geo['timezone'] !== '') {
                $html .= $this->field('Saat dilimi', $geo['timezone'], true);
            }
            $html .= '</dl>';
            if ($geo['status'] === 'unavailable') {
                $html .= '<p class="w3-ip-data-note">Konum ve ağ bilgileri şu anda kullanılamıyor. IP adresiniz yukarıda gösterilmeye devam eder.</p>';
            } elseif ($geo['status'] === 'no_data') {
                $html .= '<p class="w3-ip-data-note">Bu IP adresi için konum veya ağ kaydı bulunamadı.</p>';
            } elseif ($geo['status'] === 'partial') {
                $html .= '<p class="w3-ip-data-note">Bazı konum veya ağ bilgileri şu anda kullanılamıyor.</p>';
            }
        }
        $html .= '<noscript><p class="w3-ip-noscript">IP adresini seçerek elle kopyalayabilirsiniz.</p></noscript></div>';
        $html .= '<div class="w3-ip-map-panel"><div class="w3-ip-map-heading"><h2>Yaklaşık konum</h2>' . ($hasMap ? '<a href="' . qa_html($mapLink) . '" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer">Haritada aç ↗</a>' : '<span>Konum yok</span>') . '</div><div class="w3-ip-map" id="w3-ip-map"' . ($hasMap ? ' data-lat="' . qa_html((string) $geo['latitude']) . '" data-lon="' . qa_html((string) $geo['longitude']) . '"' : '') . '><div class="w3-ip-map-placeholder' . ($hasMap ? '' : ' w3-ip-map-empty') . '"><span class="w3-ip-map-symbol">' . $this->icon('pin', 31) . '</span><h3>' . ($hasMap ? qa_html($place !== '' ? $place : 'IP adresinin yaklaşık konumu') : 'Konum bilgisi bulunamadı') . '</h3><p>' . ($hasMap ? 'IP konumunu harita üzerinde inceleyin.' : 'Genel bir IP adresini sorgulayarak konumunu burada görüntüleyebilirsiniz.') . '</p>';
        if ($hasMap) {
            $html .= '<button type="button" class="w3-ip-map-button" id="w3-ip-show-map" hidden>Haritayı göster <span aria-hidden="true">↗</span></button><span class="w3-ip-map-coordinate" dir="ltr">' . qa_html($coordinates) . '</span>';
        }
        $html .= '</div></div><p class="w3-ip-map-note">' . ($hasMap ? 'Haritayı açtığınızda OpenStreetMap’e bağlanılır. ' : '') . 'IP konumu tahminidir; ev veya sokak adresinizi göstermez.</p></div></section>';
        $html .= '<section class="w3-ip-lookup" aria-labelledby="w3-ip-lookup-title"><div><h2 id="w3-ip-lookup-title">Başka bir IP adresini sorgula</h2><p>Genel IPv4 veya IPv6 adresinin konumunu ve ağını öğrenin.</p></div><form method="get" action="' . qa_path_html('whatismyip') . '" class="w3-ip-form">' . qa_path_form_html('whatismyip') . '<label class="w3-ip-visually-hidden" for="w3-ip-query">Sorgulanacak IP adresi</label><div class="w3-ip-form-row"><input type="text" id="w3-ip-query" name="ip" value="' . qa_html(substr($query, 0, 64)) . '" placeholder="Örnek: 8.8.8.8" maxlength="45" required spellcheck="false" autocomplete="off" autocapitalize="none" dir="ltr"' . ($error !== '' ? ' aria-invalid="true" aria-describedby="w3-ip-query-error" autofocus' : '') . '><button type="submit">' . $this->icon('search', 17) . 'Sorgula</button></div>';
        if ($error !== '') {
            $html .= '<p class="w3-ip-form-error" id="w3-ip-query-error" role="alert">' . qa_html($error) . '</p>';
        }
        $html .= '<p class="w3-ip-form-hint">Denemek için <a href="' . qa_path_html('whatismyip', array('ip' => '8.8.8.8')) . '" rel="nofollow">8.8.8.8</a> adresini kullanabilirsiniz.</p></form></section>';
        $html .= '<div class="w3-ip-source"><span>Konum ve ağ verisi: <a href="https://db-ip.com" target="_blank" rel="noopener noreferrer">IP Geolocation by DB-IP</a> · <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener noreferrer">CC BY 4.0</a>' . ($geo['release'] !== '' ? ' · ' . qa_html($geo['release']) : '') . '</span><span>IP sorguları bu sitede işlenir.</span></div>';
        $html .= '<div class="w3-ip-info"><section><span class="w3-ip-eyebrow">BAĞLANTINIZI TANIYIN</span><h2>IP adresi neyi gösterir?</h2><p>IP adresi, internette veri alışverişi için kullanılan ağ adresidir. Aynı modemden internete çıkan cihazlar tek bir dış IP adresini paylaşabilir.</p><p>Buradaki IPv4 veya IPv6 etiketi yalnızca bu istekte kullanılan adresi belirtir. Ağınızın her iki sürümü de destekleyip desteklemediğini ölçmez.</p><a class="w3-ip-related" href="' . qa_path_html('whois-sorgulama') . '">Alan adı bilgileri için Whois sorgulama <span aria-hidden="true">→</span></a></section><section class="w3-ip-faq" aria-labelledby="w3-ip-faq-title"><h2 id="w3-ip-faq-title">Merak edilenler</h2><details><summary>Haritadaki konum ne kadar doğru?</summary><p>Konum IP bloklarına göre tahmin edilir. Sağlayıcınızın ağ merkezi veya başka bir şehir görünebilir. Özellikle mobil ağlarda ve VPN bağlantılarında gerçek konumunuzdan uzak olabilir.</p></details><details><summary>VPN kullanıyorsam hangi IP görünür?</summary><p>Genellikle VPN veya proxy hizmetinin çıkış IP adresi görünür. Gösterilen konum da bu çıkış adresine ait tahmindir.</p></details><details><summary>Ağ sağlayıcısı internet şirketim mi?</summary><p>Bu alan IP adresini duyuran ağın (ASN) kuruluşunu gösterir. Altyapı, üst sağlayıcı veya VPN şirketi olabilir; aboneliğinizin bulunduğu şirketle her zaman aynı değildir.</p></details></section></div></div>';
        $content['custom'] = $html;
        return $content;
    }
}
