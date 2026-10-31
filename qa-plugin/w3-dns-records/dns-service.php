<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class W3DnsRecordsService
{
    private $cache;
    private $rateDirectory;
    public function __construct($cache, $rateDirectory) { $this->cache = $cache; $this->rateDirectory = $rateDirectory; }
    public static function types() { return array('A'=>1, 'AAAA'=>28, 'CNAME'=>5, 'MX'=>15, 'NS'=>2, 'TXT'=>16, 'SOA'=>6, 'CAA'=>257, 'SRV'=>33); }
    public static function descriptions()
    {
        return array('A'=>'Alan adının IPv4 adresleri.', 'AAAA'=>'Alan adının IPv6 adresleri.', 'CNAME'=>'Başka bir DNS adına yönlenen takma ad kaydı.', 'MX'=>'E-postaları kabul eden sunucular; düşük öncelik değeri önce kullanılır.', 'NS'=>'Alan adının yetkili DNS sunucuları.', 'TXT'=>'SPF, alan adı doğrulama ve diğer metin kayıtları.', 'SOA'=>'DNS bölgesinin ana sunucusu, seri numarası ve yenileme süreleri.', 'CAA'=>'Sertifika düzenlemesine ilişkin yetkilendirme kayıtları.', 'SRV'=>'Belirli bir hizmetin sunucusu ve portu; örnek: _sip._tcp.ornek.com.');
    }
    public function normalize($input)
    {
        if (!is_string($input) || strlen($input) > 1024 || preg_match('/[\x00-\x20\x7f]/', trim($input))) { throw new InvalidArgumentException('Tek bir alan adı yazın. Örnek: w3uzman.com'); }
        $input = trim($input);
        if ($input === '') { throw new InvalidArgumentException('Sorgulamak istediğiniz alan adını yazın.'); }
        $url = parse_url(strpos($input, '://') === false ? 'https://' . $input : $input);
        if (!$url || empty($url['host']) || !isset($url['scheme']) || !in_array(strtolower($url['scheme']), array('http','https'), true) || isset($url['user']) || isset($url['pass']) || isset($url['port'])) { throw new InvalidArgumentException('Geçerli bir alan adı veya web adresi yazın.'); }
        $domain = strtolower(rtrim($url['host'], '.'));
        if (filter_var(trim($domain, '[]'), FILTER_VALIDATE_IP)) { throw new InvalidArgumentException('IP adresi yerine alan adı yazın. IP bilgileri için WhatisMyip aracını kullanabilirsiniz.'); }
        if (preg_match('/[^\x20-\x7e]/', $domain)) {
            if (!function_exists('idn_to_ascii')) { throw new InvalidArgumentException('Türkçe karakterli alan adını xn-- (Punycode) biçiminde yazın.'); }
            $info = array();
            $domain = idn_to_ascii($domain, IDNA_USE_STD3_RULES, INTL_IDNA_VARIANT_UTS46, $info);
            if (!$domain || !empty($info['errors'])) { throw new InvalidArgumentException('Alan adının karakterleri geçerli değil.'); }
        }
        $labels = explode('.', $domain);
        if (strlen($domain) > 253 || count($labels) < 2 || !preg_match('/^(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})$/D', end($labels))) { throw new InvalidArgumentException('Uzantısıyla birlikte geçerli bir alan adı yazın. Örnek: w3uzman.com'); }
        foreach ($labels as $label) {
            if (!preg_match('/^[a-z0-9_](?:[a-z0-9_-]{0,61}[a-z0-9_])?$/D', $label)) { throw new InvalidArgumentException('Alan adında geçersiz veya fazla uzun bir bölüm var.'); }
        }
        // Preserve www, service labels, DKIM selectors and other subdomains exactly.
        return $domain;
    }
    public function lookup($domain, $type, $ip)
    {
        $domain = $this->normalize($domain);
        $types = self::types();
        if (!is_string($type) || !isset($types[$type])) { throw new InvalidArgumentException('Desteklenen bir DNS kayıt türü seçin.'); }
        $key = 'dns:' . hash('sha256', $domain . ':' . $type);
        $cached = $this->cache->get($key);
        if (is_array($cached) && isset($cached['expires'], $cached['checked'], $cached['records']) && $cached['expires'] > time()) {
            $elapsed = max(0, time() - $cached['checked']);
            foreach ($cached['records'] as &$record) { $record['ttl'] = max(0, $record['ttl'] - $elapsed); } unset($record);
            $cached['cached'] = true;
            unset($cached['expires']);
            return $cached;
        }
        if (!$this->allowRequest($ip)) { return $this->failure($domain, $type, 'Sorgu sınırına ulaşıldı veya önbellek kullanılamıyor. Bir dakika sonra tekrar deneyin.', 429); }
        // A fixed DoH resolver is contacted; the supplied hostname is never fetched as a website.
        $url = 'https://dns.google/resolve?' . http_build_query(array('name'=>$domain, 'type'=>$types[$type], 'cd'=>'0', 'do'=>'0', 'edns_client_subnet'=>'0.0.0.0/0'), '', '&', PHP_QUERY_RFC3986);
        $response = $this->http($url);
        if ($response['status'] !== 200) { return $this->failure($domain, $type, 'DNS çözümleyicisine ulaşılamadı. Bu durum kayıt bulunmadığı anlamına gelmez.'); }
        $data = json_decode($response['body'], true);
        if (!is_array($data) || !isset($data['Status'], $data['Question'][0]['name'], $data['Question'][0]['type']) || !is_int($data['Status']) || !is_string($data['Question'][0]['name']) || strcasecmp(rtrim($data['Question'][0]['name'], '.'), $domain) !== 0 || $data['Question'][0]['type'] !== $types[$type] || !empty($data['TC']) || !empty($data['CD'])) {
            return $this->failure($domain, $type, 'DNS yanıtı eksik veya doğrulanamadı. Lütfen tekrar deneyin.');
        }
        if ($data['Status'] !== 0 && $data['Status'] !== 3) {
            $codes = array(1=>'FORMERR', 2=>'SERVFAIL', 4=>'NOTIMP', 5=>'REFUSED');
            $code = isset($codes[$data['Status']]) ? $codes[$data['Status']] : 'DNS ' . $data['Status'];
            return $this->failure($domain, $type, 'DNS sorgusu tamamlanamadı (' . $code . '). Yetkili sunucu veya DNSSEC yapılandırması kontrol gerektirebilir.');
        }
        $records = array(); $seen = array(); $recordNames = array_flip($types);
        if (isset($data['Answer']) && !is_array($data['Answer'])) { return $this->failure($domain, $type, 'DNS yanıt biçimi doğrulanamadı.'); }
        foreach (isset($data['Answer']) ? $data['Answer'] : array() as $answer) {
            if (!is_array($answer) || !isset($answer['name'], $answer['type'], $answer['TTL'], $answer['data']) || !is_string($answer['name']) || !is_int($answer['type']) || !is_int($answer['TTL']) || $answer['TTL'] < 0 || !is_string($answer['data']) || strlen($answer['data']) > 32768 || !preg_match('/^[a-z0-9_.*-]{1,254}$/iD', $answer['name'])) { return $this->failure($domain, $type, 'DNS kayıtlarından biri geçerli biçimde okunamadı.'); }
            // Alias chains are useful; unrelated DNSSEC metadata is not shown as a requested record.
            if ($answer['type'] !== $types[$type] && $answer['type'] !== 5) { continue; }
            $identity = $answer['name'] . ':' . $answer['type'] . ':' . $answer['data'];
            if (isset($seen[$identity])) { continue; }
            $seen[$identity] = true;
            $records[] = array('name'=>$answer['name'], 'type'=>isset($recordNames[$answer['type']]) ? $recordNames[$answer['type']] : 'TYPE' . $answer['type'], 'ttl'=>min(2147483647, $answer['TTL']), 'data'=>$answer['data']);
            if (count($records) > 100) { return $this->failure($domain, $type, 'DNS yanıtı görüntüleme sınırını aştı. Daha özel bir ad sorgulayın.'); }
        }
        $matching = array_filter($records, function ($record) use ($type) { return $record['type'] === $type; });
        $state = $data['Status'] === 3 ? 'not_found' : ($matching ? 'ready' : 'empty');
        $message = $state === 'not_found' ? 'DNS adı veya yönlendiği hedef bulunamadı (NXDOMAIN). Bu, alan adının satın alınabileceğini göstermez.' : ($state === 'empty' ? 'Bu ad için ' . $type . ' kaydı bulunamadı. Bu kayıt türü her alan adında bulunmak zorunda değildir.' : count($matching) . ' adet ' . $type . ' kaydı bulundu.');
        $result = array('domain'=>$domain, 'type'=>$type, 'state'=>$state, 'message'=>$message, 'records'=>$records, 'dnssec'=>isset($data['AD']) && $data['AD'] === true, 'rcode'=>$data['Status'], 'checked'=>time(), 'cached'=>false, 'source'=>'Google Public DNS', 'http_status'=>200);
        $ttl = $matching ? min(300, min(array_column($records, 'ttl'))) : $this->negativeTtl($data);
        if ($ttl > 0) { $entry = $result; $entry['expires'] = time() + $ttl; $this->cache->set($key, $entry, max(1, (int) ceil($ttl / 60))); }
        return $result;
    }
    private function negativeTtl($data)
    {
        foreach (isset($data['Authority']) && is_array($data['Authority']) ? $data['Authority'] : array() as $authority) {
            if (isset($authority['type'], $authority['TTL'], $authority['data']) && $authority['type'] === 6 && is_int($authority['TTL']) && $authority['TTL'] >= 0 && is_string($authority['data'])) {
                $fields = preg_split('/\s+/', trim($authority['data']));
                if (count($fields) === 7 && ctype_digit($fields[6])) { return min(60, $authority['TTL'], (int) $fields[6]); }
            }
        }
        return 0;
    }
    private function failure($domain, $type, $message, $status = 200) { return array('domain'=>$domain, 'type'=>$type, 'state'=>'error', 'message'=>$message, 'records'=>array(), 'dnssec'=>false, 'checked'=>time(), 'cached'=>false, 'source'=>'Google Public DNS', 'http_status'=>$status); }
    protected function allowRequest($ip)
    {
        $file = @fopen($this->rateDirectory . '/w3-dns-rate.php', 'c+');
        if (!$file) { return false; }
        if (!flock($file, LOCK_EX)) { fclose($file); return false; }
        $raw = stream_get_contents($file, 196608); $newline = strpos($raw, "\n");
        $rates = $newline === false ? array() : json_decode(substr($raw, $newline + 1), true);
        $window = (int) floor(time() / 60);
        if (!is_array($rates) || !isset($rates['window']) || $rates['window'] !== $window) { $rates = array('window'=>$window, 'total'=>0, 'ips'=>array()); }
        $key = hash('sha256', (string) $ip); $count = isset($rates['ips'][$key]) ? (int) $rates['ips'][$key] : 0;
        $allowed = $count < 90 && $rates['total'] < 900;
        if ($allowed) {
            $rates['ips'][$key] = $count + 1; $rates['total']++;
            $payload = '<?php http_response_code(404); exit; ?>' . "\n" . json_encode($rates);
            rewind($file); $allowed = ftruncate($file, 0) && fwrite($file, $payload) === strlen($payload) && fflush($file);
        }
        flock($file, LOCK_UN); fclose($file); return $allowed;
    }
    protected function http($url)
    {
        $body = ''; $status = 0;
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, array(CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_TIMEOUT=>8, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_HTTPHEADER=>array('Accept: application/json'), CURLOPT_USERAGENT=>'W3Uzman-DNS/1.0', CURLOPT_WRITEFUNCTION=>function ($curl, $chunk) use (&$body) { if (strlen($body) + strlen($chunk) > 262144) { return 0; } $body .= $chunk; return strlen($chunk); }));
            $ok = curl_exec($curl); if ($ok !== false) { $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE); } curl_close($curl);
        } else {
            $context = stream_context_create(array('http'=>array('timeout'=>8, 'follow_location'=>0, 'ignore_errors'=>true, 'header'=>"Accept: application/json\r\nUser-Agent: W3Uzman-DNS/1.0\r\n"), 'ssl'=>array('verify_peer'=>true, 'verify_peer_name'=>true)));
            $body = @file_get_contents($url, false, $context, 0, 262145);
            if (is_string($body) && strlen($body) <= 262144 && isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) { $status = (int) $match[1]; } else { $body = ''; }
        }
        return array('status'=>$status, 'body'=>$body);
    }
}
