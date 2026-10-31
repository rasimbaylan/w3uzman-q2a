<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class w3_whois_page
{
    private $directory;
    private $url;
    public function load_module($directory, $urltoroot) { $this->directory = $directory; $this->url = $urltoroot; }
    public function suggest_requests() { return array(array('title' => 'Whois Sorgulama', 'request' => 'whois-sorgulama', 'nav' => null)); }
    public function match_request($request) { return $request === 'whois-sorgulama' || $request === 'whois'; }

    public function process_request($request)
    {
        if ($request === 'whois') {
            $domain = qa_get('domain');
            $params = is_string($domain) ? array('domain' => $domain) : null;
            header('Location: ' . qa_path('whois-sorgulama', $params), true, 301);
            qa_exit('redirect');
        }
        require_once $this->directory . 'w3-whois-service.php';
        $cache = new Q2A_Storage_FileCacheDriver(array('enabled' => true, 'dir' => QA_BASE_DIR . 'qa-cache', 'keyprefix' => 'w3-whois-v2:'));
        $service = new W3WhoisService($cache);
        $input = qa_get('domain');
        $domain = is_string($input) ? trim($input) : '';
        $result = null;
        if ($input !== null) {
            header('X-Robots-Tag: noindex, follow');
            try {
                $domain = $service->normalize($input);
                $result = $service->lookup($domain, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown', qa_get('format') !== 'status');
            } catch (InvalidArgumentException $error) {
                $result = array('state' => 'error', 'message' => $error->getMessage());
            }
        }
        // Independent status checks reuse the validated, cached registry lookup.
        if (qa_get('format') === 'status') {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Robots-Tag: noindex, follow');
            $status = $this->availability($domain, $result ?: array('state' => 'error'));
            echo json_encode(array('domain' => $domain, 'state' => $status['state'], 'label' => $status['label'], 'message' => $result && isset($result['message']) ? $result['message'] : ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
            qa_exit('whois-status');
        }
        $content = qa_content_prepare();
        $content['title'] = 'Whois Sorgulama';
        $content['whois_intro'] = 'Alan adı kayıt tarihlerini, kayıt kuruluşunu ve DNS bilgilerini sorgulayın.';
        $content['canonical'] = qa_path_html('whois-sorgulama', null, qa_opt('site_url'));
        $content['description'] = 'Alan adlarının kayıt kuruluşunu, kayıt ve bitiş tarihlerini, nameserver bilgilerini W3Uzman Whois aracıyla sorgulayın.';
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat']);
        $content['script'][] = '<script defer src="' . qa_html($this->url . 'whois.js?v=4') . '"></script>';
        $content['css_src'][] = $this->url . 'whois-contacts.css?v=2';
        $html = '<div class="w3-whois">';
        $html .= '<section class="w3-whois-query" aria-label="Alan adı sorgulama">';
        $html .= '<form action="' . qa_path_html('whois-sorgulama') . '" method="get" id="w3-whois-form"><label for="w3-whois-domain">Domain sorgula</label><div class="w3-whois-field"><div class="w3-whois-input-wrap"><input id="w3-whois-domain" name="domain" type="text" value="' . qa_html($domain) . '" placeholder="ornek.com" maxlength="253" required autocapitalize="none" autocomplete="off" spellcheck="false" aria-describedby="w3-whois-help"><button id="w3-whois-clear" type="button" aria-label="Alan adını temizle"' . ($domain === '' ? ' hidden' : '') . '>×</button><span class="w3-whois-suffix" aria-hidden="true">.com / .net / .tr</span></div><button type="submit"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"></circle><path d="m16 16 4.5 4.5"></path></svg>Sorgula</button></div></form>';
        if ($result && $result['state'] !== 'error') { $html .= $this->alternativesHtml($domain, $result); }
        $html .= '<div class="w3-whois-examples"><span>Örnekler:</span>';
        foreach (array('w3uzman.com', 'example.net', 'example.org') as $example) {
            $html .= '<a href="' . qa_path_html('whois-sorgulama', array('domain' => $example)) . '">' . $example . '</a>';
        }
        $html .= '<span id="w3-whois-help">Uzantı yazılmazsa .com eklenir.</span></div>';
        $html .= '</section>';
        if ($result) {
            $html .= $this->resultHtml($domain, $result);
        }
        $html .= '<div class="w3-whois-info"><section><span class="w3-whois-eyebrow">KAYIT BİLGİLERİNİ ANLAYIN</span><h2>Whois ile neleri öğrenebilirsiniz?</h2><p>Whois, bir alan adının kayıt bilgilerini incelemenizi sağlar. Bu araç, desteklenen uzantılarda modern RDAP kayıt servislerini; .tr alan adlarında ise TRABİS Whois servisini kullanır.</p><div class="w3-whois-features"><div><b>Kayıt tarihleri</b><p>Alan adının ne zaman kaydedildiğini ve bildirilen bitiş tarihini görün.</p></div><div><b>Kayıt kuruluşu</b><p>Alan adının hangi kayıt kuruluşunda yönetildiğini öğrenin.</p></div><div><b>Nameserver bilgileri</b><p>Alan adının yetkili DNS sunucularını ve kayıt durum kodlarını inceleyin.</p></div></div></section><aside><h2>Bilmeniz gerekenler</h2><details><summary>Alan adı sahibini görebilir miyim?</summary><p>Gizlilik politikaları nedeniyle kişi ve iletişim bilgileri çoğu kayıtta gizlenir. Araç, kayıt servisinin kamuya açtığı bilgileri gösterir.</p></details><details><summary>“Kayıt edilebilir” ne demek?</summary><p>Ön kontrolde alan adına ait kayıt görülmediğini belirtir; satın alınabilirlik garantisi değildir. Rezerve adlar, tahsis kısıtları ve sonradan yapılan kayıtlar nedeniyle kesin müsaitliği kayıt kuruluşundan doğrulayın.</p></details><details><summary>Bilgiler ne kadar güncel?</summary><p>Başarılı sorgular 10 dakika önbellekte tutulur. Tarihler kayıt servisinin bildirdiği şekliyle gösterilir; tüm alanlar her uzantıda sunulmayabilir.</p></details></aside></div><p class="w3-whois-footnote">Bu araç bilgilendirme amaçlıdır. Veri kaynağı kayıt servisidir; gizlenmiş bilgiler çözümlenmez ve alan adı satışı yapılmaz.</p></div>';
        $content['custom'] = $html;
        return $content;
    }

    private function resultHtml($domain, $r)
    {
        if ($r['state'] === 'error') {
            return '<section class="w3-whois-notice w3-whois-notice--error" role="status"><h2>Sorgu tamamlanamadı</h2><p>' . qa_html($r['message']) . '</p></section>';
        }
        $status = $this->availability($domain, $r);
        $html = '<section class="w3-whois-result w3-whois-result--' . $status['state'] . '" aria-labelledby="w3-whois-result-title"><header class="w3-whois-result-header"><span class="w3-whois-status w3-whois-status--' . $status['state'] . '">' . $status['label'] . '</span><h2 id="w3-whois-result-title">' . qa_html($domain) . '</h2><p>Kayıt ve yenileme bilgileri</p></header>';
        if ($r['state'] === 'not-found') {
            $html .= '<p class="w3-whois-availability-note">' . ($status['state'] === 'available' ? 'Ön kontrole göre bu alan adı kayıt edilebilir görünüyor. Rezerve adlar, özel tahsis koşulları ve anlık değişiklikler nedeniyle kesin müsaitlik kayıt kuruluşunda doğrulanmalıdır.' : 'Bu ad için kayıt bulunamadı, ancak kayıt edilebilirliği doğrulanamadı. Alt alan adları ve özel tahsis kuralları için kayıt kuruluşundan bilgi alın.') . '</p>';
            $html .= '<div class="w3-whois-source">Kaynak: ' . qa_html($r['source']) . ' · Sorgulama: ' . gmdate('d.m.Y H:i', $r['checked']) . ' UTC · Ön kontrol, en fazla 10 dakika önbellek</div></section>';
            return $html;
        }
        $html .= '<dl class="w3-whois-metrics">';
        foreach (array('registrar' => 'Kayıt firması', 'created' => 'İlk kayıt', 'expires' => 'Yenileme tarihi', 'updated' => 'Son güncelleme') as $key => $label) {
            $value = $r[$key];
            $subtext = $key === 'registrar' ? 'Yetkili kayıt kuruluşu' : ($key === 'created' ? 'Tescil tarihi (UTC)' : ($key === 'expires' ? 'Bitiş tarihi (UTC)' : 'Kayıt güncellemesi (UTC)'));
            if ($key !== 'registrar' && $value && ($timestamp = strtotime($value)) !== false) {
                $value = gmdate('d.m.Y H:i', $timestamp);
                if ($key === 'expires') { $days = (int) ceil(($timestamp - time()) / 86400); $subtext = $days >= 0 ? $days . ' gün kaldı' : 'Bildirilen bitiş tarihi geçmiş'; }
            }
            $html .= '<div><dt>' . $label . '</dt><dd>' . qa_html($value ?: 'Paylaşılmamış') . '</dd><small' . ($key === 'expires' && isset($days) && $days >= 0 ? ' class="w3-whois-days"' : '') . '>' . qa_html($subtext) . '</small></div>';
        }
        $html .= '</dl>' . $this->contactsHtml($r) . '<div class="w3-whois-technical"><section class="w3-whois-tech-card"><h3>Nameserverlar</h3><ul class="w3-whois-nameservers">';
        foreach (array_unique($r['nameservers']) as $ns) { $html .= '<li>' . qa_html($ns) . '</li>'; }
        if (!$r['nameservers']) { $html .= '<li>Kayıt servisi bu bilgiyi paylaşmamış.</li>'; }
        $html .= '</ul></section><section class="w3-whois-tech-card w3-whois-related" data-current-domain="' . qa_html($domain) . '"><div class="w3-whois-tech-heading"><h3>Kayıtlı diğer uzantılar</h3><span id="w3-whois-related-count">Kontrol ediliyor…</span></div><ul id="w3-whois-related-list"></ul><p id="w3-whois-related-message" role="status">Diğer uzantılar kontrol ediliyor.</p></section></div>';
        $statuses = array_filter($r['statuses'], 'is_string');
        if ($statuses) { $html .= '<p class="w3-whois-domain-status"><span>Alan adı durumu:</span> ' . qa_html(implode(' · ', $statuses)) . '</p>'; }
        $html .= '<details class="w3-whois-raw"><summary>Ham WHOIS / RDAP kaydı</summary><div class="w3-whois-raw-toolbar"><span>Sunucudan gelen kayıt yanıtı</span><button type="button" id="w3-whois-copy">Kopyala</button></div><span id="w3-whois-copy-status" role="status"></span><pre id="w3-whois-raw" tabindex="0" aria-label="Ham kayıt yanıtı">';
        foreach (preg_split('/\r\n|\r|\n/', $r['raw']) as $line) { $html .= '<span class="w3-whois-code-line">' . qa_html($line) . '</span>'; }
        $html .= '</pre></details><div class="w3-whois-source">Kaynak: ' . qa_html($r['source']) . ' · Sorgulama: ' . gmdate('d.m.Y H:i', $r['checked']) . ' UTC · En fazla 10 dakika önbellek</div></section>';
        return $html;
    }

    private function contactsHtml($result)
    {
        $info = isset($result['registrar_info']) && is_array($result['registrar_info']) ? $result['registrar_info'] : array();
        $registrar = array('name' => !empty($info['name']) ? $info['name'] : (!empty($result['registrar']) ? $result['registrar'] : 'Paylaşılmamış'));
        foreach (array('iana_id', 'abuse_email', 'abuse_phone') as $key) { if (!empty($info[$key])) { $registrar[$key] = $info[$key]; } }
        $cards = array($this->contactCard('Kayıt firması ve şikâyet iletişimi', $registrar, 'registrar'));
        $contacts = isset($result['contacts']) && is_array($result['contacts']) ? $result['contacts'] : array();
        $labels = array('registrant' => 'Alan adı sahibi iletişim bilgileri', 'administrative' => 'İdari iletişim bilgileri', 'technical' => 'Teknik iletişim bilgileri');
        $hasRegistrant = false;
        foreach ($contacts as $contact) { if (isset($contact['role']) && $contact['role'] === 'registrant') { $hasRegistrant = true; } }
        if (!$hasRegistrant) {
            $message = !empty($result['contact_lookup']) && $result['contact_lookup'] === 'unavailable'
                ? 'Kayıt kuruluşunun iletişim servisine şu anda ulaşılamadı veya yanıt doğrulanamadı. Temel alan adı bilgileri gösterilmeye devam ediyor.'
                : 'Alan adı sahibi iletişim bilgileri bu yanıtta paylaşılmamış. Gizlilik nedeniyle gizlenmiş veya kayıt servisi tarafından yayımlanmamış olabilir.';
            $cards[] = $this->contactCard($labels['registrant'], array(), 'registrant', $message);
        }
        foreach ($contacts as $contact) {
            if (!is_array($contact) || !isset($contact['role'], $contact['fields'], $labels[$contact['role']]) || !is_array($contact['fields'])) { continue; }
            $note = !empty($result['redacted']) ? 'Kaynak, bazı alanların gizlendiğini bildiriyor. Yalnızca paylaşılan alanlar gösteriliyor.' : '';
            $cards[] = $this->contactCard($labels[$contact['role']], $contact['fields'], $contact['role'], $note);
        }
        $html = '<div class="w3-whois-contacts" data-card-count="' . count($cards) . '" aria-label="Kayıt ve iletişim bilgileri">' . implode('', $cards);
        $html .= '<p class="w3-whois-contact-source">Bilgiler yetkili kayıt servisinin herkese açık yanıtından alınır. ';
        if (!empty($result['registrar_url']) && filter_var($result['registrar_url'], FILTER_VALIDATE_URL) && parse_url($result['registrar_url'], PHP_URL_SCHEME) === 'https') {
            $html .= '<a href="' . qa_html($result['registrar_url']) . '" target="_blank" rel="noopener noreferrer">Kayıt kuruluşu kaynağı <span aria-hidden="true">↗</span></a>';
        }
        return $html . '</p></div>';
    }

    private function contactCard($title, $fields, $role, $note = '')
    {
        $icon = $role === 'registrar'
            ? '<path d="M4 21V7l8-4 8 4v14M2 21h20M9 21v-5h6v5M8 9h1m6 0h1M8 12h1m6 0h1"></path>'
            : '<circle cx="12" cy="7" r="3.5"></circle><path d="M5 21v-3a7 7 0 0 1 14 0v3"></path>';
        $html = '<section class="w3-whois-contact-card w3-whois-contact-' . $role . '"><h3><span class="w3-whois-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $icon . '</svg></span>' . qa_html($title) . '</h3>';
        $labels = array('name' => $role === 'registrar' ? 'Kayıt firması' : 'Ad / unvan', 'organization' => 'Kuruluş', 'iana_id' => 'IANA numarası', 'abuse_email' => 'Şikâyet e-postası', 'abuse_phone' => 'Şikâyet telefonu', 'street' => 'Adres', 'address' => 'Adres', 'city' => 'Şehir / ilçe', 'region' => 'İl / bölge', 'postal_code' => 'Posta kodu', 'country' => 'Ülke', 'phone' => 'Telefon', 'fax' => 'Faks', 'email' => 'E-posta', 'contact_url' => 'İletişim formu');
        if ($fields) {
            $html .= '<dl>';
            foreach ($labels as $key => $label) {
                if (!isset($fields[$key]) || $fields[$key] === '' || $fields[$key] === array()) { continue; }
                $values = is_array($fields[$key]) ? $fields[$key] : array($fields[$key]);
                $html .= '<div><dt>' . $label . '</dt><dd>';
                foreach ($values as $value) {
                    if (!is_string($value)) { continue; }
                    $display = qa_html($value); $href = null; $external = false;
                    if (in_array($key, array('email', 'abuse_email'), true) && filter_var($value, FILTER_VALIDATE_EMAIL)) { $href = 'mailto:' . $value; }
                    elseif (in_array($key, array('phone', 'abuse_phone', 'fax'), true) && preg_match('/^\+?[0-9][0-9 ().-]{4,40}$/D', $value)) { $href = 'tel:' . preg_replace('/[^0-9+]/', '', $value); }
                    elseif ($key === 'contact_url') {
                        $p = @parse_url($value);
                        if ($p && isset($p['scheme'], $p['host']) && in_array(strtolower($p['scheme']), array('http', 'https'), true) && !isset($p['user']) && !isset($p['pass']) && filter_var($value, FILTER_VALIDATE_URL)) { $href = $value; $external = true; $display = 'Kayıt kuruluşunun iletişim formu ↗'; }
                    }
                    $html .= '<span>' . ($href !== null ? '<a href="' . qa_html($href) . '"' . ($external ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . $display . '</a>' : $display) . '</span>';
                }
                $html .= '</dd></div>';
            }
            $html .= '</dl>';
        }
        if ($note !== '') { $html .= '<p class="w3-whois-contact-note">' . qa_html($note) . '</p>'; }
        return $html . '</section>';
    }

    private function baseName($domain)
    {
        // Do not turn an arbitrary subdomain into a purchasable domain suggestion.
        if (preg_match('/^([^.]+)\.(?:com|net|org|gen|biz|info|web|name|tv|av|dr|bbs|bel|edu|gov|k12|kep|pol|tsk)\.tr$/D', $domain, $m)) { return $m[1]; }
        if (preg_match('/^([^.]+)\.[^.]+$/D', $domain, $m)) { return $m[1]; }
        return null;
    }

    private function availability($domain, $result)
    {
        if ($result['state'] === 'registered') { return array('state' => 'registered', 'label' => 'Kayıtlı'); }
        $base = $this->baseName($domain);
        $trNameAllowed = substr($domain, -3) !== '.tr' || ($base !== null && strlen($base) >= 2 && substr($base, 2, 2) !== '--');
        if ($result['state'] === 'not-found' && $base !== null && $trNameAllowed) {
            return array('state' => 'available', 'label' => 'Kayıt edilebilir');
        }
        return array('state' => 'unknown', 'label' => 'Durum doğrulanamadı');
    }

    private function alternativesHtml($domain, $result)
    {
        $base = $this->baseName($domain);
        if ($base === null) { return ''; }
        $html = '<nav class="w3-whois-alternatives" aria-label="Diğer uzantılarda müsaitlik"><ul>';
        foreach (array('net', 'com.tr', 'tr', 'org') as $extension) {
            $candidate = $base . '.' . $extension;
            $status = $candidate === $domain ? $this->availability($domain, $result) : array('state' => 'checking', 'label' => 'Kontrol ediliyor…');
            $url = qa_path_html('whois-sorgulama', array('domain' => $candidate));
            $checkUrl = qa_path_html('whois-sorgulama', array('domain' => $candidate, 'format' => 'status'));
            $labels = array('available' => 'Boş', 'registered' => 'Dolu', 'unknown' => 'Durum doğrulanamadı', 'checking' => 'Kontrol ediliyor');
            $label = $labels[$status['state']];
            $tooltip = $status['state'] === 'available' ? 'Boş (ön kontrol)' : $label;
            $html .= '<li class="w3-whois-alternative" data-domain="' . qa_html($candidate) . '" data-check-url="' . $checkUrl . '"' . ($status['state'] === 'checking' ? ' data-pending="1"' : '') . '><a href="' . $url . '" title="' . qa_html($tooltip) . '" aria-label="' . qa_html($candidate . ': ' . $tooltip) . '"><span class="w3-whois-status w3-whois-status--' . $status['state'] . '" role="status" aria-live="polite"><span aria-hidden="true">●</span><span class="qa-reader-only">' . $label . '</span></span><span class="w3-whois-alternative-domain">' . qa_html($candidate) . '</span></a></li>';
        }
        return $html . '</ul><noscript><p>Alan adlarına tıklayarak tek tek sorgulayabilirsiniz.</p></noscript></nav>';
    }
}
