<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class W3DomainSearchService
{
    private $cache;
    private $rateDirectory;

    public function __construct($cache, $rateDirectory)
    {
        $this->cache = $cache;
        $this->rateDirectory = $rateDirectory;
    }

    public static function extensions()
    {
        return array('com', 'net', 'org', 'com.tr', 'tr', 'info', 'biz', 'ai', 'io', 'co');
    }

    public function normalize($input)
    {
        if (!is_string($input) || strlen($input) > 512 || preg_match('/[\x00-\x20\x7f]/', trim($input))) {
            throw new InvalidArgumentException('Tek bir alan adı veya kelime yazın. Örnek: w3uzman');
        }
        $input = trim($input);
        if ($input === '') { throw new InvalidArgumentException('Sorgulamak istediğiniz adı yazın.'); }
        $url = parse_url(strpos($input, '://') === false ? 'https://' . $input : $input);
        if (!$url || empty($url['host']) || !in_array(strtolower($url['scheme']), array('http', 'https'), true) || isset($url['user']) || isset($url['pass']) || isset($url['port'])) {
            throw new InvalidArgumentException('Geçerli bir alan adı veya kelime yazın.');
        }
        $host = strtolower(rtrim($url['host'], '.'));
        if (filter_var($host, FILTER_VALIDATE_IP)) { throw new InvalidArgumentException('IP adresi yerine bir alan adı yazın.'); }
        if (preg_match('/[^\x20-\x7e]/', $host)) {
            if (!function_exists('idn_to_ascii')) { throw new InvalidArgumentException('Türkçe karakterli adı xn-- biçiminde yazın.'); }
            $info = array();
            $host = idn_to_ascii($host, IDNA_USE_STD3_RULES, INTL_IDNA_VARIANT_UTS46, $info);
            if (!$host || !empty($info['errors'])) { throw new InvalidArgumentException('Alan adı karakterleri geçerli değil.'); }
        }
        // Only the supported suffixes are stripped; arbitrary subdomains are not guessed.
        $host = preg_replace('/^www\./', '', $host);
        foreach (array('com.tr', 'com', 'net', 'org', 'tr', 'info', 'biz', 'ai', 'io', 'co') as $suffix) {
            if (substr($host, -strlen($suffix) - 1) === '.' . $suffix) {
                $host = substr($host, 0, -strlen($suffix) - 1);
                break;
            }
        }
        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $host)) {
            throw new InvalidArgumentException('Uzantısız tek bir ad yazın veya desteklenen bir alan adı girin. Boşluk ve alt alan adı kullanmayın.');
        }
        if (substr($host, 2, 2) === '--' && substr($host, 0, 4) !== 'xn--') {
            throw new InvalidArgumentException('Alan adının üçüncü ve dördüncü karakterleri birlikte tire olamaz.');
        }
        if (substr($host, 0, 4) === 'xn--' && function_exists('idn_to_ascii')) {
            $info = array();
            $ascii = idn_to_ascii($host, IDNA_USE_STD3_RULES, INTL_IDNA_VARIANT_UTS46, $info);
            if (!$ascii || !empty($info['errors'])) { throw new InvalidArgumentException('Punycode alan adı geçerli değil.'); }
        }
        return $host;
    }

    public function lookup($name, $extension, $ip)
    {
        $name = $this->normalize($name);
        if (!is_string($extension) || !in_array($extension, self::extensions(), true)) {
            throw new InvalidArgumentException('Bu uzantı desteklenmiyor.');
        }
        $domain = $name . '.' . $extension;
        $key = 'domain:' . hash('sha256', $domain);
        $cached = $this->cache->get($key);
        if (is_array($cached) && isset($cached['domain'], $cached['state']) && $cached['domain'] === $domain) {
            $cached['cached'] = true;
            return $cached;
        }
        if (!$this->allowRequest($ip)) {
            return $this->result($domain, 'unknown', 'Sorgu sınırına ulaşıldı veya önbellek kullanılamıyor. Bir dakika sonra tekrar deneyin.', '', 429);
        }
        if (in_array($extension, array('tr', 'com.tr', 'io', 'co'), true)) {
            $servers = array('tr' => 'whois.trabis.gov.tr', 'com.tr' => 'whois.trabis.gov.tr', 'io' => 'whois.nic.io', 'co' => 'whois.registry.co');
            $raw = $this->whois($servers[$extension], $domain);
            $source = $extension === 'tr' || $extension === 'com.tr' ? 'TRABİS Whois' : 'Registry Whois';
            if (preg_match('/^\s*(?:\*\*\s*)?Domain Name\s*:\s*' . preg_quote($domain, '/') . '\.?\s*$/mi', $raw)) {
                $result = $this->result($domain, 'registered', 'Bu alan adı kayıtlı.', $source);
            } elseif (preg_match('/^\s*(?:No match found(?:\s+for\b[^\r\n]*)?|No entries found[^\r\n]*|Domain not found\.?|The queried object does not exist: DOMAIN NOT FOUND|No Data Found|No match for\s+[^\r\n]+)\s*$/mi', $raw)) {
                $result = $this->notFound($domain, $name, $source);
            } else {
                $result = $this->result($domain, 'unknown', 'Kayıt servisine ulaşılamadı veya yanıt doğrulanamadı. Daha sonra tekrar deneyin.', $source);
            }
        } else {
            // Fixed HTTPS endpoints verified against IANA's RDAP bootstrap registry (2026-10-18).
            $bases = array(
                'com' => 'https://rdap.verisign.com/com/v1/',
                'net' => 'https://rdap.verisign.com/net/v1/',
                'org' => 'https://rdap.publicinterestregistry.org/rdap/',
                'info' => 'https://rdap.identitydigital.services/rdap/',
                'biz' => 'https://rdap.nic.biz/',
                'ai' => 'https://rdap.identitydigital.services/rdap/',
            );
            $response = $this->http($bases[$extension] . 'domain/' . rawurlencode($domain));
            $data = json_decode($response['body'], true);
            if ($response['status'] === 200 && is_array($data) && isset($data['objectClassName'], $data['ldhName']) && $data['objectClassName'] === 'domain' && is_string($data['ldhName']) && strcasecmp(rtrim($data['ldhName'], '.'), $domain) === 0) {
                $result = $this->result($domain, 'registered', 'Bu alan adı kayıtlı.', 'RDAP');
            } elseif ($response['status'] === 404 && ((is_array($data) && isset($data['errorCode']) && $data['errorCode'] === 404) || ($response['body'] === '' && isset($response['type']) && stripos($response['type'], 'application/rdap+json') === 0))) {
                $result = $this->notFound($domain, $name, 'RDAP');
            } else {
                $result = $this->result($domain, 'unknown', 'Kayıt servisi geçerli bir yanıt vermedi. Bu sonuç alan adının boş olduğu anlamına gelmez.', 'RDAP');
            }
        }
        if ($result['state'] !== 'unknown') { $this->cache->set($key, $result, $result['state'] === 'registered' ? 5 : 1); }
        return $result;
    }

    private function notFound($domain, $name, $source)
    {
        // Conservative handling of short/reserved/IDN names; absence alone is not availability.
        if (strlen($name) < 3 || in_array($name, array('example', 'test', 'invalid', 'localhost', 'nic', 'whois', 'rdap', 'www'), true) || substr($name, 0, 4) === 'xn--') {
            return $this->result($domain, 'unknown', 'Kayıt bulunamadı; bu ad için tahsis veya rezervasyon koşulları ayrıca doğrulanmalı.', $source);
        }
        return $this->result($domain, 'available', 'Kayıt bulunamadı. Kesin müsaitliği kayıt kuruluşunda doğrulayın.', $source);
    }

    private function result($domain, $state, $message, $source, $status = 200)
    {
        return array('domain' => $domain, 'state' => $state, 'label' => $state === 'registered' ? 'Dolu' : ($state === 'available' ? 'Boş' : 'Doğrulanamadı'), 'message' => $message, 'source' => $source, 'checked' => time(), 'cached' => false, 'http_status' => $status);
    }

    protected function allowRequest($ip)
    {
        // A shared lock makes the rate limit effective across simultaneous AJAX requests.
        $file = @fopen($this->rateDirectory . '/w3-domain-rate.php', 'c+');
        if (!$file) { return false; }
        if (!flock($file, LOCK_EX)) { fclose($file); return false; }
        $raw = stream_get_contents($file, 131072);
        $newline = strpos($raw, "\n");
        $rates = $newline === false ? array() : json_decode(substr($raw, $newline + 1), true);
        $window = (int) floor(time() / 60);
        if (!is_array($rates) || !isset($rates['window']) || $rates['window'] !== $window) { $rates = array('window' => $window, 'total' => 0, 'ips' => array()); }
        $hash = hash('sha256', (string) $ip);
        $count = isset($rates['ips'][$hash]) ? (int) $rates['ips'][$hash] : 0;
        $allowed = $count < 60 && $rates['total'] < 600;
        if ($allowed) {
            $rates['ips'][$hash] = $count + 1;
            $rates['total']++;
            $payload = '<?php http_response_code(404); exit; ?>' . "\n" . json_encode($rates);
            rewind($file);
            $allowed = ftruncate($file, 0) && fwrite($file, $payload) === strlen($payload) && fflush($file);
        }
        flock($file, LOCK_UN); fclose($file);
        return $allowed;
    }

    protected function http($url)
    {
        $body = ''; $status = 0; $type = '';
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => array('Accept: application/rdap+json, application/json'), CURLOPT_USERAGENT => 'W3Uzman-Domain/1.0',
                CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$body) { if (strlen($body) + strlen($chunk) > 262144) { return 0; } $body .= $chunk; return strlen($chunk); },
            ));
            $ok = curl_exec($curl);
            $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $type = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            // Verisign returns an empty RDAP 404, sometimes with an unclean TLS EOF.
            if ($ok !== false || ($code === 404 && $body === '' && curl_errno($curl) === CURLE_RECV_ERROR)) { $status = $code; }
            curl_close($curl);
        } else {
            $context = stream_context_create(array('http' => array('timeout' => 10, 'follow_location' => 0, 'ignore_errors' => true, 'header' => "Accept: application/rdap+json, application/json\r\nUser-Agent: W3Uzman-Domain/1.0\r\n"), 'ssl' => array('verify_peer' => true, 'verify_peer_name' => true)));
            $body = @file_get_contents($url, false, $context, 0, 262145);
            if (is_string($body) && strlen($body) <= 262144 && isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                $status = (int) $m[1];
                foreach ($http_response_header as $line) { if (stripos($line, 'Content-Type:') === 0) { $type = trim(substr($line, 13)); } }
            }
            else { $body = ''; }
        }
        return array('status' => $status, 'body' => $body, 'type' => $type);
    }

    protected function whois($server, $domain)
    {
        $socket = @stream_socket_client('tcp://' . $server . ':43', $errno, $error, 4);
        if (!$socket) { return ''; }
        stream_set_timeout($socket, 6);
        $query = $domain . "\r\n";
        if (fwrite($socket, $query) !== strlen($query)) { fclose($socket); return ''; }
        $raw = ''; $deadline = microtime(true) + 7;
        while (!feof($socket) && strlen($raw) <= 262144 && microtime(true) < $deadline) {
            $chunk = fgets($socket, 4096);
            if ($chunk === false) { break; }
            $raw .= $chunk;
        }
        $meta = stream_get_meta_data($socket); $complete = feof($socket); fclose($socket);
        return !$complete || $meta['timed_out'] || strlen($raw) > 262144 ? '' : $raw;
    }
}
