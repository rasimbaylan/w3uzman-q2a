<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
require_once __DIR__ . '/w3-rdap-contacts.php';

class W3WhoisService
{
    private $cache;
    public function __construct($cache) { $this->cache = $cache; }

    public function normalize($input)
    {
        if (!is_string($input) || strlen($input) > 2048 || preg_match('/[\x00-\x20\x7f]/', trim($input))) {
            throw new InvalidArgumentException('Geçerli bir alan adı yazın. Örnek: w3uzman.com');
        }
        $input = trim($input);
        $host = parse_url(strpos($input, '://') === false ? 'https://' . $input : $input, PHP_URL_HOST);
        if (!$host) { throw new InvalidArgumentException('Geçerli bir alan adı yazın.'); }
        $host = strtolower(rtrim($host, '.'));
        if (preg_match('/[^\x20-\x7e]/', $host)) {
            if (!function_exists('idn_to_ascii')) { throw new InvalidArgumentException('Türkçe karakterli alan adını xn-- biçiminde yazın.'); }
            $host = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        }
        // Treat a single valid label as a .com name; keep explicit extensions intact.
        if ($host && strpos($host, '.') === false && preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $host)) {
            $host .= '.com';
        }
        if (!$host || strlen($host) > 253 || filter_var($host, FILTER_VALIDATE_IP) ||
            !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $host)) {
            throw new InvalidArgumentException('Geçerli bir alan adı veya kelime yazın. Örnek: example.com');
        }
        // www is commonly pasted from a website URL; other subdomains are not guessed.
        return preg_replace('/^www\./', '', $host);
    }

    public function lookup($domain, $ip, $includeContacts = true)
    {
        $key = 'domain:contacts-v1:' . ($includeContacts ? 'full:' : 'status:') . $domain;
        $cached = $this->cache->get($key);
        if (is_array($cached)) { return $cached; }
        $rateKey = 'rate:' . hash('sha256', $ip . ':' . floor(time() / 60));
        $count = (int) $this->cache->get($rateKey);
        if ($count >= 12) { return $this->failure('Çok sık sorgu yapıldı. Bir dakika sonra tekrar deneyin.'); }
        $this->cache->set($rateKey, $count + 1, 2);

        $tld = substr($domain, strrpos($domain, '.') + 1);
        if ($tld === 'tr') {
            $result = $this->turkishWhois($domain);
        } elseif ($tld === 'io' || $tld === 'co') {
            $result = $this->registryWhois($domain);
        } else {
            $bootstrap = $this->cache->get('bootstrap');
            if (!is_array($bootstrap)) {
                $response = $this->http('https://data.iana.org/rdap/dns.json');
                $bootstrap = $response['status'] === 200 ? json_decode($response['body'], true) : null;
                if (is_array($bootstrap) && isset($bootstrap['services'])) { $this->cache->set('bootstrap', $bootstrap, 1440); }
            }
            if (empty($bootstrap['services'])) { return $this->failure('Kayıt servisine şu anda ulaşılamıyor. Lütfen daha sonra tekrar deneyin.'); }
            $base = null;
            foreach ($bootstrap['services'] as $service) {
                if (in_array($tld, $service[0], true)) {
                    foreach ($service[1] as $url) {
                        if (strpos($url, 'https://') === 0) { $base = $url; break; }
                    }
                    break;
                }
            }
            if (!$base) { return $this->failure('Bu uzantı için desteklenen bir kayıt servisi bulunamadı.'); }
            $registryUrl = rtrim($base, '/') . '/domain/' . rawurlencode($domain);
            $response = $this->http($registryUrl);
            if ($response['status'] === 404) {
                $result = array('state' => 'not-found', 'message' => 'Kayıt servisinde bu alan adı için kayıt bulunamadı. Bu sonuç, alan adının satın alınabileceğini garanti etmez.', 'source' => 'RDAP', 'checked' => time());
            } elseif ($response['status'] === 200 && is_array($data = json_decode($response['body'], true)) && isset($data['objectClassName']) && $data['objectClassName'] === 'domain' && (!isset($data['ldhName']) || (is_string($data['ldhName']) && strcasecmp(rtrim($data['ldhName'], '.'), $domain) === 0))) {
                $result = $this->parseRdap($data);
                if ($includeContacts) { $result = $this->addRegistrarContacts($result, $domain, $data, $registryUrl); }
            } else {
                return $this->failure('Sorgu tamamlanamadı. Kayıt servisi geçici olarak yanıt vermiyor veya sorgu sınırına ulaşıldı.');
            }
        }
        if ($includeContacts && $result['state'] === 'registered' && $result['source'] !== 'RDAP') {
            $result = array_merge($result, W3RdapContacts::fromWhois($result['raw']));
        }
        if ($result['state'] !== 'error') { $this->cache->set($key, $result, 10); }
        return $result;
    }

    protected function http($url)
    {
        $status = 0; $body = '';
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 9,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => array('Accept: application/rdap+json, application/json'),
                CURLOPT_USERAGENT => 'W3Uzman-Whois/1.0',
                CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$body) {
                    if (strlen($body) + strlen($chunk) > 524288) { return 0; }
                    $body .= $chunk; return strlen($chunk);
                },
            ));
            $ok = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            // Some registries close an empty 404 response without a clean TLS EOF.
            // The received 404 is sufficient; incomplete successful data is not.
            if ($ok === false && $status !== 404) { $status = 0; }
            curl_close($curl);
        } else {
            $context = stream_context_create(array(
                'http' => array('timeout' => 9, 'follow_location' => 0, 'ignore_errors' => true, 'header' => "Accept: application/rdap+json, application/json\r\nUser-Agent: W3Uzman-Whois/1.0\r\n"),
                'ssl' => array('verify_peer' => true, 'verify_peer_name' => true),
            ));
            $body = @file_get_contents($url, false, $context, 0, 524289);
            if (is_string($body) && strlen($body) <= 524288 && isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) { $status = (int) $m[1]; }
            else { $body = ''; }
        }
        return array('status' => $status, 'body' => $body);
    }

    private function parseRdap($data)
    {
        $result = array('state' => 'registered', 'registrar' => '', 'created' => '', 'expires' => '', 'updated' => '', 'nameservers' => array(), 'statuses' => isset($data['status']) && is_array($data['status']) ? array_values(array_filter($data['status'], 'is_string')) : array(), 'source' => 'RDAP', 'checked' => time());
        foreach (isset($data['events']) && is_array($data['events']) ? $data['events'] : array() as $event) {
            $map = array('registration' => 'created', 'expiration' => 'expires', 'last changed' => 'updated');
            if (is_array($event) && isset($event['eventAction'], $event['eventDate']) && is_string($event['eventAction']) && is_string($event['eventDate']) && isset($map[$event['eventAction']])) { $result[$map[$event['eventAction']]] = $event['eventDate']; }
        }
        foreach (isset($data['nameservers']) && is_array($data['nameservers']) ? $data['nameservers'] : array() as $ns) {
            if (is_array($ns) && !empty($ns['ldhName']) && is_string($ns['ldhName'])) { $result['nameservers'][] = strtolower($ns['ldhName']); }
        }
        $result = array_merge($result, W3RdapContacts::parse($data));
        if ($result['registrar'] === '' && !empty($result['registrar_info']['name'])) { $result['registrar'] = $result['registrar_info']['name']; }
        $result['raw'] = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $result;
    }

    protected function registrarHttp($url, $domain)
    {
        $client = new W3RdapReferral();
        return $client->get($url, $domain);
    }

    private function addRegistrarContacts($result, $domain, $data, $registryUrl)
    {
        $result['registry_url'] = $registryUrl;
        $result['contact_lookup'] = 'registry';
        foreach (isset($data['links']) && is_array($data['links']) ? array_slice($data['links'], 0, 20) : array() as $link) {
            if (!is_array($link) || !isset($link['rel'], $link['href'], $link['type']) || $link['rel'] !== 'related' || $link['type'] !== 'application/rdap+json') { continue; }
            $url = W3RdapReferral::url($link['href'], $domain);
            if (!$url || $url === $registryUrl) { continue; }
            $result['contact_lookup'] = 'unavailable';
            $response = $this->registrarHttp($url, $domain);
            $record = $response['status'] === 200 ? json_decode($response['body'], true, 64) : null;
            // Never combine another domain's data with this result.
            if (!is_array($record) || !isset($record['objectClassName'], $record['ldhName']) || $record['objectClassName'] !== 'domain' || !is_string($record['ldhName']) || strcasecmp(rtrim($record['ldhName'], '.'), $domain) !== 0) { break; }
            $extra = W3RdapContacts::parse($record);
            $oldId = isset($result['registrar_info']['iana_id']) ? $result['registrar_info']['iana_id'] : '';
            $newId = isset($extra['registrar_info']['iana_id']) ? $extra['registrar_info']['iana_id'] : '';
            if ($oldId !== '' && $newId !== '' && $oldId !== $newId) { break; }
            $result['registrar_info'] = array_merge($result['registrar_info'], $extra['registrar_info']);
            $roles = array_column($extra['contacts'], 'role');
            $keep = array();
            foreach ($result['contacts'] as $contact) { if (!$extra['redacted'] && !in_array($contact['role'], $roles, true)) { $keep[] = $contact; } }
            $result['contacts'] = array_merge($keep, $extra['contacts']);
            $result['redacted'] = $result['redacted'] || $extra['redacted'];
            $result['contact_lookup'] = 'registrar';
            $result['registrar_url'] = $url;
            $result['raw'] = "=== Ana kayıt servisi (RDAP) ===\n" . $result['raw'] . "\n\n=== Kayıt kuruluşu (RDAP) ===\n" . json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break; // At most one extra network request per full lookup.
        }
        return $result;
    }

    protected function whoisText($domain)
    {
        // Fixed registry endpoints: no user-provided host or referral is contacted.
        $tld = substr($domain, strrpos($domain, '.') + 1);
        $servers = array('io' => 'whois.nic.io', 'co' => 'whois.registry.co', 'tr' => 'whois.trabis.gov.tr');
        if (!isset($servers[$tld])) { return ''; }
        $socket = @stream_socket_client('tcp://' . $servers[$tld] . ':43', $errno, $error, 4);
        if (!$socket) { return ''; }
        stream_set_timeout($socket, 7);
        fwrite($socket, $domain . "\r\n");
        $raw = '';
        while (!feof($socket) && strlen($raw) <= 524288) {
            $chunk = fgets($socket, 4096);
            if ($chunk === false) { break; }
            $raw .= $chunk;
        }
        $meta = stream_get_meta_data($socket); fclose($socket);
        return $meta['timed_out'] || strlen($raw) > 524288 ? '' : $raw;
    }

    private function registryWhois($domain)
    {
        $raw = $this->whoisText($domain);
        if (!$raw) { return $this->failure('Yetkili Whois servisine şu anda ulaşılamıyor. Daha sonra tekrar deneyin.'); }
        if (preg_match('/^\s*(?:Domain not found\.?|The queried object does not exist: DOMAIN NOT FOUND)\s*$/mi', $raw)) {
            return array('state' => 'not-found', 'message' => 'Yetkili kayıt servisinde bu alan adı için kayıt bulunamadı. Bu sonuç satın alınabilirlik garantisi değildir.', 'source' => 'Registry WHOIS', 'checked' => time());
        }
        if (!preg_match('/^\s*Domain Name\s*:\s*' . preg_quote($domain, '/') . '\.?\s*$/mi', $raw)) { return $this->failure('Whois yanıtı doğrulanamadı. Daha sonra tekrar deneyin.'); }
        $result = array('state' => 'registered', 'registrar' => '', 'created' => '', 'expires' => '', 'updated' => '', 'statuses' => array(), 'nameservers' => array(), 'source' => 'Registry WHOIS', 'checked' => time(), 'raw' => $raw);
        foreach (array('created' => 'Creation Date', 'expires' => 'Registry Expiry Date', 'updated' => 'Updated Date', 'registrar' => 'Registrar') as $key => $label) {
            if (preg_match('/^\s*' . $label . '\s*:\s*(.+)$/mi', $raw, $match)) { $result[$key] = trim($match[1]); }
        }
        if (preg_match_all('/^\s*Name Server\s*:\s*([a-z0-9.-]+)\s*$/mi', $raw, $matches)) { $result['nameservers'] = array_map('strtolower', $matches[1]); }
        if (preg_match_all('/^\s*Domain Status\s*:\s*(.+)$/mi', $raw, $matches)) { $result['statuses'] = array_map('trim', $matches[1]); }
        return $result;
    }

    private function turkishWhois($domain)
    {
        $raw = $this->whoisText($domain);
        if (!$raw) { return $this->failure('TRABİS kayıt servisine şu anda ulaşılamıyor. Daha sonra tekrar deneyin.'); }
        if (preg_match('/No match found|No entries found/i', $raw)) {
            return array('state' => 'not-found', 'message' => 'TRABİS üzerinde kayıt bulunamadı. Bu sonuç, alan adının satın alınabileceğini garanti etmez.', 'source' => 'TRABİS WHOIS', 'checked' => time());
        }
        if (!preg_match('/\bDomain Name\s*:\s*' . preg_quote($domain, '/') . '\b/i', $raw)) { return $this->failure('TRABİS yanıtı doğrulanamadı. Daha sonra tekrar deneyin.'); }
        $r = array('state' => 'registered', 'registrar' => '', 'created' => '', 'expires' => '', 'updated' => '', 'statuses' => array(), 'nameservers' => array(), 'source' => 'TRABİS WHOIS', 'checked' => time(), 'raw' => $raw);
        foreach (array('created' => 'Created on', 'expires' => 'Expires on', 'updated' => 'Last Update(?: Time)?') as $key => $label) {
            if (preg_match('/^\s*' . $label . '[.\s]*:\s*(.+)$/mi', $raw, $m)) { $r[$key] = rtrim(trim($m[1]), '.'); }
        }
        if (preg_match('/\*\* Registrar:\s*(.*?)(?=\n\s*\*\*|\z)/si', $raw, $m) && preg_match('/Organization Name\s*:\s*(.+)/i', $m[1], $name)) { $r['registrar'] = trim($name[1]); }
        if (preg_match('/\*\* Domain Servers:\s*(.*?)(?=\n\s*\*\*|\z)/si', $raw, $m)) {
            foreach (preg_split('/\r?\n/', trim($m[1])) as $line) {
                if (preg_match('/^([a-z0-9][a-z0-9.-]+\.[a-z]{2,})\.?\s*$/i', trim($line), $ns)) { $r['nameservers'][] = strtolower($ns[1]); }
            }
        }
        return $r;
    }

    private function failure($message) { return array('state' => 'error', 'message' => $message); }
}
