<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

// Only application route identifiers are translated, never handles, article slugs,
// tag names, query payloads, static assets, or external URLs. Existing category
// aliases below are translated only in category-route positions, without DB edits.
function w3_url_route_map()
{
    return array(
        'tag' => 'etiket', 'feedback' => 'bize-ulasin', 'questions' => 'haberler', 'hot' => 'populer-haberler',
        'users' => 'uyeler', 'user' => 'uye', 'categories' => 'kategoriler', 'tags' => 'etiketler',
        'unanswered' => 'cevaplanmamis', 'ask' => 'sor', 'updates' => 'guncellemeler',
        'account' => 'hesap', 'favorites' => 'favoriler', 'messages' => 'mesajlar', 'message' => 'mesaj',
        'activity' => 'hareketler', 'answers' => 'cevaplar', 'comments' => 'yorumlar', 'logins' => 'girisler',
        'login' => 'giris', 'logout' => 'cikis', 'register' => 'kayit', 'search' => 'ara',
        'forgot' => 'sifremi-unuttum', 'reset' => 'sifre-yenile', 'confirm' => 'eposta-dogrula',
        'unsubscribe' => 'abonelikten-ayril', 'ip' => 'ip-bilgisi', 'admin' => 'yonetim', 'feed' => 'akis',
        'domain-oneri' => 'alan-adi-oneri', 'domain-sorgulama' => 'alan-adi-sorgulama',
        'responsive-kontrol' => 'mobil-uyumluluk-kontrolu', 'whatismyip' => 'ip-adresim',
        'tag-edit' => 'etiket-duzenle', 'listlinks' => 'paylasilan-baglantilar',
        'database' => 'veritabani', 'domain' => 'alan-adi', 'hosting-sunucu' => 'hosting-sunucu', 'diğer' => 'diger',
    );
}

function w3_url_child_map($route)
{
    switch ($route) {
        case 'users': return array('new' => 'yeni-kullanicilar', 'special' => 'ozel-kullanicilar', 'blocked' => 'engelli-kullanicilar');
        case 'user': return array('wall' => 'duvar', 'activity' => 'yeni-etkinlikler', 'questions' => 'sorular', 'answers' => 'cevaplar');
        case 'favorites': return array('questions' => 'haberler', 'users' => 'uyeler', 'tags' => 'etiketler');
        case 'messages': return array('sent' => 'gonderilen');
        case 'feed': return array('questions' => 'haberler', 'hot' => 'populer-haberler', 'unanswered' => 'cevaplanmamis', 'answers' => 'cevaplar', 'comments' => 'yorumlar', 'activity' => 'hareketler', 'qa' => 'tum-icerikler', 'tag' => 'etiket', 'search' => 'arama');
        case 'admin': return array(
            'general' => 'genel', 'emails' => 'epostalar', 'users' => 'uyeler', 'layout' => 'gorunum',
            'posting' => 'icerik-ekleme', 'viewing' => 'goruntuleme', 'lists' => 'listeler', 'categories' => 'kategoriler',
            'permissions' => 'izinler', 'pages' => 'sayfalar', 'feeds' => 'akislar', 'points' => 'puanlar',
            'spam' => 'istenmeyen-icerik', 'caching' => 'onbellek', 'stats' => 'istatistikler', 'mailing' => 'toplu-eposta',
            'plugins' => 'eklentiler', 'moderate' => 'icerik-onayi', 'flagged' => 'bildirilenler', 'hidden' => 'gizlenenler',
            'approve' => 'uye-onayi', 'layoutwidgets' => 'bilesenler', 'recalc' => 'yeniden-hesaplama',
            'userfields' => 'profil-alanlari', 'usertitles' => 'uye-unvanlari',
        );
    }
    return array();
}

