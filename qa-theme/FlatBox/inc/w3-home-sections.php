<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

function w3_home_section_icon($name)
{
    $icons = array(
        'featured' => '<path d="M13 3c1 5 6 6 6 12a7 7 0 0 1-14 0c0-3 2-6 5-8-1 4 0 5 1 5 2-2 3-5 2-9Z"></path>',
        'popular' => '<path d="M4 20V13h4v7m3 0V9h4v11m3 0V4h3v16M3 8l6-4 5 2 6-4"></path>',
        'new' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"></path>',
        'learn' => '<rect x="5" y="3" width="15" height="17" rx="2"></rect><path d="M8 7h8M8 11h8M5 17h15M8 20v2"></path>',
        'yapay-zeka-ai' => '<path d="M12 5a3 3 0 0 0-5-2 4 4 0 0 0-3 6 4 4 0 0 0 0 6 4 4 0 0 0 3 6 3 3 0 0 0 5-2V5Zm0 0a3 3 0 0 1 5-2 4 4 0 0 1 3 6 4 4 0 0 1 0 6 4 4 0 0 1-3 6 3 3 0 0 1-5-2M7 7v3l3 2m-6 3h3v3m10-11v3l-3 2m6 3h-3v3"></path>',
        'web-yazilim' => '<path d="m8 6-6 6 6 6m8-12 6 6-6 6M14 3l-4 18"></path>',
        'web-tasarim' => '<circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3c5 5 5 13 0 18-5-5-5-13 0-18Z"></path>',
        'sosyal-medya' => '<circle cx="7" cy="12" r="3"></circle><circle cx="18" cy="5" r="3"></circle><circle cx="18" cy="19" r="3"></circle><path d="m10 10 5-3m-5 7 5 3"></path>',
        'donanim' => '<rect x="5" y="5" width="14" height="14" rx="2"></rect><path d="M9 2v3m6-3v3M9 19v3m6-3v3M2 9h3m-3 6h3m14-6h3m-3 6h3M9 9h6v6H9Z"></path>',
        'mobil' => '<rect x="6" y="2" width="12" height="20" rx="2"></rect><path d="M10 5h4m-3 14h2"></path>',
        'domain' => '<circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3c5 5 5 13 0 18-5-5-5-13 0-18Z"></path>',
    );
    $shape = isset($icons[$name]) ? $icons[$name] : $icons['domain'];
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $shape . '</svg>';
}

function w3_home_shortcuts_html()
{
    $items = array(
        array('featured', '#w3-home-featured', 'Günün Öne Çıkanları', 'Editörün seçimi'),
        array('popular', qa_path_html('questions', array('sort' => 'views')), 'Popüler Haberler', 'En çok okunanlar'),
        array('new', qa_path_html('questions'), 'Yeni Eklenenler', 'Kaçırmayın'),
        array('learn', qa_path_html('dersler'), 'Öğren & Geliş', 'Dersler ve rehberler'),
    );
    $html = '<nav class="w3-home-shortcuts" aria-label="İçerikleri keşfet">';
    foreach ($items as $item) {
        $html .= '<a class="w3-home-shortcut--' . $item[0] . '" href="' . $item[1] . '"><span class="w3-home-shortcut-icon">' . w3_home_section_icon($item[0]) . '</span><span class="w3-home-shortcut-text"><strong>' . qa_html($item[2]) . '</strong><small>' . qa_html($item[3]) . '</small></span></a>';
    }
    return $html . '</nav>';
}

function w3_home_popular_categories_html($categoryids = array())
{
    static $categories = null;
    if ($categories === null) {
        $categories = array();
        foreach (qa_db_read_all_assoc(qa_db_query_sub('SELECT categoryid, title, tags, qcount, backpath FROM ^categories WHERE parentid IS NULL ORDER BY position, categoryid')) as $category) { $categories[$category['tags']] = $category; }
    }
    if (!$categories) { return ''; }
    $selectedids = array_map('intval', $categoryids);
    $groups = array(array('yapay-zeka-ai', 'yapay-zeka'), array('web-yazilim', 'yazilim'), array('web-tasarim'), array('sosyal-medya'), array('donanim', 'domain', 'hosting-sunucu'), array('mobil'));
    $html = '<section class="w3-home-popular-categories" aria-labelledby="w3-home-popular-categories-title"><header><h2 id="w3-home-popular-categories-title">Popüler Kategoriler</h2><a href="' . qa_path_html('categories') . '">Tümünü Gör <span aria-hidden="true">→</span></a></header><ul>';
    foreach ($groups as $group) {
        foreach ($group as $slug) {
            if (!isset($categories[$slug])) { continue; }
            $category = $categories[$slug];
            $path = implode('/', array_reverse(explode('/', $category['backpath'])));
            $count = number_format((int) $category['qcount'], 0, ',', '.');
            $current = in_array((int) $category['categoryid'], $selectedids, true);
            $selected = $current ? ' class="is-current" aria-current="' . ((int) end($selectedids) === (int) $category['categoryid'] ? 'page' : 'true') . '"' : '';
            $html .= '<li><a href="' . qa_path_html('questions/' . $path) . '"' . $selected . '><span class="w3-home-category-icon">' . w3_home_section_icon($slug) . '</span><span class="w3-home-category-name">' . qa_html($category['title']) . '</span><span class="w3-home-category-count" aria-label="' . $count . ' haber">' . $count . '</span></a></li>';
            break;
        }
    }
    return $html . '</ul></section>';
}
