<?php
/** Presentation only. Consume permission-filtered forms, never expose raw account fields. */
if (!defined('QA_VERSION')) { http_response_code(403); exit; }

function w3_profile_icon($name)
{
    $paths = array(
        'user' => '<circle cx="12" cy="7" r="3.5"></circle><path d="M5 21v-3a7 7 0 0 1 14 0v3"></path>',
        'calendar' => '<rect x="4" y="5" width="16" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path>',
        'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6z"></path>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 6 9 7 9-7"></path>',
        'login' => '<path d="M13 4h6v16h-6M3 12h12m-5-5 5 5-5 5"></path>',
        'clock' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 6v6l4 2"></path>',
        'pin' => '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0z"></path><circle cx="12" cy="10" r="2.5"></circle>',
        'trophy' => '<path d="M7 3h10v6a5 5 0 0 1-10 0V3ZM7 5H3v3a4 4 0 0 0 5 4M17 5h4v3a4 4 0 0 1-5 4M12 14v6M8 21h8"></path>',
        'question' => '<circle cx="12" cy="12" r="9"></circle><path d="M9 9a3 3 0 0 1 6 0c0 2-3 2-3 4M12 17h.01"></path>',
        'answer' => '<path d="M21 11a8 8 0 0 1-8 8H5l-3 3v-9a9 9 0 0 1 19-2Z"></path><path d="M7 10h.01M12 10h.01M17 10h.01"></path>',
        'comment' => '<path d="M4 4h16v13H9l-5 4V4Z"></path><path d="M8 9h.01M12 9h.01M16 9h.01"></path>',
        'activity' => '<path d="M2 12h5l3-8 4 16 3-8h5"></path>',
        'chart' => '<path d="M5 21V11M12 21V3M19 21V7" stroke-width="4"></path>',
        'up' => '<path d="M7 11v10H3V11h4Zm0 0 5-8c2 0 2 2 1 6h6a2 2 0 0 1 2 2l-2 8a2 2 0 0 1-2 2H7"></path>',
        'down' => '<path d="M7 13V3H3v10h4Zm0 0 5 8c2 0 2-2 1-6h6a2 2 0 0 0 2-2l-2-8a2 2 0 0 0-2-2H7"></path>',
        'crown' => '<path d="m3 6 4 4 5-7 5 7 4-4-3 13H6L3 6Z"></path>',
        'edit' => '<path d="m15 4 5 5M4 20l4-1L21 6l-5-5L3 14l1 6Z"></path>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"></path>',
    );
    $path = isset($paths[$name]) ? $paths[$name] : $paths['user'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

function w3_profile_prepare(&$content)
{
    if (empty($content['form_profile']) || empty($content['form_activity']) || !empty($content['error'])) { return; }
    $content['w3_profile_redesign'] = true;
    $icons = array('duration' => 'calendar', 'level' => 'shield', 'permits' => 'shield', 'email' => 'mail', 'lastlogin' => 'login', 'lastwrite' => 'clock', 'points' => 'trophy', 'questions' => 'question', 'answers' => 'answer', 'comments' => 'comment', 'votedon' => 'up', 'votegave' => 'up', 'votegot' => 'down', 'bonus' => 'star');
    foreach (array('form_profile', 'form_activity') as $part) {
        foreach ($content[$part]['fields'] as $key => &$field) {
            if (is_array($field) && !empty($field['label'])) {
                $icon = isset($icons[$key]) ? $icons[$key] : 'user';
                $field['label'] = '<span class="w3-profile-field-label"><span class="w3-profile-field-icon">' . w3_profile_icon($icon) . '</span><span>' . $field['label'] . '</span></span>';
            }
        }
        unset($field);
    }
}

function w3_profile_stat_text($content, $field, $class)
{
    $html = isset($content['form_activity']['fields'][$field]['value']) ? $content['form_activity']['fields'][$field]['value'] : '';
    if (preg_match('~<span\b[^>]*class=["\']' . preg_quote($class, '~') . '["\'][^>]*>(.*?)</span>~s', $html, $match)) {
        return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES, 'UTF-8'));
    }
    return null;
}

function w3_profile_hero_html($content, $favoritehtml = '')
{
    $handle = (string) qa_request_part(1);
    $fields = $content['form_profile']['fields'];
    $avatar = !empty($fields['avatar']['html']) ? w3_safe_avatar_html($fields['avatar']['html']) : '<span class="w3-profile-initial">' . qa_html(qa_substr($handle, 0, 1)) . '</span>';
    $levelvalue = isset($fields['level']['value']) ? $fields['level']['value'] : '';
    // The first prepared line is the public role; omit private-message/category links here.
    $levelparts = preg_split('~<a\b|<br\s*/?>~i', $levelvalue);
    $role = trim(html_entity_decode(strip_tags($levelparts[0]), ENT_QUOTES, 'UTF-8'));
    $duration = isset($fields['duration']['value']) ? $fields['duration']['value'] : '';
    $html = '<section class="w3-profile-hero" aria-label="Üye özeti"><div class="w3-profile-hero-avatar">' . $avatar . '</div><div class="w3-profile-hero-identity"><div class="w3-profile-name">' . $favoritehtml . '<h1>' . qa_html($handle) . '</h1></div><p>W3Uzman topluluk profili</p>';
    if ($role !== '') { $html .= '<span class="w3-profile-role">' . w3_profile_icon('shield') . qa_html($role) . '</span>'; }
    $html .= '<div class="w3-profile-hero-meta">';
    if ($duration !== '') { $html .= '<span>' . w3_profile_icon('calendar') . $duration . '</span>'; }
    $html .= '<span>' . w3_profile_icon('pin') . 'W3Uzman kullanıcısı</span></div></div><div class="w3-profile-metrics" aria-label="Topluluk katkıları">';
    $metrics = array(array('points', 'qa-uf-user-points', 'Puan', 'trophy'), array('answers', 'qa-uf-user-a-posts', 'Cevap', 'answer'), array('questions', 'qa-uf-user-q-posts', 'Soru', 'question'), array('comments', 'qa-uf-user-c-posts', 'Yorum', 'comment'));
    foreach ($metrics as $metric) {
        $value = w3_profile_stat_text($content, $metric[0], $metric[1]);
        if ($value === null) { continue; }
        $rank = $metric[0] === 'points' ? w3_profile_stat_text($content, 'points', 'qa-uf-user-rank') : null;
        $html .= '<div class="w3-profile-metric w3-profile-metric-' . $metric[0] . '">' . w3_profile_icon($metric[3]) . '<strong>' . qa_html($value) . '</strong><span>' . $metric[2] . ($rank !== null ? '<small>#' . qa_html($rank) . '</small>' : '') . '</span></div>';
    }
    return $html . '</div></section>';
}

function w3_profile_navigation($navigation)
{
    $icons = array('profile' => 'user', 'wall' => 'comment', 'activity' => 'activity', 'questions' => 'question', 'answers' => 'answer', 'account' => 'edit', 'favorites' => 'star', 'messages' => 'mail');
    foreach ($navigation as $key => &$item) {
        if ($key === 'profile') { $item['label'] = 'Profil'; }
        if (isset($icons[$key])) { $item['label'] = w3_profile_icon($icons[$key]) . '<span>' . $item['label'] . '</span>'; }
    }
    unset($item);
    return $navigation;
}
