<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
require_once __DIR__ . '/w3-site-stats.php';

function w3_news_icon($name)
{
    $icons = array(
        'home' => '<path d="m3 10 9-7 9 7v10H7V10h10v10M10 20v-6h4v6"/>',
        'recent' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/>',
        'hot' => '<path d="M13 3c1 5 6 6 6 12a7 7 0 0 1-14 0c0-3 2-6 5-8-1 4 0 5 1 5 2-2 3-5 2-9Z"/>',
        'votes' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/>',
        'answers' => '<path d="M21 11a9 9 0 0 1-9 9H4l-2 2 1-7a9 9 0 1 1 18-4Z"/>',
        'views' => '<path d="m3 17 6-6 4 4 8-10M15 5h6v6"/>',
        'users' => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m2 5a5 5 0 0 1 3 6"/>',
        'stats' => '<path d="M5 20v-9m7 9V4m7 16V8"/>',
        'rss' => '<circle cx="5" cy="19" r="1"/><path d="M4 11a9 9 0 0 1 9 9M4 4a16 16 0 0 1 16 16"/>',
        'arrow' => '<path d="m9 5 7 7-7 7"/>',
    );
    $shapes = isset($icons[$name]) ? $icons[$name] : $icons['recent'];
    // The site's HTML minifier strips self-closing slashes; explicit SVG end tags are required.
    $shapes = preg_replace('~<(path|circle|rect)([^>]*)/>~', '<$1$2></$1>', $shapes);
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $shapes . '</svg>';
}

function w3_news_hero_art()
{
    $art = '<svg class="w3-news-hero-art" viewBox="0 0 280 200" fill="none" aria-hidden="true"><circle cx="214" cy="105" r="112" fill="#fff2c3" opacity=".55"/><path d="M24 188C5 123 47 47 111 32" stroke="#f4d575" opacity=".65"/><circle cx="111" cy="32" r="3" fill="#ffd365"/><g transform="translate(65 34) rotate(8 87 70)"><rect x="14" y="19" width="155" height="137" rx="12" fill="#f6d97e" opacity=".25"/><rect width="155" height="137" rx="12" fill="white"/><rect x="20" y="20" width="114" height="11" rx="4" fill="#ffdb79"/><path d="M22 51h52M22 67h52M22 83h43M22 99h52M22 115h111" stroke="#ffda76" stroke-width="7" stroke-linecap="round"/><rect x="86" y="45" width="48" height="55" rx="6" fill="#ffe8a6"/><circle cx="101" cy="58" r="5" fill="white"/><path d="m89 95 16-19 10 9 10-16 10 26" fill="white" opacity=".9"/></g><circle cx="241" cy="24" r="5" fill="#ffda75"/><circle cx="38" cy="94" r="4" fill="#ffde8c"/><circle cx="248" cy="159" r="5" fill="#ffda75"/></svg>';
    return preg_replace('~<(path|circle|rect)([^>]*)/>~', '<$1$2></$1>', $art);
}

/** Full product names in headings, short names only in Google topic chips. */
function w3_news_is_google_archive($content)
{
    $root = isset($content['navigation']['cat']['google']) ? $content['navigation']['cat']['google'] : null;
    $ids = isset($content['categoryids']) ? $content['categoryids'] : array();
    return $root && $ids && (int) reset($ids) === (int) $root['categoryid'];
}

function w3_news_prepare_google_archive(&$content)
{
    if (!w3_news_is_google_archive($content) || count($content['categoryids']) !== 2) { return; }
    $id = (int) end($content['categoryids']);
    foreach ($content['navigation']['cat']['google']['subnav'] as $item) {
        if ((int) $item['categoryid'] === $id) {
            $name = html_entity_decode($item['label'], ENT_QUOTES, 'UTF-8');
            $content['title'] = qa_html($name . ' Haberleri');
            return;
        }
    }
}

