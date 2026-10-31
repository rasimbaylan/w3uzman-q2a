<?php
/** Category artwork used only when an article has no usable cover image. */
if (!defined('QA_VERSION')) {
    http_response_code(403);
    exit;
}

function w3_category_cover_url($categoryid, $title = '')
{
    static $categories = null;
    if ($categories === null) {
        $categories = array();
        foreach (qa_db_read_all_assoc(qa_db_query_sub('SELECT categoryid, parentid, tags FROM ^categories')) as $category) {
            $categories[(int)$category['categoryid']] = $category;
        }
    }

    // Match slugs rather than IDs so category numbering can differ on live.
    $artwork = array(
        'web-yazilim' => 'yazilim', 'yazilim' => 'yazilim',
        'web-tasarim' => 'web-tasarim', 'grafik-tasarim' => 'grafik-tasarim',
        'sosyal-medya' => 'sosyal-medya', 'cms' => 'cms', 'seo' => 'seo',
        'e-ticaret' => 'e-ticaret', 'eticaret' => 'e-ticaret', 'opencart' => 'e-ticaret',
        'woocommerce' => 'e-ticaret', 'magento' => 'e-ticaret', 'prestashop' => 'e-ticaret',
        'hosting-sunucu' => 'hosting-sunucu',
        'database' => 'database', 'veritabani' => 'database', 'google' => 'google',
        'domain' => 'domain', 'haberler' => 'teknoloji', 'teknoloji' => 'teknoloji',
        'mobil' => 'mobil', 'masaustu' => 'yazilim', 'donanim' => 'donanim',
        'uygulamalar' => 'yazilim', 'otomasyon' => 'yazilim', 'siber-guvenlik' => 'yazilim',
        'dersler' => 'egitim',
        'yapay-zeka-ai' => 'yapay-zeka', 'yapay-zeka' => 'yapay-zeka',
    );
    $key = 'teknoloji';
    $visited = array();
    $id = (int)$categoryid;
    while (isset($categories[$id]) && !isset($visited[$id])) {
        $visited[$id] = true;
        $category = $categories[$id];
        if (isset($artwork[$category['tags']])) {
            $key = $artwork[$category['tags']];
            break;
        }
        $id = (int)$category['parentid'];
    }

    // A broad/general category can still use a topical fallback; explicit categories keep priority.
    if ($key === 'teknoloji' && is_string($title)) {
        if (preg_match('/yapay zek[aâ]|chatgpt|openai|gemini|claude|gpt[\s-]?[0-9]|llama/iu', $title)) { $key = 'yapay-zeka'; }
        elseif (preg_match('/e[- ]?ticaret|opencart|woocommerce|shopify|magento/iu', $title)) { $key = 'e-ticaret'; }
        elseif (preg_match('/yaz[ıi]l[ıi]m|programlam|javascript|python|\bphp\b|\bcss\b|\bhtml\b|copilot/iu', $title)) { $key = 'yazilim'; }
    }
    // Four photographic cover families. Existing article images and stored content are never changed.
    $families = array(
        'e-ticaret' => 'e-ticaret',
        'yapay-zeka' => 'yapay-zeka',
        'yazilim' => 'yazilim', 'web-tasarim' => 'yazilim', 'grafik-tasarim' => 'yazilim',
        'cms' => 'yazilim', 'database' => 'yazilim', 'egitim' => 'yazilim',
        'seo' => 'e-ticaret', 'google' => 'e-ticaret', 'sosyal-medya' => 'e-ticaret',
    );
    $family = isset($families[$key]) ? $families[$key] : 'teknoloji';
    return qa_path_to_root() . 'qa-theme/FlatBox/images/category-covers-v2/' . $family . '.webp';
}
