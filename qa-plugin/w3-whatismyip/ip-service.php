<?php
/** Local-only IP classification and DB-IP Lite lookup. No outbound requests. */
if (!defined('QA_VERSION')) {
    http_response_code(404);
    exit;
}

class w3_ip_lookup_service
{
    private $dataDirectory;

    public function __construct($dataDirectory = null)
    {
        $this->dataDirectory = $dataDirectory === null ? __DIR__ . '/data' : $dataDirectory;
    }

    public function address($input)
    {
        $empty = array('address' => '', 'version' => '', 'local' => false);
        if (!is_string($input) || strlen($input) > 64) {
            return $empty;
        }
        $input = trim($input);
        if (filter_var($input, FILTER_VALIDATE_IP) === false) {
            return $empty;
        }
        $packed = inet_pton($input);
        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
            $packed = substr($packed, 12);
        }
        $address = inet_ntop($packed);
        $version = strlen($packed) === 4 ? 'IPv4' : 'IPv6';
        $local = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        if ($version === 'IPv4') {
            foreach (array('0.0.0.0/8', '100.64.0.0/10', '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4') as $range) {
                $local = $local || $this->inRange($packed, $range);
            }
        } else {
            $local = $local || !$this->inRange($packed, '2000::/3')
                || $this->inRange($packed, '2001:db8::/32') || $this->inRange($packed, '3fff::/20');
        }
        return array('address' => $address, 'version' => $version, 'local' => $local);
    }

    private function inRange($packed, $cidr)
    {
        list($network, $bits) = explode('/', $cidr);
        $network = inet_pton($network);
        if (strlen($packed) !== strlen($network)) {
            return false;
        }
        $bytes = (int) floor($bits / 8);
        $remainder = (int) $bits % 8;
        return substr($packed, 0, $bytes) === substr($network, 0, $bytes)
            && (!$remainder || ((ord($packed[$bytes]) ^ ord($network[$bytes])) & (255 << (8 - $remainder))) === 0);
    }

    private function name($record)
    {
        foreach (array('tr', 'en') as $language) {
            if (isset($record['names'][$language]) && is_string($record['names'][$language])) {
                return $record['names'][$language];
            }
        }
        return '';
    }

    public function lookup($ip)
    {
        $result = array('status' => 'no_data', 'country' => '', 'country_code' => '', 'region' => '', 'city' => '',
            'latitude' => null, 'longitude' => null, 'timezone' => '', 'provider' => '', 'asn' => '', 'release' => '');
        if (empty($ip['address'])) {
            return $result;
        }
        if (!empty($ip['local'])) {
            $result['status'] = 'private';
            return $result;
        }
        $autoload = __DIR__ . '/lib/maxmind-db-reader/autoload.php';
        if (!class_exists('MaxMind\\Db\\Reader') && is_file($autoload) && !function_exists('mmdb_autoload')) {
            require_once $autoload;
        }
        if (!class_exists('MaxMind\\Db\\Reader')) {
            $result['status'] = 'unavailable';
            return $result;
        }
        $records = array();
        $readCount = 0;
        foreach (array('city', 'asn') as $type) {
            $file = $this->dataDirectory . '/dbip-' . $type . '-lite.mmdb';
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            $reader = null;
            try {
                $reader = new \MaxMind\Db\Reader($file);
                $record = $reader->get($ip['address']);
                $records[$type] = is_array($record) ? $record : array();
                $result['release'] = gmdate('Y-m', $reader->metadata()->buildEpoch);
                $readCount++;
            } catch (\Throwable $error) {
                // An absent, unreadable or damaged database must not break the IP page.
            } finally {
                if ($reader !== null) {
                    $reader->close();
                }
            }
        }
        $city = isset($records['city']) ? $records['city'] : array();
        if (!empty($city['country']['iso_code']) && $city['country']['iso_code'] !== 'ZZ') {
            $result['country'] = $this->name($city['country']);
            $result['country_code'] = $city['country']['iso_code'];
            $result['city'] = isset($city['city']) ? $this->name($city['city']) : '';
            $result['region'] = isset($city['subdivisions'][0]) ? $this->name($city['subdivisions'][0]) : '';
            if (isset($city['location']['latitude'], $city['location']['longitude'])
                && is_numeric($city['location']['latitude']) && is_numeric($city['location']['longitude'])
                && abs($city['location']['latitude']) <= 90 && abs($city['location']['longitude']) <= 180) {
                $result['latitude'] = (float) $city['location']['latitude'];
                $result['longitude'] = (float) $city['location']['longitude'];
            }
            if (isset($city['location']['time_zone']) && is_string($city['location']['time_zone'])) {
                $result['timezone'] = $city['location']['time_zone'];
            }
        }
        if (!empty($records['asn']['autonomous_system_number'])) {
            $result['asn'] = 'AS' . (int) $records['asn']['autonomous_system_number'];
        }
        if (!empty($records['asn']['autonomous_system_organization']) && is_string($records['asn']['autonomous_system_organization'])) {
            $result['provider'] = $records['asn']['autonomous_system_organization'];
        }
        $hasData = $result['country'] !== '' || $result['asn'] !== '' || $result['provider'] !== '';
        $result['status'] = $hasData ? ($readCount === 2 ? 'ready' : 'partial') : ($readCount ? 'no_data' : 'unavailable');
        return $result;
    }
}
