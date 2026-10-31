<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

/** Public RDAP/jCard fields only. Roles are kept distinct; nothing hidden is inferred. */
class W3RdapContacts
{
    public static function text($value, $depth = 0)
    {
        if ($depth > 3) { return ''; }
        if (is_array($value)) {
            $parts = array();
            foreach (array_slice($value, 0, 20) as $part) { $text = self::text($part, $depth + 1); if ($text !== '') { $parts[] = $text; } }
            $value = implode(', ', $parts);
        }
        if (!is_string($value) && !is_numeric($value)) { return ''; }
        $value = trim(preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', '', (string) $value));
        return strlen($value) <= 8192 ? $value : '';
    }

    public static function parse($data)
    {
        $out = array('registrar_info' => array(), 'contacts' => array(), 'redacted' => !empty($data['redacted']));
        $count = 0;
        self::entities(isset($data['entities']) ? $data['entities'] : array(), $out, $count, 0);
        return $out;
    }

    private static function entities($entities, &$out, &$count, $depth)
    {
        if (!is_array($entities) || $depth > 5) { return; }
        foreach ($entities as $entity) {
            if (++$count > 64) { return; }
            if (!is_array($entity)) { continue; }
            $roles = isset($entity['roles']) && is_array($entity['roles']) ? $entity['roles'] : array();
            $card = self::card(isset($entity['vcardArray']) ? $entity['vcardArray'] : array());
            if (!empty($entity['redacted'])) { $out['redacted'] = true; }
            if (in_array('registrar', $roles, true)) {
                if (!empty($card['name'])) { $out['registrar_info']['name'] = $card['name']; }
                foreach (isset($entity['publicIds']) && is_array($entity['publicIds']) ? $entity['publicIds'] : array() as $id) {
                    if (is_array($id) && isset($id['type'], $id['identifier']) && is_string($id['type']) && preg_match('/iana.*registrar/i', $id['type'])) { $out['registrar_info']['iana_id'] = self::text($id['identifier']); }
                }
            }
            if (in_array('abuse', $roles, true)) {
                foreach (array('email', 'phone') as $key) {
                    if (!empty($card[$key])) { $old = isset($out['registrar_info']['abuse_' . $key]) ? $out['registrar_info']['abuse_' . $key] : array(); $out['registrar_info']['abuse_' . $key] = array_values(array_unique(array_merge($old, $card[$key]))); }
                }
            }
            foreach (array('registrant', 'administrative', 'technical') as $role) {
                if (in_array($role, $roles, true) && $card && count($out['contacts']) < 12) {
                    $contact = array('role' => $role, 'fields' => $card);
                    $uri = isset($entity['contact-uri']) ? self::text($entity['contact-uri']) : '';
                    if ($uri !== '') { $contact['fields']['contact_url'] = array($uri); }
                    if (!in_array($contact, $out['contacts'], true)) { $out['contacts'][] = $contact; }
                } elseif (in_array($role, $roles, true) && !empty($entity['contact-uri']) && count($out['contacts']) < 12) {
                    $out['contacts'][] = array('role' => $role, 'fields' => array('contact_url' => array(self::text($entity['contact-uri']))));
                }
            }
            self::entities(isset($entity['entities']) ? $entity['entities'] : array(), $out, $count, $depth + 1);
        }
    }

    private static function card($vcard)
    {
        $out = array();
        if (!is_array($vcard) || !isset($vcard[1]) || !is_array($vcard[1])) { return $out; }
        foreach (array_slice($vcard[1], 0, 128) as $property) {
            if (!is_array($property) || !isset($property[0], $property[3]) || !is_string($property[0])) { continue; }
            $key = strtolower($property[0]); $value = self::text($property[3]);
            if ($value === '' && !($key === 'adr' && (!empty($property[1]['label']) || !empty($property[1]['cc'])))) { continue; }
            if ($key === 'fn') { $out['name'] = $value; }
            elseif ($key === 'org') { $out['organization'] = $value; }
            elseif ($key === 'email' || $key === 'tel') {
                $target = $key === 'email' ? 'email' : 'phone';
                $types = isset($property[1]['type']) ? (array) $property[1]['type'] : array();
                if ($key === 'tel' && in_array('fax', $types, true)) { $target = 'fax'; }
                $value = preg_replace($key === 'email' ? '/^mailto:/i' : '/^tel:/i', '', $value);
                if (!isset($out[$target])) { $out[$target] = array(); }
                if (count($out[$target]) < 8 && !in_array($value, $out[$target], true)) { $out[$target][] = $value; }
            } elseif ($key === 'adr') {
                if (is_array($property[3])) {
                    $address = $property[3];
                    foreach (array(2 => 'street', 3 => 'city', 4 => 'region', 5 => 'postal_code', 6 => 'country') as $index => $target) {
                        $part = isset($address[$index]) ? self::text($address[$index]) : '';
                        if ($part !== '') { $out[$target] = $part; }
                    }
                    foreach (array(0, 1) as $index) {
                        $part = isset($address[$index]) ? self::text($address[$index]) : '';
                        if ($part !== '') { $out['street'] = $part . (isset($out['street']) ? ', ' . $out['street'] : ''); }
                    }
                }
                if (empty($out['street']) && !empty($property[1]['label'])) { $out['address'] = self::text($property[1]['label']); }
                elseif (!is_array($property[3])) { $out['address'] = $value; }
                if (empty($out['country']) && !empty($property[1]['cc'])) { $out['country'] = self::text($property[1]['cc']); }
            }
        }
        return $out;
    }

    public static function fromWhois($raw)
    {
        $out = array('registrar_info' => array(), 'contacts' => array(), 'redacted' => false);
        foreach (array('iana_id' => 'Registrar IANA ID', 'abuse_email' => 'Registrar Abuse Contact Email', 'abuse_phone' => 'Registrar Abuse Contact Phone') as $key => $label) {
            if (preg_match_all('/^\s*' . $label . '\s*:\s*(.+)$/mi', $raw, $matches)) { $values = array_map('trim', $matches[1]); $out['registrar_info'][$key] = $key === 'iana_id' ? $values[0] : array_values(array_unique($values)); }
        }
        $map = array('Name' => 'name', 'Organization' => 'organization', 'Street' => 'street', 'City' => 'city', 'State/Province' => 'region', 'Postal Code' => 'postal_code', 'Country' => 'country', 'Phone' => 'phone', 'Email' => 'email', 'Fax' => 'fax');
        foreach (array('Registrant' => 'registrant', 'Admin' => 'administrative', 'Tech' => 'technical') as $prefix => $role) {
            $fields = array();
            foreach ($map as $label => $key) {
                if (preg_match_all('/^\s*' . $prefix . '\s+' . preg_quote($label, '/') . '\s*:\s*(.+)$/mi', $raw, $matches)) {
                    $values = array_values(array_unique(array_filter(array_map(array(__CLASS__, 'text'), $matches[1]))));
                    if ($values) { $fields[$key] = in_array($key, array('phone', 'email', 'fax'), true) ? $values : implode(', ', $values); }
                }
            }
            if ($fields) { $out['contacts'][] = array('role' => $role, 'fields' => $fields); }
        }
        return $out;
    }
}

/** One bounded HTTPS registrar lookup, with a public-IP pin and no redirects/proxy. */
class W3RdapReferral
{
    public static function url($url, $domain)
    {
        if (!is_string($url) || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url) || preg_match('/%(?:0[0-9a-f]|1[0-9a-f]|7f)|%(?![0-9a-f]{2})/i', $url)) { return null; }
        $p = @parse_url($url);
        if (!$p || !isset($p['scheme'], $p['host'], $p['path']) || strtolower($p['scheme']) !== 'https' || isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment']) || (isset($p['port']) && $p['port'] !== 443)) { return null; }
        $host = strtolower($p['host']);
        if (strlen($host) > 253 || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D', $host) || preg_match('/\.(?:localhost|local|test|internal|lan|home|invalid|example|onion|alt)$/D', $host)) { return null; }
        // Only a referral for the exact queried domain, not arbitrary URLs/entities.
        if (!preg_match('~/domain/([^/]+)/?$~iD', $p['path'], $m) || strcasecmp(rawurldecode($m[1]), $domain) !== 0 || preg_match('~(?:%2f|%5c|/\.\.?/)~i', $p['path'])) { return null; }
        return 'https://' . $host . $p['path'];
    }

    public static function publicAddress($ip)
    {
        if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) { return false; }
        $packed = inet_pton($ip);
        $ranges = strlen($packed) === 4 ? array('0.0.0.0/8','100.64.0.0/10','192.0.0.0/24','192.0.2.0/24','192.88.99.0/24','198.18.0.0/15','198.51.100.0/24','203.0.113.0/24','224.0.0.0/4','240.0.0.0/4') : array('2001::/23','2001:db8::/32','2002::/16','3fff::/20');
        if (strlen($packed) === 16 && !self::inRange($packed, '2000::/3')) { return false; }
        foreach ($ranges as $range) { if (self::inRange($packed, $range)) { return false; } }
        return true;
    }