// One public root per news category; storage IDs and native slugs remain unchanged.
function w3_category_archive_roots()
{
    return array(
        'web-yazilim' => 'yazilim', 'yapay-zeka-ai' => 'yapay-zeka',
        'web-tasarim' => 'web-tasarim', 'grafik-tasarim' => 'grafik-tasarim',
        'sosyal-medya' => 'sosyal-medya', 'cms' => 'cms', 'seo' => 'seo',
        'e-ticaret' => 'e-ticaret', 'diğer' => 'diger',
        'hosting-sunucu' => 'hosting-sunucu', 'database' => 'veritabani',
        'google' => 'google', 'domain' => 'alan-adi', 'mobil' => 'mobil', 'masaustu' => 'masaustu',
        'uygulamalar' => 'uygulamalar', 'otomasyon' => 'otomasyon', 'siber-guvenlik' => 'siber-guvenlik',
        // Retired course root is recognized only to redirect old URLs to MySQL.
        'mysql-ucretsiz-video-dersleri' => 'mysql-dersleri',
        // Retired category alias only; /haberler remains the all-news archive.
        'haberler' => 'teknoloji-haberleri',
    );
}

function w3_url_translate_children($parts, $incoming)
{
    $route = strtr($parts[0], 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    $index = $route === 'user' ? 2 : 1;
    $map = w3_url_child_map($route);
    if (isset($parts[$index]) && $map) {
        $value = $parts[$index];
        $suffix = '';
        if ($route === 'feed' && preg_match('/^(.*)(\.(?:rss|xml))$/', $value, $match)) {
            $value = $match[1];
            $suffix = $match[2];
        }
        if ($incoming) {
            $key = array_search($value, $map, true);
            if ($key !== false) { $parts[$index] = $key . $suffix; }
        } elseif (isset($map[$value])) {
            $parts[$index] = $map[$value] . $suffix;
        }
    }
    $categories = w3_category_archive_roots();
    $start = null;
    if (in_array($route, array('questions','categories','unanswered','hot','answers','comments','activity'), true)) { $start = 1; }
    elseif ($route === 'feed' && isset($parts[1])) {
        $feedtypes = w3_url_child_map('feed');
        unset($feedtypes['tag'], $feedtypes['search']);
        $feedtype = preg_replace('/\.(rss|xml)$/', '', $parts[1]);
        if (isset($feedtypes[$feedtype]) || in_array($feedtype, $feedtypes, true)) { $start = 2; }
    }
    elseif (isset($categories[$route])) { $start = 0; }
    if ($start !== null) {
        if ($route === 'questions' && isset($parts[$start]) && in_array($parts[$start], w3_news_sort_paths(), true)) { $start++; }
        if ($route === 'unanswered' && isset($parts[$start]) && in_array($parts[$start], array('cozum-secilmeyen','oy-almayan'), true)) { $start++; }
        // Only the root category is aliased; identical child slugs must not be rewritten.
        if (isset($parts[$start])) {
            $i = $start;
            $value = $parts[$i];
            $suffix = '';
            if ($route === 'feed' && preg_match('/^(.*)(\.(?:rss|xml))$/', $value, $match)) { $value = $match[1]; $suffix = $match[2]; }
            if ($value === 'barindirma-sunucu') {
                $value = 'hosting-sunucu';
                $parts[$i] = $value . $suffix;
            }
            if ($incoming) {
                $key = array_search($value, $categories, true);
                if ($key !== false) { $parts[$i] = $key . $suffix; }
            } elseif (isset($categories[$value])) { $parts[$i] = $categories[$value] . $suffix; }
        }
    }
    return $parts;
}

function w3_url_public_parts()
{
    if (isset($_GET['qa-rewrite']) && is_string($_GET['qa-rewrite'])) {
        $parts = explode('/', qa_gpc_to_string($_GET['qa-rewrite']));
        // Apache decodes &/# in rewrite captures. Compare the original public
        // path instead, so canonical handles containing those characters never loop.
        if (!empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['SCRIPT_NAME'])) {
            $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            if (strpos($path, $base . '/') === 0) { $parts = explode('/', substr($path, strlen($base) + 1)); }
        }
    } elseif (isset($_GET['qa']) && is_string($_GET['qa'])) {
        $parts = explode('/', qa_gpc_to_string($_GET['qa']));
        if (count($parts) === 1) {
            for ($i = 1; $i < 10; $i++) {
                if (isset($_GET['qa_' . $i]) && is_string($_GET['qa_' . $i])) { $parts[] = qa_gpc_to_string($_GET['qa_' . $i]); }
            }
        }
    } else {
        $path = rawurldecode((string) parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH));
        $position = strpos($path, '/index.php/');
        $parts = $position !== false ? explode('/', substr($path, $position + 11)) : array();
    }
    return array_values(array_filter($parts, 'strlen'));
}

