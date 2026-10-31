<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

/** The same presentation for the news sidebar and Q2A's Activity Count widget. */
function w3_site_stats_html()
{
    static $instance = 0;
    $instance++;
    $headingid = 'w3-site-stats-title' . ($instance > 1 ? '-' . $instance : '');
    $html = '<section class="w3-site-stats-card" aria-labelledby="' . $headingid . '"><header><h2 id="' . $headingid . '">' . w3_site_stats_icon('stats') . 'Site İstatistikleri</h2></header><dl class="w3-site-stats-grid">';
    foreach (array(
        array('news', 'cache_qcount', 'Haber', 'blue'),
        array('comments', 'cache_acount', 'Yorum', 'green'),
        array('users', 'cache_ccount', 'Yanıt', 'amber'),
        array('users', 'cache_userpointscount', 'Kullanıcı', 'pink'),
    ) as $stat) {
        $count = max(0, (int) qa_opt($stat[1]));
        $number = $count >= 1000 ? rtrim(rtrim(number_format($count / 1000, 1, '.', ''), '0'), '.') . 'K' : (string) $count;
        $html .= '<div class="w3-site-stat-' . $stat[3] . '" title="' . qa_html(number_format($count, 0, ',', '.') . ' ' . $stat[2]) . '"><dt>' . w3_site_stats_icon($stat[0]) . '<span>' . $stat[2] . '</span></dt><dd>' . qa_html($number) . '</dd></div>';
    }
    return $html . '</dl></section>';
}

function w3_site_stats_icon($name)
{
    $shapes = array(
        'stats' => '<path d="M5 20v-9m7 9V4m7 16V8"></path>',
        'news' => '<rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 7h6M9 11h6M9 15h4"></path>',
        'comments' => '<path d="M21 11a9 9 0 0 1-9 9H4l-2 2 1-7a9 9 0 1 1 18-4Z"></path>',
        'users' => '<circle cx="9" cy="7" r="3"></circle><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m2 5a5 5 0 0 1 3 6"></path>',
    );
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $shapes[$name] . '</svg>';
}