    private static function inRange($packed, $cidr)
    {
        list($network, $bits) = explode('/', $cidr); $network = inet_pton($network);
        if (strlen($packed) !== strlen($network)) { return false; }
        $bytes = (int) floor($bits / 8); $remainder = (int) $bits % 8;
        return substr($packed, 0, $bytes) === substr($network, 0, $bytes) && (!$remainder || ((ord($packed[$bytes]) ^ ord($network[$bytes])) & (255 << (8 - $remainder))) === 0);
    }

    protected function resolve($host)
    {
        $addresses = array();
        $records = function_exists('dns_get_record') ? @dns_get_record($host, DNS_A | DNS_AAAA) : array();
        foreach (is_array($records) ? $records : array() as $record) {
            if (isset($record['ip'])) { $addresses[] = $record['ip']; }
            if (isset($record['ipv6'])) { $addresses[] = $record['ipv6']; }
        }
        if (!$addresses && function_exists('gethostbynamel')) { $fallback = @gethostbynamel($host); $addresses = is_array($fallback) ? $fallback : array(); }
        foreach ($addresses as $address) { if (!self::publicAddress($address)) { return null; } }
        return $addresses ? $addresses[0] : null;
    }

    public function get($url, $domain)
    {
        $empty = array('status' => 0, 'body' => '');
        $url = self::url($url, $domain);
        if (!$url || !function_exists('curl_init') || !defined('CURLOPT_RESOLVE')) { return $empty; }
        $host = parse_url($url, PHP_URL_HOST); $address = $this->resolve($host);
        if (!$address || !self::publicAddress($address)) { return $empty; }
        $body = ''; $headerBytes = 0; $pin = strpos($address, ':') !== false ? '[' . $address . ']' : $address;
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 7, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*', CURLOPT_NETRC => CURL_NETRC_IGNORED,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_RESOLVE => array($host . ':443:' . $pin), CURLOPT_USERAGENT => 'W3Uzman-Whois/1.1',
            CURLOPT_HTTPHEADER => array('Accept: application/rdap+json, application/json'),
            CURLOPT_HEADERFUNCTION => function ($c, $line) use (&$headerBytes) { $headerBytes += strlen($line); return $headerBytes > 32768 ? 0 : strlen($line); },
            CURLOPT_WRITEFUNCTION => function ($c, $chunk) use (&$body) { if (strlen($body) + strlen($chunk) > 524288) { return 0; } $body .= $chunk; return strlen($chunk); },
        ));
        $ok = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
        return $ok === false ? $empty : array('status' => $status, 'body' => $body);
    }
}