function qa_get_request_map()
{
    return array_merge(qa_get_request_map_base(), w3_url_route_map());
}

// Keep Q2A's internal sort keys, but expose readable Turkish news URLs.
function w3_news_sort_paths()
{
    return array('hot' => 'populer', 'votes' => 'en-cok-oylanan', 'answers' => 'en-cok-yorumlanan', 'views' => 'en-cok-okunan');
}

// Retired/moved category paths only: never rewrite article slugs, tags or handles.
function w3_software_category_alias_parts($parts)
{
    $route = isset($parts[0]) ? strtolower($parts[0]) : '';
    $offset = null;
    if (in_array($route, array('questions','hot','categories','unanswered','answers','comments','activity'), true)) {
        $offset = 1;
        if ($route === 'questions' && isset($parts[1]) && in_array($parts[1], w3_news_sort_paths(), true)) { $offset = 2; }
    } elseif ($route === 'feed' && isset($parts[1]) && in_array(preg_replace('/\.(rss|xml)$/', '', $parts[1]), array('questions','hot','unanswered','answers','comments','activity','qa'), true)) {
        $offset = 2;
    }
    if ($route === 'unanswered' && isset($parts[$offset]) && in_array($parts[$offset], array('cozum-secilmeyen','oy-almayan'), true)) { $offset++; }
    if ($offset === null || !isset($parts[$offset])) { return $parts; }
    $root = preg_replace('/\.(rss|xml)$/', '', $parts[$offset]);
    if (in_array($root, array('haberler', 'teknoloji-haberleri'), true) && count($parts) === $offset + 1) {
        // Drop only the retired category filter, preserving news sorting and feeds.
        $suffix = substr($parts[$offset], strlen($root));
        array_splice($parts, $offset, 1);
        if ($route === 'feed' && $suffix !== '' && !preg_match('/\.(rss|xml)$/', $parts[1])) { $parts[1] .= $suffix; }
        return $parts;
    }
    if ($root === 'mysql-ucretsiz-video-dersleri') {
        $suffix = '';
        $last = $parts[count($parts) - 1];
        if ($route === 'feed' && preg_match('/(\.(?:rss|xml))$/', $last, $match)) { $suffix = $match[1]; }
        array_splice($parts, $offset, count($parts) - $offset, array('database', 'mysql' . $suffix));
        return $parts;
    }
    if (!isset($parts[$offset + 1])) { return $parts; }
    $last = $parts[$offset + 1];
    $suffix = '';
    if ($route === 'feed' && preg_match('/^(.*)(\.(rss|xml))$/', $last, $match)) { $last = $match[1]; $suffix = $match[2]; }
    $map = array(
        'uygulamalar/uretkenlik' => array('uygulamalar','ofis-araclari'),
        'uygulamalar/not-alma' => array('uygulamalar','ofis-araclari'),
        'uygulamalar/proje-yonetimi' => array('uygulamalar','ofis-araclari'),
        'web-tasarim/javascript' => array('web-yazilim','javascript'),
        'web-yazilim/ajax' => array('web-yazilim','javascript'),
        'web-yazilim/objective-c-swift' => array('web-yazilim'),
        'web-tasarim/semantic-ui' => array('web-tasarim','css'),
        'google/youtube' => array('sosyal-medya','youtube'),
        'google/adwords' => array('google','ads'),
        'google/google-analytics' => array('google','analytics'),
        'google/webmaster-araçları' => array('google','search-console'),
        'google/webmaster-araclari' => array('google','search-console'),
        'sosyal-medya/twitter' => array('sosyal-medya','x'),
    );
    $key = $parts[$offset] . '/' . $last;
    if (isset($map[$key])) {
        $replacement = $map[$key];
        $replacement[count($replacement)-1] .= $suffix;
        array_splice($parts, $offset, 2, $replacement);
    }
    return $parts;
}

