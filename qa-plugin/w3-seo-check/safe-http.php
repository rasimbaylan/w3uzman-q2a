<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

/** Bounded public HTTP(S) fetches. No cookies, login, proxy, JS or implicit redirects. */
class W3SeoHttpClient
{
    private $deadline;
    public function __construct() { $this->deadline = microtime(true) + 25; }

    public function normalize($input)
    {
        if (!is_string($input) || strlen($input) > 2048) { throw new InvalidArgumentException('En fazla 2.048 karakterlik bir web adresi yazın.'); }
        $input = trim($input);
        if ($input === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $input) || preg_match('/%(?:0[0-9a-f]|1[0-9a-f]|7f)|%(?![0-9a-f]{2})/i', $input)) {
            throw new InvalidArgumentException('Boşluk, kontrol karakteri veya ters eğik çizgi içermeyen geçerli bir adres yazın.');
        }
        if (strpos($input, '://') === false) { $input = 'https://' . $input; }
        $p = @parse_url($input);
        if (!$p || empty($p['host']) || !isset($p['scheme']) || !in_array(strtolower($p['scheme']), array('http','https'), true) || isset($p['user']) || isset($p['pass'])) {
            throw new InvalidArgumentException('Yalnızca giriş bilgisi içermeyen HTTP veya HTTPS adresleri kontrol edilebilir.');
        }
        $scheme = strtolower($p['scheme']); $host = strtolower(rtrim($p['host'], '.'));
        if (isset($p['port']) && $p['port'] !== ($scheme === 'https' ? 443 : 80)) { throw new InvalidArgumentException('Yalnızca standart 80 ve 443 portları kullanılabilir.'); }
        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) { throw new InvalidArgumentException('IP adresi yerine herkese açık bir alan adı yazın.'); }
        if (preg_match('/[^\x20-\x7e]/', $host)) {
            if (!function_exists('idn_to_ascii')) { throw new InvalidArgumentException('Uluslararası alan adını xn-- (Punycode) biçiminde yazın.'); }
            $info = array(); $host = idn_to_ascii($host, IDNA_USE_STD3_RULES, INTL_IDNA_VARIANT_UTS46, $info);
            if (!$host || !empty($info['errors'])) { throw new InvalidArgumentException('Alan adının karakterleri geçerli değil.'); }
        }
        if (strlen($host) > 253 || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})$/D', $host) || preg_match('/\.(?:localhost|local|test|internal|lan|home|onion|invalid|example|alt)$/i', $host)) {
            throw new InvalidArgumentException('localhost, yerel ağ veya özel alan adları yerine herkese açık bir alan adı yazın.');
        }
        return $scheme . '://' . $host . $this->cleanPath(isset($p['path']) && $p['path'] !== '' ? $p['path'] : '/') . (isset($p['query']) ? '?' . $p['query'] : '');
    }

    public function publicAddress($ip)
    {
        if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) { return false; }
        $packed = inet_pton($ip);
        $ranges = strlen($packed) === 4 ? array('0.0.0.0/8','100.64.0.0/10','192.0.0.0/24','192.0.2.0/24','192.88.99.0/24','198.18.0.0/15','198.51.100.0/24','203.0.113.0/24','224.0.0.0/4','240.0.0.0/4') : array('2001::/23','2001:db8::/32','2002::/16','3fff::/20');
        if (strlen($packed) === 16 && !$this->inRange($packed, '2000::/3')) { return false; }
        foreach ($ranges as $range) { if ($this->inRange($packed, $range)) { return false; } }
        return true;
    }

    private function inRange($packed, $cidr)
    {
        list($network, $bits) = explode('/', $cidr); $network = inet_pton($network);
        if (strlen($packed) !== strlen($network)) { return false; }
        $bytes = (int) floor($bits / 8); $remainder = (int) $bits % 8;
        return substr($packed, 0, $bytes) === substr($network, 0, $bytes) && (!$remainder || ((ord($packed[$bytes]) ^ ord($network[$bytes])) & (255 << (8 - $remainder))) === 0);
    }

    /** Resolve relative URLs, without requesting them. Keep queries and significant slashes. */
    public function absolute($reference, $base)
    {
        $reference = trim((string) $reference);
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $reference)) { return null; }
        $b = @parse_url($base); if (!$b || empty($b['host'])) { return null; }
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $reference)) { $url = $reference; }
        elseif (substr($reference, 0, 2) === '//') { $url = $b['scheme'] . ':' . $reference; }
        else {
            $origin = $b['scheme'] . '://' . $b['host'] . (isset($b['port']) ? ':' . $b['port'] : '');
            $r = @parse_url($reference); if ($r === false) { return null; }
            $oldpath = isset($b['path']) ? $b['path'] : '/'; $path = isset($r['path']) ? $r['path'] : '';
            if ($path === '') { $path = $oldpath; $query = isset($r['query']) ? '?' . $r['query'] : (isset($b['query']) ? '?' . $b['query'] : ''); }
            else { if ($path[0] !== '/') { $path = substr($oldpath, 0, strrpos($oldpath, '/') + 1) . $path; } $query = isset($r['query']) ? '?' . $r['query'] : ''; }
            $url = $origin . $this->cleanPath($path) . $query . (isset($r['fragment']) ? '#' . $r['fragment'] : '');
        }
        $p = @parse_url($url);
        return $p && isset($p['scheme'], $p['host']) && in_array(strtolower($p['scheme']), array('http','https'), true) && !isset($p['user']) && !isset($p['pass']) ? $url : null;
    }

    private function cleanPath($path)
    {
        $segments = explode('/', $path); $clean = array();
        foreach ($segments as $i => $segment) {
            if ($segment === '.') { if ($i === count($segments)-1) { $clean[] = ''; } continue; }
            if ($segment === '..') { if (count($clean) > 1) { array_pop($clean); } if ($i === count($segments)-1) { $clean[] = ''; } continue; }
            $clean[] = $segment;
        }
        return implode('/', $clean);
    }

    public function get($url, $limit = 2097152, $redirects = 3)
    {
        $url = $this->normalize($url); $chain = array(); $seen = array();
        for ($hop = 0; $hop <= $redirects; $hop++) {
            if (isset($seen[$url])) { throw new RuntimeException('Yönlendirme döngüsü tespit edildi.'); }
            $seen[$url] = true;
            $host = parse_url($url, PHP_URL_HOST); $address = $this->resolve($host);
            if (!$address || !$this->publicAddress($address)) { throw new RuntimeException('Alan adı güvenli bir genel IP adresine çözümlenemedi. Yerel veya özel ağ hedefleri engellenir.'); }
            $response = $this->exchange($url, $address, $limit, 8000);
            $chain[] = array('url' => $url, 'status' => $response['status']);
            if (in_array($response['status'], array(301,302,303,307,308), true)) {
                if ($hop === $redirects) { throw new RuntimeException('Yönlendirme sınırı aşıldı. En fazla ' . $redirects . ' yönlendirme izlenir.'); }
                $locations = isset($response['headers']['location']) ? $response['headers']['location'] : array();
                if (count($locations) !== 1 || !($next = $this->absolute($locations[0], $url))) { throw new RuntimeException('Yönlendirme adresi geçerli veya tekil değil.'); }
                // Every redirect repeats URL/DNS validation; cURL never follows on its own.
                $url = $this->normalize($next); continue;
            }
            $response['url'] = $url; $response['chain'] = $chain;
            return $response;
        }
        throw new RuntimeException('Sayfa alınamadı.');
    }

    protected function resolve($host)
    {
        $addresses = array();
        foreach (array(1,28) as $type) {
            $r = $this->exchange('https://dns.google/resolve?' . http_build_query(array('name'=>$host,'type'=>$type,'cd'=>'0','edns_client_subnet'=>'0.0.0.0/0'), '', '&', PHP_QUERY_RFC3986), '8.8.8.8', 65536, 3500);
            $d = $r['status'] === 200 ? json_decode($r['body'], true) : null;
            if (!is_array($d) || !isset($d['Status'], $d['Question'][0]['name'], $d['Question'][0]['type']) || $d['Status'] !== 0 || !is_string($d['Question'][0]['name']) || strcasecmp(rtrim($d['Question'][0]['name'], '.'), $host) !== 0 || $d['Question'][0]['type'] !== $type || !empty($d['TC']) || !empty($d['CD'])) { throw new RuntimeException('DNS yanıtı doğrulanamadı. Alan adını kontrol edin veya daha sonra deneyin.'); }
            foreach (isset($d['Answer']) && is_array($d['Answer']) ? $d['Answer'] : array() as $answer) {
                if (isset($answer['type'], $answer['data']) && in_array($answer['type'], array(1,28), true)) {
                    if (!$this->publicAddress($answer['data'])) { throw new RuntimeException('Alan adının DNS kayıtlarında özel veya ayrılmış IP adresi bulundu; bağlantı engellendi.'); }
                    $addresses[] = inet_ntop(inet_pton($answer['data']));
                }
            }
        }
        return $addresses ? $addresses[0] : null;
    }

    protected function exchange($url, $address, $limit, $timeout)
    {
        if (!function_exists('curl_init') || !defined('CURLOPT_RESOLVE')) { throw new RuntimeException('Bu araç için hostingde PHP cURL uzantısı etkin olmalıdır.'); }
        if (!$this->publicAddress($address)) { throw new RuntimeException('Güvenli olmayan ağ hedefi engellendi.'); }
        $remaining = (int) (($this->deadline - microtime(true)) * 1000);
        if ($remaining < 100) { throw new RuntimeException('Kontrolün toplam süre sınırı aşıldı. Daha sonra tekrar deneyin.'); }
        $body = ''; $headers = array(); $headerBytes = 0; $oversize = false;
        $p = parse_url($url); $port = $p['scheme'] === 'https' ? 443 : 80;
        $pin = strpos($address, ':') !== false ? '[' . $address . ']' : $address;
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_CONNECTTIMEOUT_MS => min(3000, $remaining), CURLOPT_TIMEOUT_MS => min($timeout, $remaining),
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*', CURLOPT_NETRC => CURL_NETRC_IGNORED,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_RESOLVE => array($p['host'] . ':' . $port . ':' . $pin),
            CURLOPT_ENCODING => 'gzip,deflate', CURLOPT_USERAGENT => 'W3Uzman-SEO/1.0 (+https://w3uzman.com/sayfa-seo-kontrolu)',
            CURLOPT_HTTPHEADER => array('Accept: text/html,application/xhtml+xml,text/plain,application/json;q=0.8'),
            CURLOPT_HEADERFUNCTION => function ($c, $line) use (&$headers, &$headerBytes, &$oversize) {
                $n = strlen($line); $headerBytes += $n;
                if ($headerBytes > 32768) { $oversize = true; return 0; }
                if (preg_match('~^HTTP/\S+\s+\d{3}~i', $line)) { $headers = array(); }
                elseif (strpos($line, ':') !== false) { list($key, $value) = explode(':', $line, 2); $key = strtolower(trim($key)); $headers[$key][] = trim($value); }
                return $n;
            },
            CURLOPT_WRITEFUNCTION => function ($c, $chunk) use (&$body, &$oversize, $limit) {
                if (strlen($body) + strlen($chunk) > $limit) { $oversize = true; return 0; }
                $body .= $chunk; return strlen($chunk);
            },
        ));
        $ok = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE); $error = curl_errno($curl); curl_close($curl);
        if ($oversize) { throw new RuntimeException('Yanıt boyut sınırını aşıyor; eksik içerikten SEO sonucu üretilmedi.'); }
        if ($ok === false || !$status) { throw new RuntimeException('Sayfaya güvenli bağlantı kurulamadı veya zaman aşımı oluştu (bağlantı kodu ' . $error . '). TLS doğrulaması kapatılmaz.'); }
        return array('status'=>$status, 'headers'=>$headers, 'body'=>$body);
    }
}
