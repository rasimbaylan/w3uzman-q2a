<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

function w3_article_icon($name)
{
    $icons = array(
        'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"></rect><path d="M15 8V3H3v12h5"></path>',
        'bookmark' => '<path d="M6 3h12v18l-6-4-6 4V3Z"></path>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="m3 6 9 7 9-7"></path>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"></path>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"></path>',
        'clock' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
        'user' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 22v-3a8 8 0 0 1 16 0v3"></path>',
        'facebook' => '<path d="M14 21v-8h3l.5-4H14V7c0-2 1-2 4-2V2h-3c-4 0-5 2-5 5v2H7v4h3v8"></path>',
        'x' => '<path d="m4 3 16 18h-4L1 3h4Zm16 0L4 21"></path>',
        'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="3"></rect><path d="M7 10v7m0-10v.1M11 17v-7m0 3c0-4 6-4 6 0v4"></path>',
    );
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . (isset($icons[$name]) ? $icons[$name] : $icons['star']) . '</svg>';
}

function w3_article_image_url($value)
{
    if (!is_string($value)) { return ''; }
    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    if (preg_match('/[\x00-\x1f\x7f]/', $value)) { return ''; }
    $value = trim($value);
    if ($value === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $value) || substr($value, 0, 2) === '//') { return ''; }
    if (!preg_match('~^(?:https?://|/(?!/)|\./|\.\./|\?qa=|qa-blob\.)~i', $value)) { return ''; }
    return w3_site_image_url($value);
}

/** Style clearly title-cased legacy paragraphs without changing their text or heading semantics. */
function w3_article_legacy_sections($html)
{
    if (preg_match('~<h[2-6]\b~i', $html)) { return $html; }
    preg_match_all('~<p\b([^>]*)>([^<]+)</p>~iu', $html, $paragraphs, PREG_OFFSET_CAPTURE);
    if (count($paragraphs[0]) < 6) { return $html; }
    $titles = array();
    foreach ($paragraphs[0] as $i => $match) {
        if ($i === 0 || !isset($paragraphs[2][$i + 1]) || trim($paragraphs[1][$i][0]) !== '') { continue; }
        $text = trim(html_entity_decode($paragraphs[2][$i][0], ENT_QUOTES, 'UTF-8'));
        $next = trim(html_entity_decode($paragraphs[2][$i + 1][0], ENT_QUOTES, 'UTF-8'));
        $length = preg_match_all('/./us', $text, $unused);
        if ($length < 18 || $length > 100 || preg_match('/[.!?:;…]$/u', $text) || preg_match_all('/./us', $next, $unused) < 180) { continue; }
        preg_match_all('/\p{L}[\p{L}\p{N}.]*/u', $text, $words);
        if (count($words[0]) < 3 || count($words[0]) > 14) { continue; }
        $capital = 0; $total = 0;
        foreach ($words[0] as $word) {
            if (in_array($word, array('ve', 'ile', 'için', 'veya', 'bir', 'de', 'da'), true)) { continue; }
            $total++; if (preg_match('/^\p{Lu}/u', $word)) { $capital++; }
        }
        if ($total >= 3 && $capital / $total >= .8) { $titles[] = $match; }
    }
    if (count($titles) < 2) { return $html; }
    foreach (array_reverse($titles) as $title) {
        $replacement = preg_replace('~^<p\b[^>]*>~i', '<p class="w3-article-legacy-heading">', $title[0]);
        $html = substr_replace($html, $replacement, $title[1], strlen($title[0]));
    }
    return $html;
}