/** Reuse Q2A's real category tree and URLs; do not turn categories into tag searches. */
function w3_news_category_topics_html($content)
{
    $path = isset($content['categoryids']) && is_array($content['categoryids']) ? $content['categoryids'] : array();
    $level = isset($content['navigation']['cat']) && is_array($content['navigation']['cat']) ? $content['navigation']['cat'] : array();
    if (!$path || !$level) { return ''; }

    $current = null; $parent = null; $siblings = array();
    foreach ($path as $id) {
        $found = null;
        foreach ($level as $item) {
            if (isset($item['categoryid']) && (int) $item['categoryid'] === (int) $id) { $found = $item; break; }
        }
        if ($found === null) { return ''; }
        $parent = $current;
        $siblings = $level;
        $current = $found;
        $level = isset($found['subnav']) && is_array($found['subnav']) ? $found['subnav'] : array();
    }
    $children = !empty($level);
    $topics = $children ? $level : ($parent !== null ? $siblings : array());

    // Domain tools are real tool links, not empty archive categories.
    static $domainRootId = null;
    if ($domainRootId === null) {
        $domainRootId = (int) qa_db_read_one_value(qa_db_query_sub(
            'SELECT categoryid FROM ^categories WHERE parentid IS NULL AND tags=$', 'domain'
        ), true);
    }
    $isDomain = $domainRootId && (int) reset($path) === $domainRootId;
    if ($isDomain) {
        $topics = array_merge(array(
            array('categoryid' => 0, 'label' => 'Domain Sorgulama', 'url' => qa_path_html('domain-sorgulama')),
            array('categoryid' => 0, 'label' => 'Whois Sorgulama', 'url' => qa_path_html('whois-sorgulama')),
            array('categoryid' => 0, 'label' => 'Domain Öneri Aracı', 'url' => qa_path_html('domain-oneri')),
            array('categoryid' => $domainRootId, 'label' => 'Domain Haberleri', 'url' => qa_path_html('questions/domain'), 'selected' => count($path) === 1),
        ), $topics);
    }
    // Mobile reuses Android and Google Play archives already owned by Google.
    $mobileRoot = isset($content['navigation']['cat']['mobil']) ? $content['navigation']['cat']['mobil'] : null;
    if ($mobileRoot && (int) reset($path) === (int) $mobileRoot['categoryid']) {
        static $mobileRelated = null;
        if ($mobileRelated === null) {
            $mobileRelated = qa_db_read_all_assoc(qa_db_query_sub(
                "SELECT c.categoryid,c.tags,c.backpath FROM ^categories c JOIN ^categories p ON c.parentid=p.categoryid WHERE p.parentid IS NULL AND p.tags='google' AND c.tags IN ('android','play')"
            ));
        }
        $topics = array_values($topics);
        foreach (array('android' => array('Android', 0), 'play' => array('Google Play', 4)) as $slug => $display) {
            foreach ($mobileRelated as $related) {
                if ($related['tags'] !== $slug || in_array((int) $related['categoryid'], array_map('intval', array_column($topics, 'categoryid')), true)) { continue; }
                $link = array('categoryid' => (int) $related['categoryid'], 'label' => $display[0],
                    'url' => qa_path_html('questions/' . implode('/', array_reverse(explode('/', $related['backpath'])))), 'selected' => false);
                array_splice($topics, $display[1], 0, array($link));
            }
        }
    }
    // Desktop links to the existing application archive without moving or copying it.
    $desktopRoot = isset($content['navigation']['cat']['masaustu']) ? $content['navigation']['cat']['masaustu'] : null;
    if ($desktopRoot && (int) reset($path) === (int) $desktopRoot['categoryid']) {
        static $desktopRelated = null;
        if ($desktopRelated === null) {
            $desktopRelated = qa_db_read_all_assoc(qa_db_query_sub(
                "SELECT c.categoryid,c.title,c.backpath FROM ^categories c JOIN ^categories p ON c.parentid=p.categoryid WHERE p.parentid IS NULL AND p.tags='uygulamalar' AND c.tags='masaustu-uygulamalar'"
            ));
        }
        foreach ($desktopRelated as $related) {
            if (in_array((int) $related['categoryid'], array_map('intval', array_column($topics, 'categoryid')), true)) { continue; }
            $topics[] = array('categoryid' => (int) $related['categoryid'], 'label' => $related['title'],
                'url' => qa_path_html('questions/' . implode('/', array_reverse(explode('/', $related['backpath'])))), 'selected' => false);
        }
    }
    if (!$topics) { return ''; }

    if (w3_news_is_google_archive($content)) {
        foreach ($topics as &$item) {
            $item['label'] = preg_replace('/^Google\s+/u', '', $item['label']);
        }
        unset($item);
        // Reuse real archives in other sections, not duplicate Google categories.
        static $googleRelated = null;
        if ($googleRelated === null) {
            $googleRelated = qa_db_read_all_assoc(qa_db_query_sub(
                "SELECT c.categoryid,c.tags,c.backpath FROM ^categories c JOIN ^categories p ON c.parentid=p.categoryid WHERE (p.tags='yapay-zeka-ai' AND c.tags='gemini') OR (p.tags='sosyal-medya' AND c.tags='youtube') OR (p.tags='hosting-sunucu' AND c.tags='google-cloud')"
            ));
        }
        foreach (array('gemini' => 'Gemini', 'youtube' => 'YouTube', 'google-cloud' => 'Cloud') as $slug => $label) {
            foreach ($googleRelated as $related) {
                if ($related['tags'] !== $slug) { continue; }
                $topics[] = array('categoryid' => (int) $related['categoryid'], 'label' => $label,
                    'url' => qa_path_html('questions/' . implode('/', array_reverse(explode('/', $related['backpath'])))), 'selected' => false);
            }
        }
    }

    // Q2A prepares HTML-escaped navigation fields. Decode and escape once for this renderer.
    $text = function ($value) { return qa_html(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8')); };
    $html = '<nav class="w3-news-topics" aria-label="' . ($isDomain ? 'Alan adı araçları ve konuları' : 'Alt kategoriler') . '"><ul>';
    $count = 0;
    foreach ($topics as $item) {
        if (empty($item['url']) || !isset($item['label'], $item['categoryid'])) { continue; }
        $selected = isset($item['selected']) ? $item['selected'] : (!$children && (int) $item['categoryid'] === (int) $current['categoryid']);
        $html .= '<li><a href="' . $text($item['url']) . '"' . ($selected ? ' aria-current="page"' : '') . '><span class="w3-news-topic-hash" aria-hidden="true">#</span>' . $text($item['label']) . '</a></li>';
        $count++;
    }
    return $count ? $html . '</ul></nav>' : '';
}

function w3_news_compact_count($number)
{
    $number = max(0, (int) $number);
    return $number >= 1000 ? rtrim(rtrim(number_format($number / 1000, 1, '.', ''), '0'), '.') . 'K' : (string) $number;
}

function w3_news_sidebar_html()
{
    require_once QA_INCLUDE_DIR . 'db/selects.php';
    require_once QA_INCLUDE_DIR . 'util/string.php';
    // Only public contributions count; private events, IPs and email addresses are never queried.
    $active = QA_FINAL_EXTERNAL_USERS ? array() : qa_db_read_all_assoc(qa_db_query_sub(
        "SELECT u.handle, COUNT(*) AS activity FROM ^posts p JOIN ^users u ON u.userid=p.userid WHERE p.type IN ('Q','A','C') AND p.created>=DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND (u.flags & #)=0 GROUP BY u.userid,u.handle ORDER BY activity DESC,u.handle LIMIT 6",
        QA_USER_FLAGS_USER_BLOCKED
    ));
    $membersurl = qa_path_html('users');
    $html = '<section class="w3-news-side-card w3-news-active" aria-labelledby="w3-active-title"><header><h2 id="w3-active-title">' . w3_news_icon('users') . 'Bu Hafta En Aktif Üyeler</h2><a href="' . $membersurl . '">Tümünü Gör <span aria-hidden="true">→</span></a></header>';
    if ($active) {
        $html .= '<ul class="w3-news-avatar-list">';
        foreach ($active as $user) {
            $name = (string) $user['handle'];
            $html .= '<li><a href="' . qa_path_html('user/' . $name) . '" title="' . qa_html($name) . '" aria-label="' . qa_html($name . ' profilini görüntüle') . '">' . qa_html(qa_substr($name, 0, 2)) . '</a></li>';
        }
        $html .= '</ul>';
    } else {
        $html .= '<p class="w3-news-empty">Bu hafta henüz herkese açık bir etkinlik yok. <a href="' . $membersurl . '">Topluluğu keşfet →</a></p>';
    }
    $html .= '</section>' . w3_site_stats_html();
    $recent = qa_db_select_with_pending(qa_db_qs_selectspec(qa_get_logged_in_userid(), 'created', 0, null, null, false, true, 3));
    $html .= '<section class="w3-news-side-card" aria-labelledby="w3-latest-title"><header><h2 id="w3-latest-title">' . w3_news_icon('rss') . 'Son Haberler</h2><a href="' . qa_path_html('questions') . '">Tümünü Gör <span aria-hidden="true">→</span></a></header><ul class="w3-news-latest">';
    foreach ($recent as $post) {
        $cover = w3_category_cover_url(isset($post['categoryid']) ? $post['categoryid'] : null, $post['title']);
        $image = $cover;
        $content = isset($post['content']) ? $post['content'] : '';
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $match) || preg_match('/!\[[^\]]*\]\(([^\s\)]+)/', $content, $match)) {
            $candidate = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
            if (preg_match('~^(?:https?://|/|\./|\?qa=|qa-blob\.)~i', $candidate)) { $image = $candidate; }
        }
        $image = w3_site_image_url($image);
        $url = qa_q_path_html($post['postid'], $post['title']);
        $date = date('d.m.Y', (int) $post['created']);
        $html .= '<li><a href="' . $url . '"><img src="' . qa_html($image) . '" alt="" loading="lazy" decoding="async" onerror="' . qa_html('this.onerror=null;this.src=' . qa_js($cover) . ';') . '"><span><strong>' . qa_html($post['title']) . '</strong><time datetime="' . date('Y-m-d', (int) $post['created']) . '">' . $date . '</time></span></a></li>';
    }
    if (!$recent) { $html .= '<li class="w3-news-empty">Henüz yayınlanmış haber bulunmuyor.</li>'; }
    return $html . '</ul></section>';
}
