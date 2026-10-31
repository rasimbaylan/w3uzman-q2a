<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

function w3_categories_icon($slug)
{
    $icons = array(
        'haberler' => '<rect x="4" y="4" width="16" height="16" rx="2"></rect><path d="M8 8h8M8 12h3m-3 4h8m-2-4h2"></path>',
        'yapay-zeka-ai' => '<path d="M12 4a3 3 0 0 0-5-1 4 4 0 0 0-3 6 4 4 0 0 0 0 6 4 4 0 0 0 3 6 3 3 0 0 0 5-1V4Zm0 0a3 3 0 0 1 5-1 4 4 0 0 1 3 6 4 4 0 0 1 0 6 4 4 0 0 1-3 6 3 3 0 0 1-5-1M8 6v3H5m3 9v-3H5m11-9v3h3m-3 9v-3h3M8 11h.01M16 11h.01"></path>',
        'web-yazilim' => '<path d="m8 7-5 5 5 5m8-10 5 5-5 5m-3-13-2 16"></path>',
        'web-tasarim' => '<rect x="2" y="3" width="20" height="14" rx="2"></rect><path d="M8 21h8m-4-4v4"></path>',
        'grafik-tasarim' => '<path d="M12 3a9 9 0 1 0 0 18h1a2 2 0 0 0 1-3.7 1.8 1.8 0 0 1 1-3.3h2a4 4 0 0 0 4-4c0-4-4-7-9-7Z"></path><circle cx="7" cy="11" r=".6"></circle><circle cx="10" cy="7" r=".6"></circle><circle cx="15" cy="7" r=".6"></circle><circle cx="18" cy="10" r=".6"></circle>',
        'sosyal-medya' => '<circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="5" r="3"></circle><circle cx="18" cy="19" r="3"></circle><path d="m9 10 6-3m-6 7 6 3"></path>',
        'cms' => '<ellipse cx="12" cy="5" rx="8" ry="3"></ellipse><path d="M4 5v14c0 4 16 4 16 0V5M4 12c0 4 16 4 16 0"></path>',
        'google' => '<g stroke="none"><path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2.1H12v4h5.4a4.6 4.6 0 0 1-2 3v2.5h3.2c1.9-1.7 3-4.2 3-7.4Z"></path><path fill="#34A853" d="M12 22c2.7 0 5-.9 6.7-2.4l-3.2-2.5c-.9.6-2 1-3.5 1-2.6 0-4.8-1.8-5.6-4.1H3.1v2.6A10 10 0 0 0 12 22Z"></path><path fill="#FBBC05" d="M6.4 14a6 6 0 0 1 0-4V7.4H3.1a10 10 0 0 0 0 9.2L6.4 14Z"></path><path fill="#EA4335" d="M12 5.9c1.5 0 2.8.5 3.9 1.5l2.9-2.8A9.7 9.7 0 0 0 12 2a10 10 0 0 0-8.9 5.4L6.4 10c.8-2.3 3-4.1 5.6-4.1Z"></path></g>',
        'seo' => '<rect x="3" y="14" width="3" height="7" rx=".5"></rect><rect x="10" y="8" width="3" height="13" rx=".5"></rect><rect x="17" y="2" width="3" height="19" rx=".5"></rect>',
        'hosting-sunucu' => '<rect x="3" y="3" width="18" height="7" rx="2"></rect><rect x="3" y="14" width="18" height="7" rx="2"></rect><path d="M7 6.5h.1M7 17.5h.1M15 6.5h3m-3 11h3"></path>',
        'database' => '<ellipse cx="12" cy="5" rx="8" ry="3"></ellipse><path d="M4 5v14c0 4 16 4 16 0V5M4 12c0 4 16 4 16 0"></path>',
        'e-ticaret' => '<path d="M3 3h2l3 12h11l2-9H6"></path><circle cx="9" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle>',
        'mobil' => '<rect x="6" y="2" width="12" height="20" rx="2"></rect><path d="M10 5h4m-3 14h2"></path>',
        'masaustu' => '<rect x="2" y="3" width="20" height="14" rx="2"></rect><path d="M2 7h20M9 21h6m-3-4v4M5 5h.01M8 5h.01"></path>',
        'uygulamalar' => '<rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect>',
        'otomasyon' => '<rect x="9" y="2" width="6" height="6" rx="1.5"></rect><rect x="2" y="16" width="6" height="6" rx="1.5"></rect><rect x="16" y="16" width="6" height="6" rx="1.5"></rect><path d="M12 8v4M5 16v-4h14v4"></path>',
        'siber-guvenlik' => '<path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6z"></path><path d="m8 12 3 3 5-6"></path>',
        'domain' => '<circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3c5 5 5 13 0 18-5-5-5-13 0-18Z"></path>',
        'diger' => '<circle cx="5" cy="12" r="1.3" fill="currentColor"></circle><circle cx="12" cy="12" r="1.3" fill="currentColor"></circle><circle cx="19" cy="12" r="1.3" fill="currentColor"></circle>',
        'guvenlik' => '<path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6z"></path>',
        'donanim' => '<rect x="6" y="6" width="12" height="12" rx="2"></rect><path d="M9 2v4m6-4v4M9 18v4m6-4v4M2 9h4m-4 6h4m12-6h4m-4 6h4M10 10h4v4h-4z"></path>',
    );
    $shape = isset($icons[$slug]) ? $icons[$slug] : '<path d="M3 7V5a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"></path>';
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $shape . '</svg>';
}