function qa_path($request, $params = null, $rooturl = null, $neaturls = null, $anchor = null)
{
    $request = implode('/', w3_software_category_alias_parts(explode('/', (string) $request)));
    $parts = w3_url_translate_children(explode('/', (string) $request), false);
    $request = implode('/', $parts);
    if ($parts[0] === 'unanswered') {
        $paths = array('selected' => 'cozum-secilmeyen', 'upvotes' => 'oy-almayan');
        $by = is_array($params) && isset($params['by']) && is_string($params['by']) ? $params['by'] : null;
        if (isset($paths[$by])) {
            array_splice($parts, 1, 0, array($paths[$by]));
            $request = implode('/', $parts);
            unset($params['by']);
        }
    }
    if ($parts[0] === 'questions' || $parts[0] === 'hot') {
        $paths = w3_news_sort_paths();
        $sort = $parts[0] === 'hot' ? 'hot' : (is_array($params) && isset($params['sort']) && is_string($params['sort']) ? $params['sort'] : null);
        if (isset($paths[$sort])) {
            $parts[0] = 'questions/' . $paths[$sort];
            $request = implode('/', $parts);
            if (is_array($params)) { unset($params['sort']); }
        }
    }
    // Category archives have one public root; native category IDs/slugs stay intact.
    $publicparts = explode('/', $request);
    if ($publicparts[0] === 'questions') {
        $offset = isset($publicparts[1]) && in_array($publicparts[1], w3_news_sort_paths(), true) ? 2 : 1;
        if (isset($publicparts[$offset]) && in_array($publicparts[$offset], w3_category_archive_roots(), true)) {
            $shortparts = array($publicparts[$offset]);
            if ($offset === 2) { $shortparts[] = $publicparts[1]; }
            $request = implode('/', array_merge($shortparts, array_slice($publicparts, $offset + 1)));
        }
    }
    return qa_path_base($request, $params, $rooturl, $neaturls, $anchor);
}

