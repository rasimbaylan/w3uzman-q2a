<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

/** Load covers/excerpts in one query for the IDs Q2A has already authorised for this list. */
function w3_prepare_listing_content(&$content)
{
    if (empty($content['q_list']['qs']) || !is_array($content['q_list']['qs'])) { return; }
    $ids = array();
    foreach ($content['q_list']['qs'] as $item) {
        if (!empty($item['raw']['postid']) && !array_key_exists('content', $item['raw'])) {
            $id = (int) $item['raw']['postid'];
            if ($id > 0) { $ids[$id] = $id; }
        }
    }
    if (!$ids) { return; }
    $rows = qa_db_read_all_assoc(qa_db_query_sub('SELECT postid, content FROM ^posts WHERE postid IN (#)', array_values($ids)));
    $byid = array();
    foreach ($rows as $row) { $byid[(int) $row['postid']] = (string) $row['content']; }
    foreach ($content['q_list']['qs'] as &$item) {
        $id = isset($item['raw']['postid']) ? (int) $item['raw']['postid'] : 0;
        if (isset($byid[$id]) && !array_key_exists('content', $item['raw'])) { $item['raw']['content'] = $byid[$id]; }
    }
    unset($item);
}

/** Render actual public stories instead of hard-coded demo headlines. */
function w3_home_popular_posts_html()
{
    require_once QA_INCLUDE_DIR . 'db/selects.php';
    $posts = qa_db_select_with_pending(qa_db_qs_selectspec(qa_get_logged_in_userid(), 'views', 0, null, null, false, false, 5));
    $html = '<section class="w3-home-popular" aria-labelledby="w3-popular-title"><h2 id="w3-popular-title"><span aria-hidden="true">&#128293;</span> Popüler Yazılar</h2><ol>';
    foreach ($posts as $post) {
        $html .= '<li><a href="' . qa_q_path_html($post['postid'], $post['title']) . '">' . qa_html($post['title']) . '<i aria-hidden="true">›</i></a></li>';
    }
    return $html . '</ol></section>';
}

/** Resolve first-party images to the local copy when available; never fetch the live site. */
function w3_site_image_url($url)
{
    $url = trim((string)$url);
    $parts = parse_url($url);
    // Reject explicit localhost/private-IP URLs in imported cover images.
    // Keep authored content unchanged in storage; use the existing neutral cover.
    if (is_array($parts) && !empty($parts['host'])) {
        $host = strtolower(rtrim($parts['host'], '.'));
        $address = trim($host, '[]');
        if ($host === 'localhost' || substr($host, -10) === '.localhost' || substr($host, -6) === '.local'
            || (filter_var($address, FILTER_VALIDATE_IP) && !filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
            return qa_path_to_root() . 'qa-theme/FlatBox/images/category-covers-v2/teknoloji.webp';
        }
    }
    if (!is_array($parts) || empty($parts['host']) || !in_array(strtolower($parts['host']), array('w3uzman.com', 'www.w3uzman.com'), true)) { return $url; }
    $path = isset($parts['path']) ? $parts['path'] : '/';
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
        if (isset($query['qa'], $query['qa_blobid']) && in_array($query['qa'], array('blob','image'), true) && is_string($query['qa_blobid']) && ctype_digit($query['qa_blobid']) && w3_display_blob_available($query['qa_blobid'])) {
            return qa_path_to_root() . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
    }
    $base = realpath(QA_BASE_DIR);
    $file = realpath(QA_BASE_DIR . ltrim(rawurldecode($path), '/'));
    if ($file && is_file($file) && preg_match('/\.(?:png|jpe?g|gif|webp|svg|avif|ico)$/i', $path) && strpos(str_replace('\\','/',$file), rtrim(str_replace('\\','/',$base),'/') . '/') === 0) {
        return qa_path_to_root() . ltrim($path,'/') . (!empty($parts['query']) ? '?' . $parts['query'] : '');
    }
    return preg_replace('~^http:~i', 'https:', $url);
}

function w3_display_blob_available($id)
{
    static $availability = array();
    if (!isset($availability[$id])) {
        $blob = qa_db_read_one_assoc(qa_db_query_sub('SELECT format, content IS NOT NULL AS in_database FROM ^blobs WHERE blobid=$', $id), true);
        $exists = $blob && !empty($blob['in_database']);
        if ($blob && !$exists && defined('QA_BLOBS_DIRECTORY')) {
            require_once QA_INCLUDE_DIR . 'app/blobs.php';
            $exists = is_file(qa_get_blob_filename($id, $blob['format']));
        }
        $availability[$id] = (bool) $exists;
    }
    return $availability[$id];
}

/** Avoid requesting missing imported avatar blobs; never change the stored user profile. */
function w3_safe_avatar_html($html)
{
    return preg_replace_callback('~(<img\b[^>]*\bsrc=)(["\'])([^"\']+)\2~i', function ($match) {
        $url = html_entity_decode($match[3], ENT_QUOTES, 'UTF-8');
        $parts = parse_url($url);
        if (empty($parts['query'])) { return $match[0]; }
        parse_str($parts['query'], $query);
        if (!isset($query['qa'], $query['qa_blobid']) || $query['qa'] !== 'image' || !is_string($query['qa_blobid']) || !ctype_digit($query['qa_blobid'])) { return $match[0]; }
        $id = $query['qa_blobid'];
        return w3_display_blob_available($id) ? $match[0] : $match[1] . $match[2] . qa_html(qa_path_to_root() . 'qa-theme/FlatBox/images/avatar-default.svg') . $match[2];
    }, $html);
}