/** Presentation only: never write back to posts, users or metadata. */
function w3_article_prepare(&$content)
{
    $q = isset($content['q_view']) ? $content['q_view'] : array();
    if (empty($q['raw']) || !isset($q['content']) || !isset($q['raw']['type']) || $q['raw']['type'] !== 'Q' || !empty($q['hidden']) || !empty($q['queued']) || isset($content['form_q_edit'])) { return; }
    require_once QA_INCLUDE_DIR . 'db/metas.php';
    $raw = $q['raw']; $html = (string) $q['content'];
    $text = trim(html_entity_decode(strip_tags(preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', $html)), ENT_QUOTES, 'UTF-8'));
    $words = preg_match_all('/[\p{L}\p{N}]+/u', $text, $unused);
    $data = array('minutes' => max(1, (int) ceil($words / 200)), 'hero' => '', 'hero_alt' => isset($raw['title']) ? $raw['title'] : '', 'lead' => '', 'body' => $html);
    $data['hero'] = w3_article_image_url(qa_db_postmeta_get((int) $raw['postid'], 'w3_cover_image'));
    // Body images are the editor's source of truth. An old cover meta must not add a
    // second, possibly broken image above an article that already has its own image.
    if (preg_match('~<img\b[^>]*>~is', $html, $firstImage, PREG_OFFSET_CAPTURE)) {
        $data['hero'] = '';
        // Move only the actual FIRST image, and only when its paragraph has no link,
        // caption or other content. Otherwise keep the authored placement intact.
        if (preg_match('~<p\b[^>]*>\s*(<img\b[^>]*>)\s*</p>~is', $html, $image, PREG_OFFSET_CAPTURE)
            && $image[1][1] === $firstImage[0][1]
            && preg_match('~\ssrc\s*=\s*(["\'])(.*?)\1~is', $image[1][0], $source)) {
            $candidate = w3_article_image_url($source[2]);
            if ($candidate !== '') {
                $data['hero'] = $candidate;
                if (preg_match('~\salt\s*=\s*(["\'])(.*?)\1~is', $image[1][0], $alt)) {
                    $description = trim(html_entity_decode($alt[2], ENT_QUOTES, 'UTF-8'));
                    if ($description !== '' && !in_array(strtolower($description), array('image', 'resim', 'foto'))) { $data['hero_alt'] = $description; }
                }
                $data['body'] = substr_replace($data['body'], '', $image[0][1], strlen($image[0][0]));
            }
        }
    }
    // A short actual opening paragraph can become the deck. Do not invent a summary or remove long text.
    if (preg_match('~<p\b[^>]*>(.*?)</p>~is', $data['body'], $opening)) {
        $lead = trim(html_entity_decode(strip_tags($opening[1]), ENT_QUOTES, 'UTF-8'));
        $title = isset($raw['title']) ? (string) $raw['title'] : '';
        $length = preg_match_all('/./us', $lead, $unused);
        if ($length >= 60 && $length <= 340 && !preg_match('~<(?:img|a|iframe|video|audio|code)\b~i', $opening[1]) && ($title === '' || stripos($lead, $title) !== 0) && !preg_match('~^\s*<(?:strong|b)\b[^>]*>.*?</(?:strong|b)>\s*$~is', $opening[1])) {
            $data['lead'] = $lead;
            $pos = strpos($data['body'], $opening[0]);
            if ($pos !== false) { $data['body'] = substr_replace($data['body'], '', $pos, strlen($opening[0])); }
        }
    }
    // The page owns its H1; body-level H1s keep their text but become section H2s.
    $data['body'] = preg_replace('~<(\/?)h1\b~i', '<$1h2', $data['body']);
    $data['body'] = w3_article_legacy_sections($data['body']);
    $content['w3_article_page'] = true;
    $content['w3_article'] = $data;
    if (!empty($content['widgets']['full']['top'])) {
        foreach ($content['widgets']['full']['top'] as $key => $widget) {
            if ($widget instanceof qa_ask_box) { unset($content['widgets']['full']['top'][$key]); }
        }
    }
}

function w3_article_author($q)
{
    $raw = isset($q['raw']) ? $q['raw'] : array();
    if (!empty($raw['handle'])) {
        return '<a href="' . qa_path_html('user/' . $raw['handle']) . '">' . qa_html($raw['handle']) . '</a>';
    }
    return qa_html(!empty($raw['name']) ? $raw['name'] : 'Anonim');
}

function w3_article_avatar($q)
{
    return !empty($q['avatar']) ? w3_safe_avatar_html($q['avatar']) : '<span class="w3-article-avatar-placeholder">' . w3_article_icon('user') . '</span>';
}

function w3_article_toolbar($q, $favorite = null)
{
    $raw = $q['raw'];
    $html = '<div class="w3-article-tools" role="group" aria-label="Haber işlemleri">';
    // Use the native account favourite for signed-in readers, not two different save controls.
    if (is_array($favorite)) {
        $html .= '<form ' . $favorite['form_tags'] . '><span class="qa-favoriting" ' . (isset($favorite['favorite_tags']) ? $favorite['favorite_tags'] : '') . '>';
        foreach (array('favorite_add_tags' => 'qa-favorite', 'favorite_remove_tags' => 'qa-unfavorite') as $key => $class) {
            if (isset($favorite[$key])) { $html .= '<input ' . $favorite[$key] . ' type="submit" value="" class="' . $class . '-button">'; }
        }
        $html .= '</span>';
        foreach (isset($favorite['form_hidden']) ? $favorite['form_hidden'] : array() as $name => $value) {
            $html .= '<input type="hidden" name="' . qa_html($name) . '" value="' . qa_html($value) . '">';
        }
        $html .= '</form>';
    } else {
        $html .= '<button class="w3-article-save" type="button" aria-pressed="false" aria-label="Bu haberi bu tarayıcıya kaydet" title="Bu tarayıcıya kaydet" data-article-path="' . qa_html('/' . ltrim(qa_q_request($raw['postid'], $raw['title']), '/')) . '" disabled>' . w3_article_icon('bookmark') . '</button>';
    }
    // Copy and social actions belong to the single share strip at the article's end.
    return $html . '</div>';
}

function w3_article_header_html($content)
{
    $q = $content['q_view']; $raw = $q['raw']; $data = $content['w3_article'];
    $category = isset($q['where']['data']) ? $q['where']['data'] : '';
    $html = '<header class="w3-article-heading"><nav class="w3-article-crumb" aria-label="İçerik yolu"><a href="' . qa_path_html('') . '">Ana sayfa</a><span aria-hidden="true">›</span><a href="' . qa_path_html('questions') . '">Haberler</a>' . ($category !== '' ? '<span aria-hidden="true">›</span>' . $category : '') . '</nav><div class="w3-article-kicker">' . ($category !== '' ? '<span class="w3-article-category">' . $category . '</span>' : '<span class="w3-article-category">Haber</span>') . '<span>' . w3_article_icon('clock') . 'Yaklaşık ' . (int) $data['minutes'] . ' dk okuma</span></div>';
    $html .= '<h1>' . $q['title'] . '</h1>';
    if ($data['lead'] !== '') { $html .= '<p class="w3-article-deck">' . qa_html($data['lead']) . '</p>'; }
    $html .= '<p id="w3-article-status" class="w3-article-reader" role="status" aria-live="polite" aria-atomic="true"></p></header>';
    return $html;
}

function w3_article_byline_html($q)
{
    $raw = $q['raw'];
    $created = isset($raw['created']) && is_numeric($raw['created']) ? (int) $raw['created'] : 0;
    $updated = isset($raw['updated']) && is_numeric($raw['updated']) ? (int) $raw['updated'] : 0;
    $stamp = max($created, $updated);
    $history = $created > 0 ? 'Yayın: ' . date('d.m.Y H:i', $created) : '';
    if ($updated > $created) { $history .= ($history !== '' ? ' · ' : '') . 'Güncelleme: ' . date('d.m.Y H:i', $updated); }
    $html = '<div class="w3-article-byline"><div class="w3-article-author-mini"><span class="w3-article-avatar">' . w3_article_avatar($q) . '</span><strong>' . w3_article_author($q) . '</strong></div>';
    if ($stamp > 0) {
        $label = $updated > $created ? 'Son güncelleme' : 'Yayın tarihi';
        $html .= '<span class="w3-article-dates" title="' . qa_html($history) . '"><time datetime="' . date('c', $stamp) . '" aria-label="' . $label . ': ' . date('d.m.Y', $stamp) . '">' . date('d.m.Y', $stamp) . '</time></span>';
    }
    // Preserve both dates as machine-readable metadata, but show a single date to readers.
    if ($created > 0) { $html .= '<meta itemprop="datePublished" content="' . date('c', $created) . '">'; }
    if ($updated > $created) { $html .= '<meta itemprop="dateModified" content="' . date('c', $updated) . '">'; }
    return $html . '</div>';
}

/** Kept callable for older theme files during incremental uploads; the current template does not render this duplicate card. */
function w3_article_author_card($q)
{
    $note = !empty($q['raw']['handle']) ? 'Bu içeriğin yazarı. Diğer paylaşımlarını ve topluluk profilini inceleyebilirsiniz.' : 'Bu içeriği paylaşan yazar.';
    return '<section class="w3-article-author-card" aria-label="Yazar hakkında"><span class="w3-article-avatar">' . w3_article_avatar($q) . '</span><div><span class="w3-article-overline">YAZAR</span><h2>' . w3_article_author($q) . '</h2><p>' . $note . '</p></div></section>';
}

/** Render only the actions Q2A already authorised; layout must not depend on editor state or JS. */
function w3_article_actions_html($form)
{
    if (empty($form['buttons']) || !is_array($form['buttons'])) { return ''; }
    $primary = ''; $others = '';
    foreach ($form['buttons'] as $key => $button) {
        if (empty($button) || !is_array($button)) { continue; }
        $isPrimary = $key === 'answer';
        $caption = $isPrimary ? 'Yorum yaz' : (!empty($button['popup']) ? $button['popup'] : (isset($button['label']) ? $button['label'] : 'İşlem'));
        $caption = qa_html(strip_tags(html_entity_decode($caption, ENT_QUOTES, 'UTF-8')));
        $tags = isset($button['tags']) ? $button['tags'] : '';
        $popup = isset($button['popup']) ? $button['popup'] : '';
        $class = 'qa-form-light-button qa-form-light-button-' . qa_html($key) . ($isPrimary ? ' w3-article-comment-toggle' : '');
        $html = '<input ' . $tags . ' type="submit" value="' . $caption . '" title="' . $popup . '" class="' . $class . '"' . ($isPrimary ? ' aria-controls="anew"' : '') . '>';
        if (!empty($button['note'])) { $html .= '<span class="qa-form-light-note">' . $button['note'] . '</span>'; }
        if ($isPrimary) { $primary .= $html; } else { $others .= $html; }
    }
    if ($primary === '' && $others === '') { return ''; }
    $html = '<div class="qa-q-view-buttons w3-article-actions">' . $primary;
    if ($others !== '') { $html .= '<details class="w3-article-more-actions"><summary>Diğer işlemler</summary><div class="w3-article-more-menu">' . $others . '</div></details>'; }
    $html .= '</div>';
    foreach (isset($form['hidden']) && is_array($form['hidden']) ? $form['hidden'] : array() as $name => $value) {
        $html .= '<input type="hidden" name="' . qa_html($name) . '" value="' . qa_html($value) . '">';
    }
    return $html;
}

/** Fetch covers/content in two bounded queries for already public, authorised list IDs. */
function w3_article_story_rows($posts)
{
    $safe = array();
    foreach ($posts as $p) { if (isset($p['type']) && $p['type'] === 'Q' && empty($p['hidden']) && empty($p['queued'])) { $safe[(int) $p['postid']] = $p; if (count($safe) === 4) { break; } } }
    if (!$safe) { return array(); }
    $ids = array_keys($safe);
    $covers = qa_db_read_all_assoc(qa_db_query_sub('SELECT postid, content FROM ^postmetas WHERE title=$ AND postid IN (#)', 'w3_cover_image', $ids));
    foreach ($covers as $cover) { $safe[(int) $cover['postid']]['article_cover'] = w3_article_image_url($cover['content']); }
    foreach (qa_db_read_all_assoc(qa_db_query_sub('SELECT postid, content FROM ^posts WHERE type=$ AND postid IN (#)', 'Q', $ids)) as $row) {
        $id = (int) $row['postid'];
        if (empty($safe[$id]['article_cover']) && preg_match('~<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1~is', $row['content'], $match)) { $safe[$id]['article_cover'] = w3_article_image_url($match[2]); }
    }
    return $safe;
}

function w3_article_stories_html($title, $posts, $ranked = false, $all = '')
{
    $html = '<section class="w3-article-side-card"><header><h2>' . w3_article_icon($ranked ? 'star' : 'arrow') . qa_html($title) . '</h2>' . ($all !== '' ? '<a href="' . $all . '">Tümünü Gör <span aria-hidden="true">→</span></a>' : '') . '</header><ol class="w3-article-side-stories' . ($ranked ? ' is-ranked' : '') . '">';
    foreach (w3_article_story_rows($posts) as $p) {
        $fallback = w3_category_cover_url(isset($p['categoryid']) ? $p['categoryid'] : null, $p['title']);
        $image = !empty($p['article_cover']) ? $p['article_cover'] : $fallback;
        $html .= '<li><a href="' . qa_q_path_html($p['postid'], $p['title']) . '"><img src="' . qa_html($image) . '" data-article-fallback="' . qa_html($fallback) . '" alt="" loading="lazy" decoding="async" width="96" height="64"><span><strong>' . qa_html($p['title']) . '</strong><small><time datetime="' . date('Y-m-d', (int) $p['created']) . '">' . date('d.m.Y', (int) $p['created']) . '</time> · ' . number_format((int) $p['views'], 0, ',', '.') . ' okunma</small></span></a></li>';
    }
    return $html . '</ol></section>';
}

function w3_article_sidebar_html($content)
{
    require_once QA_INCLUDE_DIR . 'db/selects.php';
    $id = (int) $content['q_view']['raw']['postid']; $userid = qa_get_logged_in_userid();
    $related = qa_db_single_select(qa_db_related_qs_selectspec($userid, $id, 6));
    $recommended = array();
    foreach ($related as $post) {
        if ($post['type'] === 'Q' && empty($post['hidden']) && empty($post['queued']) && (int) $post['postid'] !== $id) { $recommended[(int) $post['postid']] = $post; }
        if (count($recommended) === 4) { break; }
    }
    if (count($recommended) < 4) {
        foreach (qa_db_select_with_pending(qa_db_qs_selectspec($userid, 'created', 0, null, null, false, false, 6)) as $post) {
            if ($post['type'] === 'Q' && empty($post['hidden']) && empty($post['queued']) && (int) $post['postid'] !== $id) { $recommended[(int) $post['postid']] = $post; }
            if (count($recommended) === 4) { break; }
        }
    }
    $html = '<section class="w3-article-side-card w3-article-ad" aria-label="Kare reklam alanı"><span class="w3-article-ad-label">REKLAM</span><div class="w3-article-ad-slot" data-w3-article-ad-slot="square"><span>Reklam alanı<small>300 × 300 · Kare banner</small></span></div></section>';
    $html .= '<section class="w3-article-side-card"><header><h2>' . w3_article_icon('star') . 'Yapay Zekâ Araçları</h2></header><ul class="w3-article-ai-tools">';
    foreach (array(
        array('ChatGPT', 'Sohbet, yazı ve fikir geliştirme', 'chatgpt', 'C', 'green'),
        array('Gemini', 'Google’ın yapay zekâ asistanı', 'gemini', 'G', 'blue'),
        array('Claude', 'Yazı, analiz ve kodlama', 'claude', 'C', 'orange'),
        array('Midjourney', 'Görsel üretimi ve keşif', 'midjourney', 'M', 'ink'),
        array('Copilot', 'Yapay zekâ destekli asistan', 'copilot', '</>', 'purple')
    ) as $tool) {
        $html .= '<li><a href="' . qa_path_html('questions/yapay-zeka-ai/' . $tool[2]) . '"><span class="w3-article-tool-icon is-' . $tool[4] . '" aria-hidden="true">' . qa_html($tool[3]) . '</span><span><strong>' . qa_html($tool[0]) . '</strong><small>' . qa_html($tool[1]) . '</small></span><span aria-hidden="true">→</span></a></li>';
    }
    $html .= '</ul></section>';
    if ($recommended) { $html .= w3_article_stories_html('Haberleri Keşfet', $recommended, false, qa_path_html('questions')); }
    $html .= w3_home_popular_categories_html(isset($content['categoryids']) ? $content['categoryids'] : array());
    return $html;
}