function qa_index_set_request()
{
    $publicparts = w3_url_public_parts();
    // Capture the public route before Q2A maps both prefixes to internal "tag".
    $legacy = false;
    if (isset($_GET['qa-rewrite']) && is_string($_GET['qa-rewrite'])) {
        $parts = explode('/', qa_gpc_to_string($_GET['qa-rewrite']));
        $legacy = in_array(strtolower($parts[0]), array('tag', 'feedback', 'sorular', 'questions', 'hot'), true);
    } elseif (isset($_GET['qa']) && is_string($_GET['qa'])) {
        $parts = explode('/', qa_gpc_to_string($_GET['qa']));
        $legacy = in_array(strtolower($parts[0]), array('tag', 'feedback', 'sorular', 'questions', 'hot'), true);
    } elseif (!empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['SCRIPT_NAME'])) {
        // Also support old /index.php/tag/... links and subdirectory installs.
        $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $script = $_SERVER['SCRIPT_NAME'];
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
        foreach (array($base . '/tag', $script . '/tag', $base . '/feedback', $script . '/feedback', $base . '/sorular', $script . '/sorular', $base . '/questions', $script . '/questions', $base . '/hot', $script . '/hot') as $prefix) {
            if (strcasecmp($path, $prefix) === 0 || strncasecmp($path, $prefix . '/', strlen($prefix) + 1) === 0) {
                $legacy = true;
                break;
            }
        }
    }

    qa_index_set_request_base();
    // Resolve the short public archive into Q2A's existing category route.
    $archiveRoots = w3_category_archive_roots();
    $archiveRoot = strtolower((string) qa_request_part(0));
    if ($archiveRoot === 'barindirma-sunucu') { $archiveRoot = 'hosting-sunucu'; }
    $nativeRoot = isset($archiveRoots[$archiveRoot]) ? $archiveRoot : array_search($archiveRoot, $archiveRoots, true);
    if ($nativeRoot !== false) {
        global $qa_used_url_format;
        $shortparts = qa_request_parts(1);
        $sortpart = $shortparts && in_array($shortparts[0], w3_news_sort_paths(), true) ? array_shift($shortparts) : null;
        $nativeparts = array('questions');
        if ($sortpart !== null) { $nativeparts[] = $sortpart; }
        $nativeparts[] = $nativeRoot;
        qa_set_request(implode('/', array_merge($nativeparts, $shortparts)), qa_path_to_root(), $qa_used_url_format);
    }
    if (strtolower((string) qa_request_part(0)) === 'sorular') {
        global $qa_used_url_format;
        $parts = qa_request_parts(1);
        qa_set_request('questions' . ($parts ? '/' . implode('/', $parts) : ''), qa_path_to_root(), $qa_used_url_format);
    }

    $route = strtr((string) qa_request_part(0), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    $routeparts = w3_url_translate_children(qa_request_parts(), true);
    if ($routeparts !== qa_request_parts()) {
        global $qa_used_url_format;
        qa_set_request(implode('/', $routeparts), qa_path_to_root(), $qa_used_url_format);
    }
    $byredirect = false;
    if ($route === 'unanswered') {
        $parts = qa_request_parts(1);
        $paths = array('selected' => 'cozum-secilmeyen', 'upvotes' => 'oy-almayan');
        $by = $parts ? array_search($parts[0], $paths, true) : false;
        if ($by !== false) {
            array_shift($parts);
            $byredirect = isset($_GET['by']);
            $_GET['by'] = $by;
            global $qa_used_url_format;
            qa_set_request('unanswered' . ($parts ? '/' . implode('/', $parts) : ''), qa_path_to_root(), $qa_used_url_format);
        }
    }
    $newsredirect = false;
    if ($route === 'questions' || $route === 'hot') {
        $parts = qa_request_parts(1);
        $paths = w3_news_sort_paths();
        $querysort = isset($_GET['sort']) && is_string($_GET['sort']) ? qa_gpc_to_string($_GET['sort']) : null;
        $pathsort = $parts ? array_search($parts[0], $paths, true) : false;
        if ($route === 'hot') {
            $_GET['sort'] = 'hot';
            $newsredirect = true;
        } elseif ($pathsort !== false) {
            array_shift($parts);
            // The path is authoritative; remove conflicting/duplicate query variants.
            $newsredirect = isset($_GET['sort']);
            $_GET['sort'] = $pathsort;
        } elseif (isset($paths[$querysort])) {
            $newsredirect = true;
        }
        if ($route === 'hot' || $pathsort !== false) {
            global $qa_used_url_format;
            qa_set_request('questions' . ($parts ? '/' . implode('/', $parts) : ''), qa_path_to_root(), $qa_used_url_format);
            $route = 'questions';
        }
    }

    $categoryparts = w3_software_category_alias_parts(qa_request_parts());
    $categoryredirect = $categoryparts !== qa_request_parts();
    if ($categoryredirect) {
        global $qa_used_url_format;
        qa_set_request(implode('/', $categoryparts), qa_path_to_root(), $qa_used_url_format);
    }
    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    if ($categoryredirect && in_array($method, array('GET','HEAD'), true)) {
        header('Location: ' . qa_path(qa_request(), $_GET, null, QA_URL_FORMAT_NEAT), true, 301);
        qa_exit('w3_software_category_redirect');
    }
    if ($newsredirect && in_array($method, array('GET', 'HEAD'), true)) {
        header('Location: ' . qa_path(qa_request(), $_GET, null, QA_URL_FORMAT_NEAT), true, 301);
        qa_exit('w3_news_url_redirect');
    }
    if ($legacy && in_array($route, array('tag', 'feedback', 'questions', 'hot'), true) && in_array($method, array('GET', 'HEAD'), true)) {
        $parts = qa_request_parts(1);
        $request = $route === 'feedback' ? 'feedback' : ($parts ? 'tag/' . implode('/', $parts) : 'tags');
        if ($route === 'questions') { $request = 'questions' . ($parts ? '/' . implode('/', $parts) : ''); }
        if ($route === 'hot') { $request = 'hot' . ($parts ? '/' . implode('/', $parts) : ''); }
        // Native URL generation preserves tag encoding, query parameters and the installation root.
        // POST requests are deliberately not redirected, so existing forms keep their payloads.
        header('Location: ' . qa_path($request, $_GET, null, QA_URL_FORMAT_NEAT), true, 301);
        qa_exit('w3_tag_url_redirect');
    }

    if (isset(w3_url_route_map()[$route]) && in_array($method, array('GET', 'HEAD'), true)) {
        $canonical = qa_path(qa_request(), $_GET, '', QA_URL_FORMAT_NEAT);
        $canonicalparts = explode('/', rawurldecode((string) parse_url($canonical, PHP_URL_PATH)));
        if ($publicparts && ($publicparts !== $canonicalparts || $byredirect)) {
            header('Location: ' . qa_path(qa_request(), $_GET, null, QA_URL_FORMAT_NEAT), true, 301);
            qa_exit('w3_turkish_url_redirect');
        }
    }
}
