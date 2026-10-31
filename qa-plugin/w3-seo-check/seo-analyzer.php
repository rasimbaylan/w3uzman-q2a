<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

/** Observations about one fetched HTML response, not rankings or an indexing guarantee. */
class W3SeoAnalyzer
{
    private $http;
    public function __construct($http) { $this->http = $http; }
    private function clean($s) { return trim(preg_replace('/\s+/u', ' ', $s)); }
    private function clip($s, $n = 300) { return function_exists('mb_substr') ? mb_substr($s, 0, $n, 'UTF-8') : implode('', array_slice(preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY), 0, $n)); }
    private function length($s) { return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : preg_match_all('/./us', $s); }
    private function check(&$r, $id, $group, $state, $label, $detail, $value = '') { $r['checks'][] = compact('id','group','state','label','detail','value'); }

    public function analyze($response, $robots = null)
    {
        $r = array('url'=>$response['url'], 'status'=>$response['status'], 'chain'=>$response['chain'], 'checked'=>time(), 'checks'=>array(), 'headings'=>array(), 'heading_counts'=>array_fill(1,6,0), 'social'=>array(), 'robots'=>array(), 'canonicals'=>array(), 'title'=>'', 'description'=>'', 'parsed'=>false);
        $this->check($r, 'http', 'Erişim', $r['status'] === 200 ? 'pass' : 'critical', 'HTTP yanıtı', $r['status'] === 200 ? 'Sayfa bu istemciye HTTP 200 yanıtı verdi. Bu, Google tarafından dizine eklendiği anlamına gelmez.' : 'Hedef sayfa HTTP 200 vermedi. Hata veya erişim engeli sayfasının etiketlerinden SEO sonucu üretilmedi.', 'HTTP ' . $r['status']);
        if ($r['status'] !== 200) { return $this->finish($r); }
        $this->check($r, 'https', 'Erişim', strpos($r['url'], 'https://') === 0 ? 'pass' : 'warning', 'HTTPS kullanımı', strpos($r['url'], 'https://') === 0 ? 'Son sayfa doğrulanmış HTTPS bağlantısı üzerinden alındı.' : 'Son adres HTTP kullanıyor. Geçerli sertifika ve HTTP → HTTPS yönlendirmesini değerlendirin.');
        if (count($r['chain']) > 1) { $this->check($r, 'redirects', 'Erişim', 'info', 'Yönlendirme zinciri', 'Yönlendirme tek başına hata değildir. İç bağlantılarınızı mümkünse tercih edilen son adrese verin.', (count($r['chain'])-1) . ' yönlendirme'); }
        $ct = isset($response['headers']['content-type'][0]) ? $response['headers']['content-type'][0] : '';
        if (!preg_match('~^(?:text/html|application/xhtml\+xml)(?:\s*;|$)~i', $ct)) { throw new RuntimeException('Yanıt HTML sayfası değil veya Content-Type bildirimi eksik. PDF, görsel ve diğer dosyalar bu araçta analiz edilmez.'); }
        if (!class_exists('DOMDocument')) { throw new RuntimeException('Hostingde PHP DOM/XML uzantısı etkin olmalıdır.'); }
        if (substr_count($response['body'], '<') > 25000) { throw new RuntimeException('HTML yapısı güvenli analiz sınırını aşıyor. Eksik içerikten sonuç üretilmedi.'); }
        $html = $this->utf8($response['body'], $ct);
        $doc = new DOMDocument(); $previous = libxml_use_internal_errors(true);
        try { $loaded = $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        if (!$loaded) { throw new RuntimeException('HTML belgesi okunamadı.'); }
        $xp = new DOMXPath($doc); $r['parsed'] = true;
        if ($xp->query('//meta')->length > 1000 || $xp->query('//head/link')->length > 1000) { throw new RuntimeException('Sayfadaki metadata sayısı güvenli analiz sınırını aşıyor.'); }
        $titles = $xp->query('//head/title'); $r['title'] = $titles->length ? $this->clean($titles->item(0)->textContent) : '';
        $this->check($r, 'title', 'Başlık ve açıklama', $r['title'] === '' ? 'critical' : ($titles->length > 1 ? 'warning' : 'pass'), 'Sayfa başlığı (title)', $r['title'] === '' ? 'Sayfayı açıkça anlatan, sayfaya özgü bir <title> ekleyin.' : ($titles->length > 1 ? 'Birden fazla title bulundu. Tek, açıklayıcı bir sayfa başlığı kullanın.' : $this->length($r['title']) . ' karakter. Google sabit karakter sınırı tanımlamaz; görünüm cihaza ve sorguya göre değişebilir.'), $this->clip($r['title'], 500));
        $meta = array(); $headMeta = array();
        foreach ($xp->query('//meta[@name or @property]') as $node) {
            $keys = array_unique(array_filter(array(strtolower(trim($node->getAttribute('name'))),strtolower(trim($node->getAttribute('property'))))));
            $value = $this->clean($node->getAttribute('content'));
            foreach ($keys as $key) { $meta[$key][] = $value; if ($node->parentNode && strtolower($node->parentNode->nodeName) === 'head') { $headMeta[$key][] = $value; } }
        }
        $descs = isset($headMeta['description']) ? $headMeta['description'] : array(); $r['description'] = isset($descs[0]) ? $descs[0] : '';
        $this->check($r, 'description', 'Başlık ve açıklama', $r['description'] === '' || count($descs) > 1 ? 'warning' : 'pass', 'Meta açıklama', $r['description'] === '' ? 'Sayfaya özgü, yararlı bir özet ekleyin. Google açıklamayı sayfa içeriğinden de oluşturabilir; eksik açıklama dizine ekleme engeli değildir.' : (count($descs) > 1 ? 'Birden fazla description bulundu. Sayfayı anlatan tek bir meta açıklama tercih edin.' : $this->length($r['description']) . ' karakter. Sabit bir Google karakter sınırı yoktur; açıklamanızın gerçek içeriği özetlemesi önemlidir.'), $this->clip($r['description'], 800));
        $base = $r['url']; $bases = $xp->query('//head/base[@href]');
        if ($bases->length) { $candidate = $this->http->absolute($bases->item(0)->getAttribute('href'), $base); if ($candidate) { $base = $candidate; } }
        foreach ($xp->query('//head/link[@href]') as $node) {
            if (in_array('canonical', preg_split('/\s+/', strtolower(trim($node->getAttribute('rel')))), true)) {
                $raw = trim($node->getAttribute('href')); $r['canonicals'][] = array('source'=>'HTML', 'raw'=>$this->clip($raw,2048), 'url'=>$this->http->absolute($raw, $base));
            }
        }
        foreach (isset($response['headers']['link']) ? $response['headers']['link'] : array() as $line) {
            preg_match_all('~<([^>]*)>((?:;[^,]*)*)~', $line, $matches, PREG_SET_ORDER);
            foreach ($matches as $m) { if (preg_match('~;\s*rel\s*=\s*["\']?([^"\';,]+)~i', $m[2], $rel) && in_array('canonical', preg_split('/\s+/', strtolower(trim($rel[1]))), true)) { $r['canonicals'][] = array('source'=>'HTTP Link', 'raw'=>$this->clip($m[1],2048), 'url'=>$this->http->absolute($m[1], $r['url'])); } }
        }
        if (!$r['canonicals']) { $this->check($r, 'canonical', 'Canonical', 'info', 'Canonical bildirimi bulunamadı', 'Canonical zorunlu bir etiket değildir. Benzer adresleriniz varsa tercih edilen sürümü tutarlı biçimde belirtmek yararlı olabilir.'); }
        else {
            $targets = array(); $bad = false; $relative = false; $fragment = false;
            foreach ($r['canonicals'] as $c) { if (!$c['url'] || $c['raw'] === '') { $bad = true; } else { $targets[] = $c['url']; } if (!preg_match('~^https?://~i', $c['raw'])) { $relative = true; } if (strpos($c['raw'], '#') !== false) { $fragment = true; } }
            $targets = array_values(array_unique($targets));
            if ($bad || count($targets) > 1) { $state = 'critical'; $detail = 'Geçersiz veya birbiriyle çelişen canonical bildirimleri bulundu. Tek ve tutarlı bir tercih edilen adres belirtin.'; }
            elseif ($fragment || $relative || count($r['canonicals']) > 1) { $state = 'warning'; $detail = 'Tek bir mutlak HTTP(S) canonical adresi tercih edin; fragment (#) kullanmayın ve gereksiz tekrarları temizleyin.'; }
            elseif ($targets[0] !== $r['url']) { $state = 'info'; $detail = 'Canonical başka bir adresi gösteriyor. Bu bilinçli olabilir; hedefin gerçekten eşdeğer içerik olduğundan emin olun.'; }
            else { $state = 'pass'; $detail = 'Bu yanıtta kendisini gösteren tek bir canonical bulundu. Google’ın seçtiği canonical veya hedefin erişimi bu araçla doğrulanmaz.'; }
            $this->check($r, 'canonical', 'Canonical', $state, 'Canonical bildirimi', $detail, implode(' · ', $targets));
        }
        $allHeadings = $xp->query('//body//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6][not(ancestor::template) and not(ancestor::noscript)]'); $prev = 0; $jumps = 0; $empty = 0;
        foreach ($allHeadings as $node) {
            $level = (int) substr(strtolower($node->nodeName),1); $text = $this->clean($node->textContent); $r['heading_counts'][$level]++;
            if ($prev && $level > $prev+1) { $jumps++; } if ($text === '') { $empty++; } $prev = $level;
            if (count($r['headings']) < 50) { $r['headings'][] = array('level'=>$level, 'text'=>$this->clip($text,200)); }
        }
        $h1 = $r['heading_counts'][1];
        $this->check($r, 'h1', 'Başlık hiyerarşisi', $h1 === 1 ? 'pass' : ($h1 === 0 ? 'warning' : 'info'), 'Ana başlık (H1)', $h1 === 0 ? 'Ana içeriği açıklayan belirgin bir ana başlık kullanmayı değerlendirin.' : ($h1 > 1 ? 'Birden fazla H1 otomatik Google cezası anlamına gelmez. Ana başlığın belirginliğini ve anlamsal yapıyı inceleyin.' : 'Bir H1 bulundu. Başlığın içeriği doğru anlattığını ve kullanıcıya görünür olduğunu ayrıca kontrol edin.'), $h1 . ' H1');
        $this->check($r, 'hierarchy', 'Başlık hiyerarşisi', $jumps || $empty ? 'warning' : 'pass', 'Başlık sırası', $jumps || $empty ? 'Anlamsal yapı ve erişilebilirlik için boş başlıkları ve atlanan seviyeleri inceleyin. Bu bir sıralama cezası tespiti değildir.' : 'Kaynak HTML’deki başlıklarda boşluk veya ileri seviye atlaması görülmedi. CSS görünürlüğü ve JavaScript değişiklikleri incelenmez.', $allHeadings->length . ' başlık · ' . $jumps . ' seviye atlaması · ' . $empty . ' boş');
        $rules = array();
        // Google also honors robots meta in the body; other metadata stays head-only.
        foreach (array('robots','googlebot') as $key) { foreach (isset($meta[$key]) ? $meta[$key] : array() as $value) { $r['robots'][] = array('source'=>'meta ' . $key, 'value'=>$value); $rules[] = $value; } }
        foreach (isset($response['headers']['x-robots-tag']) ? $response['headers']['x-robots-tag'] : array() as $value) {
            $r['robots'][] = array('source'=>'X-Robots-Tag', 'value'=>$this->clip($value,1000));
            $rules = array_merge($rules, $this->headerRules($value));
        }
        $tokens = array(); foreach ($rules as $rule) { foreach (preg_split('/\s*,\s*|\s+/', strtolower($rule)) as $token) { $tokens[$token] = true; } }
        $noindex = isset($tokens['noindex']) || isset($tokens['none']);
        $this->check($r, 'robots-index', 'Robots', $noindex ? 'critical' : 'pass', 'Googlebot için noindex', $noindex ? 'Bu yanıtta noindex/none görüldü. Sayfanın aramada yer almasını istiyorsanız kuralı gözden geçirin; bilinçli gizlilik tercihinizse kaldırmayın. Çelişen index/noindex kurallarında kısıtlayıcı olan geçerlidir.' : 'Bu yanıtta Googlebot’a uygulanan açık noindex/none görülmedi. Bu sonuç taranabilirlik, dizine eklenme veya sıralama garantisi değildir.', $noindex ? 'noindex / none tespit edildi' : 'noindex tespit edilmedi');
        if (isset($tokens['nofollow']) || isset($tokens['none'])) { $this->check($r, 'robots-follow', 'Robots', 'info', 'Sayfa düzeyinde nofollow', 'Bağlantılara ilişkin kısıtlayıcı bir kural var. Bunun bilinçli bir yayın tercihi olup olmadığını inceleyin.'); }
        if (isset($tokens['nosnippet']) || preg_match('/\bmax-snippet\s*:\s*0\b/i', implode(',', $rules))) { $this->check($r, 'robots-snippet', 'Robots', 'info', 'Snippet kısıtlaması', 'nosnippet veya max-snippet:0 bildirimi arama açıklaması gösterimini kısıtlar.'); }
        if (stripos(implode(',', $rules), 'unavailable_after') !== false) { $this->check($r, 'robots-expiry', 'Robots', 'info', 'Tarihli yayın kısıtlaması', 'unavailable_after bildirimi bulundu. Tarih ve saat dilimini kontrol edin; bu araç zamanlanmış kaldırmanın Google’daki durumunu doğrulamaz.'); }
        if ($robots && isset($robots['body'])) {
            $decision = $this->robotsDecision($robots['body'], $r['url']);
            $this->check($r, 'robots-file', 'Robots', $decision['blocked'] ? 'warning' : 'pass', 'robots.txt / Googlebot', $decision['blocked'] ? 'Bu adres için Googlebot taramasını engelleyen bir kural eşleşti. robots.txt engeli tek başına URL’nin dizinden çıkarıldığı anlamına gelmez; engellenen sayfadaki noindex okunamayabilir.' : 'Bu robots.txt yanıtındaki Googlebot/* kurallarında adresi engelleyen eşleşme görülmedi. Bu bir dizine ekleme garantisi değildir.', $decision['rule'] !== '' ? $decision['rule'] : 'Eşleşen engelleme kuralı yok');
        } else { $this->check($r, 'robots-file', 'Robots', 'info', 'robots.txt kontrolü', $robots && isset($robots['note']) ? $robots['note'] : 'robots.txt doğrulanamadı.'); }
        foreach ($headMeta as $key => $values) { if (strpos($key,'og:') === 0 || strpos($key,'twitter:') === 0) { $r['social'][$key] = array_map(function ($v) { return $this->clip($v,1000); }, $values); } }
        $missing = array(); $invalid = array();
        foreach (array('og:title','og:type','og:image','og:url') as $key) { if (empty($headMeta[$key][0])) { $missing[] = $key; } }
        foreach (array('og:image','og:url','twitter:image') as $key) { if (!empty($headMeta[$key][0]) && !preg_match('~^https?://~i', $headMeta[$key][0])) { $invalid[] = $key; } elseif (!empty($headMeta[$key][0]) && !$this->http->absolute($headMeta[$key][0], $base)) { $invalid[] = $key; } }
        $this->check($r, 'opengraph', 'Sosyal paylaşım', $missing || $invalid ? 'warning' : 'pass', 'Open Graph etiketleri', $missing ? 'Paylaşım önizlemesi için temel alanlar eksik: ' . implode(', ', $missing) . '. Bunlar Google dizine ekleme koşulu değildir.' : ($invalid ? 'Sosyal görsel ve adres alanlarında geçerli mutlak HTTP(S) adresleri kullanın: ' . implode(', ', $invalid) . '.' : 'Dört temel Open Graph alanı bulundu. Görsel dosyası, boyutları ve platformların gerçek önizlemesi ayrıca doğrulanmalıdır.'));
        $card = isset($headMeta['twitter:card'][0]) ? $headMeta['twitter:card'][0] : '';
        $this->check($r, 'x-card', 'Sosyal paylaşım', $card === '' ? 'info' : (in_array($card,array('summary','summary_large_image','app','player'),true) ? 'pass' : 'warning'), 'X / Twitter Card', $card === '' ? 'Açık bir twitter:card bildirimi bulunamadı. Bu, Google SEO hatası değildir; sosyal paylaşım görünümünü platformda kontrol edin.' : 'Bildirilen kart türü aşağıdadır. Gerekli diğer alanları ve gerçek görünümü platformun belgeleriyle doğrulayın.', $card);
        return $this->finish($r);
    }

    private function finish($r) { $r['counts'] = array('critical'=>0,'warning'=>0,'info'=>0,'pass'=>0); foreach ($r['checks'] as $check) { $r['counts'][$check['state']]++; } return $r; }
    private function utf8($html, $ct)
    {
        $encoding = null;
        if (preg_match('/charset\s*=\s*["\']?([a-z0-9_-]+)/i', $ct, $m) || preg_match('/<meta\b[^>]*charset\s*=\s*["\']?([a-z0-9_-]+)/i', substr($html,0,8192), $m)) { $encoding = $m[1]; }
        if (!$encoding) { $encoding = preg_match('//u', $html) ? 'UTF-8' : 'Windows-1252'; }
        if (strcasecmp($encoding, 'UTF-8') !== 0) { $converted = @iconv($encoding, 'UTF-8//IGNORE', $html); if ($converted !== false) { $html = $converted; } }
        if (!preg_match('//u', $html)) { $html = iconv('UTF-8','UTF-8//IGNORE',$html); }
        return $html;
    }

    private function headerRules($value)
    {
        $out = array(); $agent = '*';
        foreach (explode(',', strtolower($value)) as $part) {
            $part = trim($part);
            if (preg_match('/^([a-z0-9_-]+)\s*:\s*(.*)$/', $part, $m) && !in_array($m[1],array('max-snippet','max-image-preview','max-video-preview','unavailable_after'),true)) { $agent = $m[1]; $part = trim($m[2]); }
            if ($agent === '*' || $agent === 'googlebot') { $out[] = $part; }
        }
        return $out;
    }

    private function robotsPath($value)
    {
        $value = preg_replace_callback('/%[0-9a-f]{2}/i', function ($m) { $c = chr(hexdec(substr($m[0],1))); return preg_match('/[a-z0-9._~-]/i',$c) ? $c : strtoupper($m[0]); }, $value);
        return preg_replace_callback('/[^\x00-\x7f]/', function ($m) { return rawurlencode($m[0]); }, $value);
    }

    public function robotsDecision($text, $url)
    {
        $groups = array(); $agents = array(); $rules = array(); $hadRule = false;
        foreach (preg_split('/\r\n|\r|\n/', ltrim($text, "\xef\xbb\xbf")) as $line) {
            $line = trim(explode('#',$line,2)[0]); if ($line === '' || strpos($line,':') === false) { continue; }
            list($key,$value) = explode(':',$line,2); $key = strtolower(trim($key)); $value = trim($value);
            if ($key === 'user-agent') { if ($hadRule) { $groups[] = array($agents,$rules); $agents=array(); $rules=array(); $hadRule=false; } $agents[]=strtolower($value); }
            elseif ($agents && in_array($key,array('allow','disallow'),true)) { $hadRule=true; if ($value !== '' && $value[0] === '/') { $rules[]=array($key,$value); } }
        }
        if ($agents) { $groups[]=array($agents,$rules); }
        $specific = false; foreach ($groups as $group) { if (in_array('googlebot',$group[0],true)) { $specific=true; } }
        $p = parse_url($url); $path = $this->robotsPath((isset($p['path']) ? $p['path'] : '/') . (isset($p['query']) ? '?' . $p['query'] : ''));
        $best=-1; $blocked=false; $winner='';
        foreach ($groups as $group) {
            if (!in_array($specific ? 'googlebot' : '*',$group[0],true)) { continue; }
            foreach ($group[1] as $rule) {
                $pattern=$this->robotsPath($rule[1]); $end=substr($pattern,-1)==='$'; if ($end) { $pattern=substr($pattern,0,-1); }
                $regex='~^'.str_replace('\\*','.*',preg_quote($pattern,'~')).($end?'$':'').'~D';
                if (!preg_match($regex,$path)) { continue; }
                $length=strlen(preg_replace('/%[0-9A-F]{2}/','x',str_replace('*','',$pattern)));
                if ($length>$best || ($length===$best && $rule[0]==='allow')) { $best=$length; $blocked=$rule[0]==='disallow'; $winner=$rule[0].': '.$rule[1]; }
            }
        }
        return array('blocked'=>$blocked,'rule'=>$winner);
    }
}