function w3_categories_prepare_content($content)
{
    $categories = array(); $children = array();
    foreach (qa_db_read_all_assoc(qa_db_query_sub('SELECT categoryid, parentid, title, tags, content, qcount, position, backpath FROM ^categories ORDER BY position, categoryid')) as $category) {
        $id = (int) $category['categoryid'];
        $categories[$id] = $category;
        $children[(int) $category['parentid']][] = $id;
    }
    $pathIds = isset($content['categoryids']) ? $content['categoryids'] : array();
    $current = $pathIds ? (int) end($pathIds) : 0;
    $ids = isset($children[$current]) ? $children[$current] : array();
    $descriptions = array(
        'haberler' => 'Teknoloji dünyasından gelişmeler ve gündemin öne çıkan haberleri.',
        'yapay-zeka-ai' => 'Yapay zekâ araçları, modeller ve uygulamalar.',
        'web-yazilim' => 'Programlama dilleri, kod örnekleri ve geliştirme.',
        'web-tasarim' => 'Arayüz tasarımı, modern web teknolojileri.',
        'grafik-tasarim' => 'Tasarım araçları, görsel düzenleme ve yaratıcılık.',
        'sosyal-medya' => 'Sosyal platformlar, içerik üretimi ve dijital pazarlama.',
        'cms' => 'WordPress, Joomla ve içerik yönetim sistemleri.',
        'google' => 'Google servisleri, reklam ve analitik araçlar.',
        'seo' => 'Arama motoru optimizasyonu ve stratejileri.',
        'hosting-sunucu' => 'Web barındırma, sunucu yönetimi ve altyapı çözümleri.',
        'database' => 'Veritabanları, sorgular ve veri yönetimi üzerine kaynaklar.',
        'e-ticaret' => 'E-ticaret platformları, çevrimiçi mağazalar ve satış araçları.',
        'mobil' => 'Mobil cihazlar, uygulamalar ve mobil teknoloji gündemi.',
        'masaustu' => 'Masaüstü platformlar, yazılım güncellemeleri ve kullanım rehberleri.',
        'uygulamalar' => 'Masaüstü ve web uygulamaları, tarayıcılar ve üretkenlik araçları.',
        'otomasyon' => 'İş akışları, uygulama entegrasyonları ve otomasyon platformları.',
        'siber-guvenlik' => 'Web ve uygulama güvenliği, güvenlik açıkları ve korunma yöntemleri.',
        'domain' => 'Alan adları, kayıt işlemleri ve domain dünyasına dair bilgiler.',
        'diger' => 'Diğer teknoloji ve güncel konular.',
    );
    $tones = array('yapay-zeka-ai' => 'blue', 'web-yazilim' => 'green', 'web-tasarim' => 'purple', 'sosyal-medya' => 'pink', 'grafik-tasarim' => 'orange', 'seo' => 'gold', 'cms' => 'teal', 'google' => 'blue', 'hosting-sunucu' => 'blue', 'database' => 'purple', 'mobil' => 'pink', 'masaustu' => 'purple', 'e-ticaret' => 'gold', 'domain' => 'blue', 'uygulamalar' => 'blue', 'otomasyon' => 'purple', 'siber-guvenlik' => 'teal');
    $content['title'] = $current && isset($categories[$current]) ? qa_html($categories[$current]['title']) . ' Alt Kategorileri' : 'Kategoriler';
    $content['w3_categories_summary'] = count($ids) . ($current ? ' alt kategori' : ' ana kategori') . ' · ' . count($categories) . ' toplam konu';
    $content['w3_categories_intro'] = $current ? 'İlgini çeken alt konuyu seç; haberleri ve rehberleri keşfet.' : 'Teknoloji, yazılım ve dijital dünya ile ilgili tüm kategorileri keşfedin.';
    $content['widgets'] = array();
    $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
    unset($content['nav_list'], $content['navigation']['cat']);
    $content['canonical'] = qa_get_canonical();
    $content['description'] = 'W3Uzman kategorilerinde teknoloji haberlerini, yazılım ve web tasarım rehberlerini ilgi alanınıza göre keşfedin.';

    $breadcrumb = '<nav class="w3-cat-breadcrumb" aria-label="Kategori yolu"><a href="' . qa_path_html('') . '" aria-label="Anasayfa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m3 10 9-7 9 7M5 9v12h5v-7h4v7h5V9"></path></svg><span>Anasayfa</span></a><span aria-hidden="true">›</span>';
    $breadcrumb .= $current ? '<a href="' . qa_path_html('categories') . '">Kategoriler</a>' : '<span aria-current="page">Kategoriler</span>';
    if ($current) {
        foreach ($pathIds as $id) {
            if (!isset($categories[$id])) { continue; }
            $path = implode('/', array_reverse(explode('/', $categories[$id]['backpath'])));
            $breadcrumb .= '<span aria-hidden="true">›</span><a href="' . qa_path_html('categories/' . $path) . '">' . qa_html($categories[$id]['title']) . '</a>';
        }
    }
    $content['w3_categories_breadcrumb'] = $breadcrumb . '</nav>';
    $html = '<div class="w3-categories">';
    $content['w3_categories_toolbar'] = '<div class="w3-cat-toolbar"><form class="w3-cat-filter" role="search"><label class="w3-cat-reader" for="w3-cat-search">Kategorilerde ara</label><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"></circle><path d="m16 16 4.5 4.5"></path></svg><input id="w3-cat-search" type="search" placeholder="Kategori veya konu ara…" autocomplete="off" disabled><button id="w3-cat-clear" type="button" hidden>Temizle</button></form><div class="w3-cat-toolbar-right"><span class="w3-cat-reader" id="w3-cat-result-count" role="status">' . count($ids) . ' kategori</span><label class="w3-cat-reader" for="w3-cat-sort">Kategorileri sırala</label><select id="w3-cat-sort" disabled><option value="default">Önerilen sıra</option><option value="popular">Popüler olanlar</option><option value="name">Ada göre A–Z</option></select></div></div>';
    $html .= '<div class="w3-cat-grid" id="w3-cat-grid">';
    foreach ($ids as $order => $id) {
        $category = $categories[$id];
        $childIds = isset($children[$id]) ? $children[$id] : array();
        $path = implode('/', array_reverse(explode('/', $category['backpath'])));
        $url = qa_path_html('questions/' . $path);
        $slug = $category['tags'];
        $root = $category; $seen = array();
        while (!empty($root['parentid']) && isset($categories[$root['parentid']]) && !isset($seen[$root['parentid']])) { $seen[$root['parentid']] = true; $root = $categories[$root['parentid']]; }
        $description = isset($descriptions[$slug]) ? $descriptions[$slug] : trim(strip_tags($category['content']));
        if ($description === '') { $description = 'Bu konudaki haberleri, rehberleri ve paylaşımları keşfedin.'; }
        $childNames = array();
        foreach ($childIds as $childId) { $childNames[] = $categories[$childId]['title']; }
        $tone = isset($tones[$root['tags']]) ? $tones[$root['tags']] : 'blue';
        $html .= '<article class="w3-cat-card w3-cat-tone-' . $tone . '" data-name="' . qa_html($category['title']) . '" data-search="' . qa_html($category['title'] . ' ' . $description . ' ' . implode(' ', $childNames)) . '" data-count="' . (int) $category['qcount'] . '" data-order="' . $order . '">';
        $html .= '<div class="w3-cat-card-body"><span class="w3-cat-icon" aria-hidden="true">' . w3_categories_icon($root['tags']) . '</span><div class="w3-cat-copy"><h2><a href="' . $url . '">' . qa_html($category['title']) . '</a></h2><p class="w3-cat-description">' . qa_html($description) . '</p></div></div>';
        if ($childIds) {
            $html .= '<div class="w3-cat-topics" aria-label="Alt konular">';
            foreach (array_slice($childIds, 0, 2) as $childId) { $child = $categories[$childId]; $childPath = implode('/', array_reverse(explode('/', $child['backpath']))); $html .= '<a href="' . qa_path_html('questions/' . $childPath) . '">' . qa_html($child['title']) . '</a>'; }
            if (count($childIds) > 2) {
                $html .= '<details class="w3-cat-more"><summary>+' . (count($childIds) - 2) . ' konu</summary><div>';
                foreach (array_slice($childIds, 2) as $childId) { $child = $categories[$childId]; $childPath = implode('/', array_reverse(explode('/', $child['backpath']))); $html .= '<a href="' . qa_path_html('questions/' . $childPath) . '">' . qa_html($child['title']) . '</a>'; }
                $html .= '</div></details>';
            }
            $html .= '</div>';
        }
        $html .= '<footer><a class="w3-cat-news-count" href="' . $url . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 7h6M9 11h6m-6 4h3"></path></svg><span>' . number_format((int) $category['qcount'], 0, ',', '.') . ' haber</span></a><a class="w3-cat-sub-link" href="' . ($childIds ? qa_path_html('categories/' . $path) : $url) . '" aria-label="' . qa_html($category['title'] . ($childIds ? ': ' . count($childIds) . ' alt kategoriyi keşfet' : ' haberlerini keşfet')) . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"></path></svg></a></footer></article>';
    }
    $html .= '</div><div class="w3-cat-empty" id="w3-cat-empty"' . ($ids ? ' hidden' : '') . '><h2>' . ($ids ? 'Aradığınız kategori bulunamadı' : 'Bu bölümde alt kategori bulunmuyor') . '</h2><p>' . ($ids ? 'Farklı bir kelime deneyin veya aramayı temizleyin.' : 'Tüm kategorilere dönerek diğer konuları keşfedebilirsiniz.') . '</p></div><div class="w3-cat-bottom"><p>Aradığınız içerik başka bir konuda olabilir.</p><a href="' . qa_path_html('questions') . '">Tüm haberleri görüntüle <span aria-hidden="true">→</span></a></div></div>';
    $content['custom'] = $html;
    $content['script'][] = '<script defer src="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-categories.js?v=2"></script>';
    return $content;
}
