<?php

/*

  FlatBox Theme for Question2Answer Package
  Copyright (C) 2014  Q2A Market <http://www.q2amarket.com>

  File:           qa-theme.php
  Version:        FlatBox 1.0.2
  Description:    Q2A theme class

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */

/**
 * FlatBox theme extends
 *
 * Extends the core theme class <code>qa_html_theme_base</code>
 *
 * @package qa_html_theme_base
 * @subpackage qa_html_theme
 * @category Theme
 * @since FlatBox 1.0.0
 * @version 1.4
 * @author Q2A Market <http://www.q2amarket.com>
 * @copyright (c) 2014, Q2A Market
 * @license http://www.gnu.org/copyleft/gpl.html
 */
require_once QA_INCLUDE_DIR . 'util/w3-category-covers.php';
require_once __DIR__ . '/inc/w3-site-stats.php';

class qa_html_theme extends qa_html_theme_base
{

    /**
     * @access public
     * @since FlatBox 1.0.0
     * @param type $template
     * @param type $content
     * @param type $rooturl
     * @param type $request
     */
    function __construct($template, $content, $rooturl, $request)
    {
        parent::__construct($template, $content, $rooturl, $request);
        if ($request === 'hakkimizda' && ($template === 'custom' || strpos($template, 'custom-') === 0) && isset($this->content['custom']) && empty($this->content['error'])) {
            $this->content['w3_about_page'] = true;
            $this->content['widgets'] = array();
            $this->content['sidebar'] = $this->content['sidepanel'] = $this->content['suggest_next'] = '';
            $this->content['description'] = 'W3Uzman; teknoloji, yazılım ve yapay zekâ üzerine Türkçe içerikler ve ortak bir öğrenme alanı sunan bilgi ve paylaşım platformudur.';
            unset($this->content['navigation']['cat']);
        }
        require_once __DIR__ . '/inc/w3-home-sections.php';
        require_once __DIR__ . '/inc/w3-site-polish.php';
        w3_prepare_listing_content($this->content);
        if ($template === 'user' || strpos($template, 'user-') === 0) {
            require_once __DIR__ . '/inc/w3-profile.php';
            if ($template === 'user') { w3_profile_prepare($this->content); }
        }
        if ($template === 'question') {
            require_once __DIR__ . '/inc/w3-article.php';
            w3_article_prepare($this->content);
        }
        if ($template === 'questions' || $template === 'hot') {
            require_once __DIR__ . '/inc/w3-news-sidebar.php';
            // Q2A omits category navigation for some sorted lists. Keep topic discovery available.
            if (!empty($this->content['categoryids']) && empty($this->content['navigation']['cat']) && qa_using_categories()) {
                require_once QA_INCLUDE_DIR . 'db/selects.php';
                require_once QA_INCLUDE_DIR . 'app/format.php';
                $categoryids = $this->content['categoryids'];
                $categoryid = (int) end($categoryids);
                if ($categoryid > 0) {
                    $navcategories = qa_db_select_with_pending(qa_db_category_nav_selectspec($categoryid, true));
                    $this->content['navigation']['cat'] = qa_category_navigation($navcategories, $categoryid, 'questions/', false);
                }
            }
            w3_news_prepare_google_archive($this->content);
        }
        if ((in_array($template, array('tags', 'feedback', 'users', 'questions', 'hot'), true) || $template === 'user' || strpos($template, 'user-') === 0) && !empty($this->content['widgets']['full']['top'])) {
            foreach ($this->content['widgets']['full']['top'] as $key => $widget) {
                if ($widget instanceof qa_ask_box) { unset($this->content['widgets']['full']['top'][$key]); }
            }
        }
        if ($template === 'categories') {
            require_once __DIR__ . '/inc/w3-categories-page.php';
            $this->content = w3_categories_prepare_content($this->content);
        }

        /**
         * Below condition only loads the require class if Q2A set
         * the FlatBox theme as site theme.
         *
         * Since this file is for FlatBox theme itself so it may not require
         * but still just to cross check once before load files
         *
         * TO DEVELOPERS
         * -------------
         * If you change the theme name from `FlatBox` to anything, make sure to
         * change in below condition
         */
        if (qa_opt('site_theme') === 'FlatBox') {
            require_once('inc/qam-flatbox-theme.php');
            require_once ('inc/qam-flatbox-functions.php');
        }
    }

    /**
     * Adding aditional meta for responsive design
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global type $flatbox
     */
    function head_metas()
    {
        global $flatbox;

        qa_html_theme_base::head_metas();
        $this->output($flatbox->meta_viewport); // add meta tag for mobiles
    }

    /** Keep the bundled logo on the current installation, rather than requesting the live site. */
    public function logo()
    {
        $original = $this->content['logo'];
        $this->content['logo'] = preg_replace('~https?://(?:www\.)?w3uzman\.com/qa-theme/FlatBox/images/logo\.png~i', qa_path_to_root() . 'qa-theme/FlatBox/images/logo.png', $original);
        parent::logo();
        $this->content['logo'] = $original;
    }

    public function avatar($item, $class, $prefix = null)
    {
        if (isset($item['avatar'])) { $item['avatar'] = w3_safe_avatar_html($item['avatar']); }
        parent::avatar($item, $class, $prefix);
    }

    public function form_image($field, $style)
    {
        if (isset($field['html'])) { $field['html'] = w3_safe_avatar_html($field['html']); }
        parent::form_image($field, $style);
    }

    /**
     * Adding theme stylesheets
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global type $flatbox
     */
    function head_css()
    {
        global $flatbox;

        // get theme css files
        $styles = $flatbox->theme_css;

        foreach ($styles as $style)
        {
            $rooturl = (strpos($style, 'Ubuntu') ? NULL : $this->rooturl);
            $href = $rooturl . $style . '.css?' . $flatbox->flatbox_version;
            $this->output('<link rel="preload" as="style" href="' . qa_html($href) . '">');
            $this->content['css_src'][] = $href;
        }
        qa_html_theme_base::head_css();

        $this->output($flatbox->inline_css);
        $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/qa-styles.css?site-polish-20261021-footer-6">');
        if (qa_request() === 'whois-sorgulama') {
            $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-plugin/w3-whois/whois.css?v=6">');
        }
        if ($this->template === 'categories') {
            $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-categories.css?v=3">');
        }
        $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-home-sections.css?v=20261021-selected-category-1">');
        if ($this->template === 'questions' || $this->template === 'hot') {
            $newsCssVersion = is_file(__DIR__ . '/w3-news.css') ? filemtime(__DIR__ . '/w3-news.css') : '20261022-cms-rows';
            $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-news.css?v=' . qa_html((string)$newsCssVersion) . '">');
        }
        if (!empty($this->content['w3_info_page'])) { $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-plugin/w3-info-pages/info-pages.css?v=1">'); }
        if (!empty($this->content['w3_about_page'])) { $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-about.css?v=20261018-4">'); }
        if (!empty($this->content['w3_article_page'])) { $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-article.css?v=20261021-1">'); }
        if ($this->template === 'users') { $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-members.css?v=1">'); }
        if ($this->template === 'user' || strpos($this->template, 'user-') === 0) { $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-profile.css?v=1">'); }
        $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-readability.css?v=20261021-2">');
        $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-site-stats.css?v=20261021-1">');
        $footerCssVersion = is_file(__DIR__ . '/w3-footer-light.css') ? filemtime(__DIR__ . '/w3-footer-light.css') : '20261022-3';
        $this->output('<link rel="stylesheet" href="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-footer-light.css?v=' . qa_html((string)$footerCssVersion) . '">');
    }

    /**
     * Adding theme javascripts
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global array $flatbox
     */
    function head_script()
    {
        global $flatbox;

        $scripts = $flatbox->theme_js;

        /**
         * I found this is the perfect way to add js files.
         *
         * Since I wrote the article for it (http://goo.gl/7KFipJ),
         * which now I need to modify based on this code.
         * I hope to get time soon to modify it.
         */
        foreach ($scripts as $script)
        {
            $this->content['script'][] = '<script defer src="' . $this->rooturl . $script . '.js?' . $flatbox->flatbox_version . '"></script>';
        }
        qa_html_theme_base::head_script();
        if ($this->template === 'feedback') {
            $this->output('<script defer src="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-contact.js?v=1"></script>');
        }
        $this->output('<script defer src="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-site.js?v=3"></script>');
        if (!empty($this->content['w3_article_page'])) { $this->output('<script defer src="' . qa_path_to_root() . 'qa-theme/FlatBox/w3-article.js?v=20261021-1"></script>'); }
        $this->newsarticle_jsonld();
    }

    private function newsarticle_jsonld(){ if($this->template!=='question'||empty($this->content['q_view']))return; $q=$this->content['q_view']; $r=@$q['raw']; require_once QA_INCLUDE_DIR.'db/metas.php'; $site=rtrim(qa_opt('site_url'),'/'); $url=$site.'/'.ltrim(@$q['url'],'./'); $s=array('@context'=>'https://schema.org','@type'=>'NewsArticle','mainEntityOfPage'=>array('@type'=>'WebPage','@id'=>$url),'headline'=>isset($r['title'])?$r['title']:strip_tags(@$q['title']),'datePublished'=>!empty($r['created'])?(is_numeric($r['created']) ? date('c',$r['created']) : date('c',strtotime($r['created']))):null,'dateModified'=>!empty($r['updated'])?(is_numeric($r['updated']) ? date('c',$r['updated']) : date('c',strtotime($r['updated']))):(!empty($r['created'])?(is_numeric($r['created']) ? date('c',$r['created']) : date('c',strtotime($r['created']))):null),'publisher'=>array('@type'=>'Organization','name'=>'W3Uzman','logo'=>array('@type'=>'ImageObject','url'=>$site.'/w3uzman.png'))); $cover=qa_db_postmeta_get(@$r['postid'],'w3_cover_image'); if($cover)$s['image']=array($cover); if(!empty($q['who']['data']))$s['author']=array('@type'=>'Person','name'=>strip_tags($q['who']['data'])); $this->output('<script type="application/ld+json">'.json_encode($s,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP).'</script>'); }
    /**`n     * Adding point count for logged in user
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global array $flatbox
     */
    function logged_in()
    {
        global $flatbox;
        qa_html_theme_base::logged_in();

        $this->output($flatbox->headers['user_points']);
    }

    /**
     * Adding sidebar for mobile device
     *
     * @access public
     * @since FlatBox 1.0.0
     */
    function body()
    {
        if (empty($this->content['w3_tool_page']) && empty($this->content['w3_info_page']) && empty($this->content['w3_about_page']) && qa_is_mobile_probably() && qa_request() !== '' && qa_request() !== 'whois-sorgulama' && !in_array($this->template, array('categories', 'feedback', 'users', 'user', 'questions', 'hot'), true) && strpos($this->template, 'user-') !== 0) {

            $this->output('<div id="qam-sidepanel-toggle"><i class="icon-left-open-big"></i></div>');
            $this->output('<div id="qam-sidepanel-mobile">');
            $this->output('<div class="qa-sidepanel">');
            $this->w3_subpage_sidebar();
            $this->output('</div>');
            $this->output('</div>');
        }
        qa_html_theme_base::body();
    }

    /**
     * Adding require javascripts
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global array $flatbox
     */
    function body_script()
    {
        global $flatbox;

        qa_html_theme_base::body_script();
        $this->output($flatbox->headers['fb_like_box_init']);
        $this->output($flatbox->headers['twitter_widget_init']);
    }

    /**
     * Adding body class dynamically
     *
     * override to add class on admin/approve-users page
     *
     * @access public
     * @since FlatBox 1.0.0
     * @return string body class
     */
    public function body_tags()
    {
        global $flatbox;

        $class = 'qa-template-' . qa_html($this->template) . ' w3-modern-header';
        if (qa_request() === '') { $class .= ' w3-home-page'; }
        if ($this->template === 'tags') { $class .= ' w3-tags-page'; }
        if ($this->template === 'feedback') { $class .= ' w3-contact-page'; }
        if ($this->template === 'users') { $class .= ' w3-members-page'; }
        if ($this->template === 'questions' || $this->template === 'hot') { $class .= ' w3-news-page'; }
        if (!empty($this->content['w3_article_page'])) { $class .= ' w3-article-page'; }
        if (!empty($this->content['w3_info_page'])) { $class .= ' w3-info-page'; }
        if (!empty($this->content['w3_about_page'])) { $class .= ' w3-about-page'; }
        if (!empty($this->content['w3_tool_page'])) { $class .= ' w3-tools-page'; }
        if ($this->template === 'user' || strpos($this->template, 'user-') === 0) { $class .= ' w3-profile-page'; }
        if ($this->template === 'user') { $class .= ' w3-profile-detail'; }
        if (qa_request() === 'whois-sorgulama') {
            $class .= ' qa-template-qa w3-whois-page';
        }
        if ($this->template === 'categories') {
            $class .= ' qa-template-qa w3-categories-page';
        }

        if (isset($this->content['categoryids'])) {
            foreach ($this->content['categoryids'] as $categoryid)
            {
                $class .= ' qa-category-' . qa_html($categoryid);
            }
        }

        // add class if admin/appovoe-users page
        if (($this->template === 'admin') && (qa_request_part(1) === 'approve')) {
            $class .= ' qam-approve-users';
        }

        $class .= ' ' . $flatbox->fixed_topbar;

        $this->output('class="' . $class . ' qa-body-js-off"');
    }

    /**
     * login form
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global array $flatbox
     */
    function nav_user_search()
    { // outputs login form if user not logged in
        global $flatbox;

        $this->output('<DIV CLASS="qam-account-items-wrapper">');

        $this->output($flatbox->headers['user_account']);

        $this->output('<DIV CLASS="qam-account-items clearfix">');

        if (!qa_is_logged_in()) {
            $login = @$this->content['navigation']['user']['login'];

            if (isset($login) && !QA_FINAL_EXTERNAL_USERS) {
                $this->output(
                        '<!--[Begin: login form]-->',
                        '<form id="qa-loginform" action="' . $login['url'] . '" method="post">',
                        '<input type="text" id="qa-userid" name="emailhandle" placeholder="' . trim(qa_lang_html('users/email_handle_label'), ':') . '" />',
                        '<input type="password" id="qa-password" name="password" placeholder="' . trim(qa_lang_html('users/password_label'), ':') . '" />',
                        '<div id="qa-rememberbox"><input type="checkbox" name="remember" id="qa-rememberme" value="1"/>',
                        '<label for="qa-rememberme" id="qa-remember">' . qa_lang_html('users/remember') . '</label></div>',
                        '<input type="hidden" name="code" value="' . qa_html(qa_get_form_security_code('login')) . '"/>',
                        '<input type="submit" value="' . $login['label'] . '" id="qa-login" name="dologin" />',
                        '</form>',
                        '<!--[End: login form]-->'
                );

                unset($this->content['navigation']['user']['login']); // removes regular navigation link to log in page
            }
        }

        qa_html_theme_base::nav('user');
        $this->output('</DIV> <!-- END qam-account-items -->');
        $this->output('</DIV> <!-- END qam-account-items-wrapper -->');
    }

    /*
     * Adding top bar
     *
     * @access public
     * @since FlatBox 1.0.0
     */

    private function w3_home_ad_html($placement = '')
    {
        if (!qa_google_ads_enabled()) {
            return '';
        }

        $class = 'w3-home-ad' . ($placement !== '' ? ' w3-home-ad--' . $placement : '');
        $size = $placement === 'sidebar' ? '300 x 250' : '728 x 90';

        return '<div class="' . $class . '" aria-label="Reklam alanı"><span>Reklam Alanı<br><b>' . $size . '</b></span><small>Google Adsense<br>Reklamınız Burada</small></div>';
    }

    function qam_body_prefix()
    {
        global $flatbox;

        $this->output('<div id="qam-topbar" class="clearfix ' . $flatbox->fixed_topbar . '">');
        $this->nav_main_sub();
        $this->output('</div><!-- END qam-topbar -->');
        if (qa_request() === '') {
          $headline_result = qa_db_query_sub(
              "SELECT posts.postid, posts.title, posts.content, posts.categoryid
               FROM ^posts AS posts
               INNER JOIN ^postmetas AS metas ON metas.postid=posts.postid
               WHERE posts.type='Q' AND metas.title='w3_slider_featured' AND metas.content='1'
               ORDER BY posts.postid DESC LIMIT 15"
          );
          $headlines = qa_db_read_all_assoc($headline_result);
          $slides_html = '';
          $dots_html = '';

          foreach ($headlines as $index => $headline) {
              $fallback_image = w3_category_cover_url($headline['categoryid'], $headline['title']);
              $image_url = null;
              $content = isset($headline['content']) ? $headline['content'] : '';

              if (preg_match('~<img[^>]+src=["\']([^"\']+)["\']~i', $content, $match)) {
                  $image_url = $match[1];
              } elseif (preg_match('~!\[[^\]]*\]\(([^\s\)]+)~', $content, $match)) {
                  $image_url = $match[1];
              } elseif (preg_match('~!\[[^\]]*\]\[([^\]]+)\]~', $content, $reference)) {
                  $reference_pattern = '~^\s*\[' . preg_quote($reference[1], '~') . '\]:\s*(\S+)~mi';
                  if (preg_match($reference_pattern, $content, $match)) {
                      $image_url = $match[1];
                  }
              }

              // Old posts can contain HTML-escaped blob URLs. Decode before
              // escaping for output so &amp; is not emitted as &amp;amp;.
              if ($image_url) {
                  $image_url = w3_site_image_url(html_entity_decode($image_url, ENT_QUOTES, 'UTF-8'));
              }

              if (!$image_url) {
                  $image_url = $fallback_image;
              }

              $active_class = $index === 0 ? ' is-active' : '';
              $hidden = $index === 0 ? 'false' : 'true';
              $tabindex = $index === 0 ? '' : ' tabindex="-1"';
              $loading = $index === 0 ? ' fetchpriority="high"' : ' loading="lazy"';
              $title = qa_html($headline['title']);
              $url = qa_q_path_html($headline['postid'], $headline['title']);

              $slides_html .= '<a class="vitrin-slide' . $active_class . '" href="' . $url . '" aria-hidden="' . $hidden . '"' . $tabindex . '>' .
                  '<img src="' . qa_html($image_url) . '" alt="' . $title . '"' . $loading . ' onerror="' . qa_html('this.onerror=null;this.src=' . qa_js($fallback_image) . ';') . '">' .
                  '<span class="vitrin-copy"><small>Yapay Zeka</small><strong>' . $title . '</strong><em>Teknoloji, yapay zeka ve dijital dünyadaki gelişmelerin ayrıntıları.</em><b>Haberin Devamı <i aria-hidden="true">&#8594;</i></b></span></a>';
              $dots_html .= '<button' . ($index === 0 ? ' class="is-active" aria-current="true"' : '') .
                  ' type="button" aria-label="' . ($index + 1) . '. manşet">' . ($index + 1) . '</button>';
          }

          $side_result = qa_db_query_sub(
              "SELECT posts.postid, posts.title, posts.content, posts.views, posts.categoryid
               FROM ^posts AS posts
               INNER JOIN ^postmetas AS metas ON metas.postid=posts.postid
               WHERE posts.type='Q' AND metas.title='w3_slider_side' AND metas.content='1'
               ORDER BY posts.postid DESC LIMIT 4"
          );
          $popular_headlines = qa_db_read_all_assoc($side_result);
          $side_ids = array();
          foreach ($popular_headlines as $side_headline) {
              $side_ids[$side_headline['postid']] = true;
          }
          if (count($popular_headlines) < 4) {
              $fallback_result = qa_db_query_sub(
                  "SELECT postid, title, content, views, categoryid FROM ^posts WHERE type='Q' ORDER BY views DESC, postid DESC LIMIT 20"
              );
              foreach (qa_db_read_all_assoc($fallback_result) as $fallback_headline) {
                  if (!isset($side_ids[$fallback_headline['postid']])) {
                      $popular_headlines[] = $fallback_headline;
                      $side_ids[$fallback_headline['postid']] = true;
                  }
                  if (count($popular_headlines) >= 4) {
                      break;
                  }
              }
          }
          $popular_html = '';

          foreach ($popular_headlines as $index => $popular) {
              $fallback_image = w3_category_cover_url($popular['categoryid'], $popular['title']);
              $image_url = null;
              $content = isset($popular['content']) ? $popular['content'] : '';

              if (preg_match('~<img[^>]+src=["\']([^"\']+)["\']~i', $content, $match)) {
                  $image_url = $match[1];
              } elseif (preg_match('~!\[[^\]]*\]\(([^\s\)]+)~', $content, $match)) {
                  $image_url = $match[1];
              } elseif (preg_match('~!\[[^\]]*\]\[([^\]]+)\]~', $content, $reference)) {
                  $reference_pattern = '~^\s*\[' . preg_quote($reference[1], '~') . '\]:\s*(\S+)~mi';
                  if (preg_match($reference_pattern, $content, $match)) {
                      $image_url = $match[1];
                  }
              }

              if ($image_url) {
                  $image_url = w3_site_image_url(html_entity_decode($image_url, ENT_QUOTES, 'UTF-8'));
              }

              if (!$image_url) {
                  $image_url = $fallback_image;
              }

              $popular_title = qa_html($popular['title']);
              $popular_url = qa_q_path_html($popular['postid'], $popular['title']);
              $popular_html .= '<a class="vitrin-popular-item" href="' . $popular_url . '">' .
                  '<span class="vitrin-popular-thumb" aria-hidden="true"><img src="' . qa_html($image_url) . '" alt="" loading="lazy" onerror="' . qa_html('this.onerror=null;this.src=' . qa_js($fallback_image) . ';') . '"></span>' .
                  '<span><strong>' . $popular_title . '</strong></span><i aria-hidden="true">&#8250;</i>' .
                  '</a>';
          }

          $this->output('
            ' . $this->w3_home_ad_html() . '
            <section class="vitrin" id="w3-home-featured" aria-label="Öne çıkan içerikler">
              <div class="vitrin-layout">
                <div class="vitrin-middle" data-vitrin-slider>
                  <div class="vitrin-track">' . $slides_html . '</div>
                  <button class="vitrin-arrow vitrin-prev" type="button" aria-label="Önceki manşet">&#10094;</button>
                  <button class="vitrin-arrow vitrin-next" type="button" aria-label="Sonraki manşet">&#10095;</button>
                  <div class="vitrin-dots" role="group" aria-label="Manşet seçimi">' . $dots_html . '</div>
                </div>
                <aside class="vitrin-popular" aria-label="Öne çıkan içerikler">
                  <h2><span aria-hidden="true">&#9889;</span> Öne Çıkanlar</h2>
                  <div class="vitrin-popular-list">' . $popular_html . '</div>
                </aside>
              </div>
            </section>
            <script>
            (function () {
              var slider = document.querySelector("[data-vitrin-slider]");
              if (!slider) return;
              var slides = slider.querySelectorAll(".vitrin-slide");
              var dots = slider.querySelectorAll(".vitrin-dots button");
			  if (!slides.length) return;
              var current = 0;
              var timer;
              var touchStartX = 0;
              var touchStartY = 0;
              function show(index) {
                current = (index + slides.length) % slides.length;
                for (var i = 0; i < slides.length; i++) {
                  var active = i === current;
                  slides[i].classList.toggle("is-active", active);
                  slides[i].setAttribute("aria-hidden", active ? "false" : "true");
                  active ? slides[i].removeAttribute("tabindex") : slides[i].setAttribute("tabindex", "-1");
                  dots[i].classList.toggle("is-active", active);
                  active ? dots[i].setAttribute("aria-current", "true") : dots[i].removeAttribute("aria-current");
                }
              }
              var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
              function start() { clearInterval(timer); if (slides.length > 1 && !reducedMotion.matches && !document.hidden && !slider.contains(document.activeElement)) timer = setInterval(function () { show(current + 1); }, 5500); }
              document.addEventListener("visibilitychange", function () { document.hidden ? clearInterval(timer) : start(); });
              slider.querySelector(".vitrin-prev").addEventListener("click", function () { show(current - 1); start(); });
              slider.querySelector(".vitrin-next").addEventListener("click", function () { show(current + 1); start(); });
              for (var i = 0; i < dots.length; i++) (function (index) {
                dots[index].addEventListener("click", function () { show(index); start(); });
                dots[index].addEventListener("mouseenter", function () { clearInterval(timer); show(index); });
              })(i);
              slider.addEventListener("mouseenter", function () { clearInterval(timer); });
              slider.addEventListener("mouseleave", start);
              slider.addEventListener("focusin", function () { clearInterval(timer); });
              slider.addEventListener("focusout", start);
              slider.addEventListener("touchstart", function (event) {
                var touch = event.changedTouches[0];
                touchStartX = touch.clientX;
                touchStartY = touch.clientY;
                clearInterval(timer);
              }, { passive: true });
              slider.addEventListener("touchend", function (event) {
                var touch = event.changedTouches[0];
                var dx = touch.clientX - touchStartX;
                var dy = touch.clientY - touchStartY;
                if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
                  show(current + (dx < 0 ? 1 : -1));
                }
                start();
              }, { passive: true });
              start();
            })();
            </script>
            ');
        }
        if (qa_is_logged_in()) {
            $this->output($flatbox->headers['ask_button']);
        }
        $this->qam_search('the-top', 'the-top-search');
    }

    /**
     * modifying markup for topbar
     *
     * @access public
     * @since FlatBox 1.0.0
     */
    function nav_main_sub()
    {
        // Every template uses the same header; keep the native subnavigation below it.
        $request = qa_request();
        $categorypage = $this->template === 'categories'
            || ($this->template === 'questions' && !empty($this->content['categoryids']));
        $newspage = $request === '' || $this->template === 'question' || $this->template === 'hot'
            || ($this->template === 'questions' && !$categorypage);
        $aipage = $this->template === 'questions' && qa_request_part(1) === 'yapay-zeka-ai';
        $items = array(
            array('Haberler', 'questions', '', $newspage),
            array('Yapay Zeka', 'questions/yapay-zeka-ai', '', $aipage),
            array('Dersler', 'dersler', '', $request === 'dersler'),
            array('Kategoriler', 'categories', 'w3-home-categories-link', $categorypage && !$aipage),
            array('Whois', 'whois-sorgulama', 'w3-home-whois', $request === 'whois-sorgulama'),
        );
        $links = '';
        foreach ($items as $item) {
            $class = trim($item[2] . ($item[3] ? ' is-current' : ''));
            $links .= '<a href="' . qa_path_html($item[1]) . '"'
                . ($class !== '' ? ' class="' . $class . '"' : '')
                . ($item[3] ? ' aria-current="page"' : '') . '>' . $item[0] . '</a>';
        }
        $tools = $this->w3_web_tools();
        $toolLinks = '';
        foreach ($tools as $toolRequest => $toolLabel) {
            $selected = $request === $toolRequest;
            $toolLinks .= '<a href="' . qa_path_html($toolRequest) . '"'
                . ($selected ? ' class="is-current" aria-current="page"' : '') . '>' . $toolLabel . '</a>';
        }
        $toolsMenu = '<details class="w3-header-tools"><summary'
            . (isset($tools[$request]) || $request === 'araclar' ? ' class="is-current"' : '')
            . '><a class="w3-header-tools-link" href="' . qa_path_html('araclar') . '"' . ($request === 'araclar' ? ' aria-current="page"' : '') . '>Araçlar</a><span class="w3-header-tools-toggle" aria-hidden="true"></span></summary><nav aria-label="Web araçları"><a href="' . qa_path_html('araclar') . '">Tüm araçlar</a>' . $toolLinks . '</nav></details>';
        $links .= $toolsMenu;
        $this->output('<DIV CLASS="qam-main-nav-wrapper clearfix"><div id="icalan">');
        $this->logo();
        $this->output(
            '<nav class="w3-home-nav" aria-label="Ana menü">' . $links . '</nav>',
            '<details class="w3-header-mobile-nav"><summary aria-label="Ana menüyü aç"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path></svg></summary><nav aria-label="Mobil ana menü">' . $links . '<a href="' . qa_path_html('search') . '">Site içinde ara</a></nav></details>',
            '<form class="w3-home-search" role="search" action="' . qa_path_html('search') . '" method="get"><label class="qa-reader-only" for="w3-home-search-input">Site içinde ara</label><input id="w3-home-search-input" type="search" name="q" value="' . qa_html(is_string(qa_get('q')) ? qa_get('q') : '') . '" placeholder="Site içinde ara..."><button type="submit" aria-label="Ara"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="10.75" cy="10.75" r="6.75"></circle><path d="m16 16 4.5 4.5"></path></svg></button></form>'
        );
        if (qa_is_logged_in()) {
            $this->output('<details class="w3-header-account"><summary>Hesabım <span aria-hidden="true">⌄</span></summary><div><a href="' . qa_path_html('user/' . qa_get_logged_in_handle()) . '">Profilim</a><a href="' . qa_path_html('ask') . '">Haber Ekle</a>');
            if (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN) {
                $this->output('<a href="' . qa_path_html('admin') . '">Yönetim</a>');
            }
            if (!empty($this->content['navigation']['user']['logout']['url'])) {
                $this->output('<a href="' . $this->content['navigation']['user']['logout']['url'] . '">Çıkış Yap</a>');
            }
            $this->output('</div></details>');
        } else {
            $loginurl = !empty($this->content['navigation']['user']['login']['url'])
                ? $this->content['navigation']['user']['login']['url'] : qa_path_html('login');
            $this->output('<a class="w3-home-login" href="' . $loginurl . '">Giriş Yap</a>');
        }
        $this->output('</div></DIV><!-- END qam-main-nav-wrapper -->');
        if ($this->template !== 'users' && $this->template !== 'user' && $this->template !== 'questions' && $this->template !== 'hot' && strpos($this->template, 'user-') !== 0) { $this->nav('sub'); }
    }

    /**
     * Additional content
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global array $flatbox
     */
    function header()
    {
        global $flatbox;

        if (empty($this->content['w3_article_page']) && empty($this->content['w3_tool_page']) && empty($this->content['w3_info_page']) && empty($this->content['w3_about_page']) && !in_array($this->template, array('categories', 'tags', 'feedback', 'users', 'user', 'questions', 'hot'), true) && strpos($this->template, 'user-') !== 0) { $this->output($flatbox->header_custom_content); }
        $this->output('<main class="qa-main-wrapper" role="main">', '');
    }

    /**
     * The method has been overridden just to remove the '-' from the note
     * for the category page (notes). I know it is not good idea to override
     * this just for '-' it. But I did
     * intentionally to avoid such issue during the updates.
     *
     * @access public
     * @since FlatBox 1.0.0
     * @param type $navlink
     * @param type $class
     */
    public function nav_link($navlink, $class)
    {
        if (isset($navlink['url'])) {
            $this->output(
                    '<a href="' . $navlink['url'] . '" class="qa-' . $class . '-link' .
                    (@$navlink['selected'] ? (' qa-' . $class . '-selected') : '') .
                    (@$navlink['favorited'] ? (' qa-' . $class . '-favorited') : '') .
                    '"' . (strlen((string)($navlink['popup'] ?? '')) ? (' title="' . $navlink['popup'] . '"') : '') .
                    (isset($navlink['target']) ? (' target="' . $navlink['target'] . '"') : '') . '>' . $navlink['label'] .
                    '</a>'
            );
        } else {
            $this->output(
                    '<span class="qa-' . $class . '-nolink' . (@$navlink['selected'] ? (' qa-' . $class . '-selected') : '') .
                    (@$navlink['favorited'] ? (' qa-' . $class . '-favorited') : '') . '"' .
                    (strlen((string)($navlink['popup'] ?? '')) ? (' title="' . $navlink['popup'] . '"') : '') .
                    '>' . $navlink['label'] . '</span>'
            );
        }

        if (strlen((string)($navlink['note'] ?? ''))) {

            $qam_note_class = '';
            if (strpos($navlink['note'], '> -') !== false) {
                $qam_note_class = !empty($navlink['note']) ? ' qam-cat-note' : NULL;
            }

            // search and replace within the string
            $search  = array(' - <', '> - ');
            $replace = array(' <', '> ');
            $output  = $this->output('<span class="qa-' . $class . '-note ' . $qam_note_class . '">' . str_replace($search, $replace, $navlink['note']) . '</span>');
        }
    }

    /**
     * The method is overridden just to swap the <tt>main()</tt> and <tt>sidepanel()</tt>
     *
     * @access public
     * @since FlatBox 1.0.0
     */
    public function body_content()
    {
        $this->body_prefix();
        $this->notices();

        $this->qam_body_prefix();

        $extratags = isset($this->content['wrapper_tags']) ? $this->content['wrapper_tags'] : '';
        $this->output('<div class="qa-body-wrapper"' . $extratags . '>', '');

        $this->widgets('full', 'top');
        $this->header();
        $this->widgets('full', 'high');

        $this->main();
        $this->sidepanel();

        $this->widgets('full', 'low');
        $this->footer();
        $this->widgets('full', 'bottom');

        $this->output('</div> <!-- END body-wrapper -->');

        $this->body_suffix();
    }

    /**
     * Overridden to customize layout and styling
     *
     * @access public
     * @since FlatBox 1.0.0
     */
    function sidepanel()
    { // removes sidebar for user profile pages
        if (!empty($this->content['w3_article_page'])) {
            $this->output('<aside class="qa-sidepanel w3-article-sidebar" aria-label="Reklam ve haber keşfi">' . w3_article_sidebar_html($this->content) . '</aside>');
            return;
        }
        if (!empty($this->content['w3_tool_page']) || !empty($this->content['w3_info_page']) || !empty($this->content['w3_about_page'])) { return; }
        if ($this->template === 'questions' || $this->template === 'hot') {
            $this->output('<aside class="qa-sidepanel w3-news-sidebar" aria-label="Haberler ve topluluk">');
            $this->output(w3_home_popular_categories_html(isset($this->content['categoryids']) ? $this->content['categoryids'] : array()));
            $this->output(w3_news_sidebar_html());
            $this->output('</aside>');
            return;
        }
        if (qa_request() === 'whois-sorgulama' || in_array($this->template, array('categories', 'feedback', 'users'), true) || strpos($this->template, 'user-') === 0) { return; }
        if (($this->template != 'user') && (!qa_is_mobile_probably() || qa_request() === '')) {
            $this->output('<div class="qa-sidepanel">');

            if (qa_request() === '') {
                $this->output(w3_home_popular_categories_html(isset($this->content['categoryids']) ? $this->content['categoryids'] : array()));
                $this->output('
                  <section class="w3-home-newsletter" aria-labelledby="w3-newsletter-title">
                    <span class="w3-home-newsletter-icon" aria-hidden="true">&#9993;</span>
                    <h2 id="w3-newsletter-title">Teknoloji Gündemi<br>E-posta Kutunuzda</h2>
                    <p>En yeni haberler, rehberler ve ipuçları için bültenimize abone olun.</p>
                    <form action="' . qa_path_html('feedback') . '" method="post">
                      <label class="w3-footer-sr" for="w3-newsletter-email">E-posta adresiniz</label>
                      <input id="w3-newsletter-email" type="email" name="newsletter_email" placeholder="E-posta adresiniz" autocomplete="email" maxlength="254" required>
                      <input type="hidden" name="w3_newsletter" value="1">
                      <input type="hidden" name="newsletter_code" value="' . qa_html(qa_get_form_security_code('w3_newsletter')) . '">
                      <button type="submit">Abonelik talebine devam et</button>
                    </form>
                    <small>Talebinizi iletişim formunda tamamlayın.</small>
                  </section>
                  ' . $this->w3_home_ad_html('sidebar') . '
                  ' . w3_home_popular_posts_html() . '
                  <a class="w3-home-guide" href="./dersler"><span aria-hidden="true">&#128214;</span><strong>Teknoloji Rehberleri</strong><em>Adım adım anlatımlar, proje fikirleri ve daha fazlası.</em><b>Rehberlere Göz At &#8594;</b></a>
                ');
            } else {
                $this->w3_subpage_sidebar();
            }
            $this->output('</div>', '');
        }
    }

    /** Keep configured widget positions and every other module's native rendering. */
    public function widgets($region, $place)
    {
        $widgets = isset($this->content['widgets'][$region][$place]) ? $this->content['widgets'][$region][$place] : array();
        if (!is_array($widgets) || !$widgets) { return; }
        $this->output('<div class="qa-widgets-' . $region . ' qa-widgets-' . $region . '-' . $place . '">');
        foreach ($widgets as $module) {
            $stats = $module instanceof qa_activity_count;
            $this->output('<div class="qa-widget-' . $region . ' qa-widget-' . $region . '-' . $place . ($stats ? ' w3-site-stats-widget' : '') . '">');
            if ($stats) {
                $this->output(w3_site_stats_html());
            } else {
                $module->output_widget($region, $place, $this, $this->template, $this->request, $this->content);
            }
            $this->output('</div>');
        }
        $this->output('</div>', '');
    }

    /** Shared desktop/mobile sidebar: the legacy category list is no longer rendered. */
    private function w3_subpage_sidebar()
    {
        $this->output(w3_home_popular_categories_html(isset($this->content['categoryids']) ? $this->content['categoryids'] : array()));
        $this->widgets('side', 'top');
        $this->sidebar();
        $this->widgets('side', 'high');
        $this->widgets('side', 'low');
        $this->output_raw(@$this->content['sidepanel']);
        $this->feed();
        $this->widgets('side', 'bottom');
    }

    /**
     * To provide various color option
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global array $flatbox
     */
    public function sidebar()
    {
        global $flatbox;

        $sidebar = @$this->content['sidebar'];

        if (!empty($sidebar)) {
            $this->output('<div class="qa-sidebar ' . $flatbox->welcome_widget_color . '">');
            $this->output_raw($sidebar);
            $this->output('</div>', '');
        }
    }

    /**
     * To add close icon
     *
     * @access public
     * @since FlatBox 1.0.0
     * @param array $q_item
     * @global array $flatbox
     */
    public function q_item_title($q_item)
    {
        global $flatbox;

        $this->output(
                '<h2 class="qa-q-item-title">',
                empty($q_item['closed']) ? '' : '<img src="' . $this->rooturl . $flatbox->icon_url . '/closed-q-list.png" class="qam-q-list-close-icon" alt="question-closed" title="' . qam_lang('closed_question') . '" />',
                '<a itemprop="url" href="' . $q_item['url'] . '"><span itemprop="headline">' . $q_item['title'] . '</span></a>',
                '</h2>'
        );
    }

    /**
     * Present list records as news articles while retaining Q2A voting data.
     */
    public function q_item_main($q_item)
    {
        $imageurl = '';
        $hascontent = isset($q_item['raw']) && array_key_exists('content', $q_item['raw']);
        $rawcontent = $hascontent ? (string) $q_item['raw']['content'] : '';
        if (!$hascontent && !empty($q_item['raw']['postid'])) {
            $rawcontent = qa_db_read_one_value(qa_db_query_sub(
                'SELECT content FROM ^posts WHERE postid=#', $q_item['raw']['postid']
            ), true);
        }

        if ($rawcontent && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $rawcontent, $match)) {
            $imageurl = $match[1];
        } elseif ($rawcontent && preg_match('/!\[[^\]]*\]\(([^\s\)]+)(?:\s+[^\)]*)?\)/', $rawcontent, $match)) {
            $imageurl = $match[1];
        } elseif ($rawcontent && preg_match('/!\[[^\]]*\]\[([^\]]+)\]/', $rawcontent, $reference)) {
            $referenceid = preg_quote($reference[1], '/');
            if (preg_match('/^\s*\[' . $referenceid . '\]:\s*(\S+)/m', $rawcontent, $match)) {
                $imageurl = $match[1];
            }
        }

        if ($imageurl) {
            // Stored HTML can contain &amp;; decode once before qa_html() escapes it for the src attribute.
            $imageurl = w3_site_image_url(html_entity_decode($imageurl, ENT_QUOTES, 'UTF-8'));
        }

        $excerpt_source = preg_replace('/<img[^>]*>/i', ' ', $rawcontent);
        $excerpt_source = preg_replace('/!\[[^\]]*\]\([^\)]*\)/', ' ', $excerpt_source);
        $excerpt_source = preg_replace('/\[[^\]]*\]\([^\)]*\)/', ' ', $excerpt_source);
        $excerpt = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($excerpt_source), ENT_QUOTES, 'UTF-8')));
        if (function_exists('mb_substr') && mb_strlen($excerpt, 'UTF-8') > 170) {
            $excerpt = mb_substr($excerpt, 0, 170, 'UTF-8') . '…';
        }

        $this->output('<div class="qa-q-item-main qa-news-item-main">');

        $fallback_image = w3_category_cover_url(isset($q_item['raw']['categoryid']) ? $q_item['raw']['categoryid'] : null, isset($q_item['raw']['title']) ? $q_item['raw']['title'] : strip_tags($q_item['title']));
        $this->output(
            '<a class="qa-news-item-image" href="', $q_item['url'], '">',
            '<img src="', qa_html($imageurl ?: $fallback_image), '" alt="', qa_html(isset($q_item['raw']['title']) ? $q_item['raw']['title'] : strip_tags($q_item['title'])), '" loading="lazy" decoding="async" onerror="', qa_html('this.onerror=null;this.src=' . qa_js($fallback_image) . ';'), '">',
            '</a>'
        );

        $this->output('<div class="qa-news-item-body">');
        $this->output('<div class="qa-news-item-meta">');
        if (!empty($q_item['where'])) {
            $this->post_meta_where($q_item, 'qa-q-item');
            $this->output('<span class="qa-news-item-meta-separator" aria-hidden="true">&bull;</span>');
        } elseif (qa_request() === '') {
            $this->output('<span class="w3-news-category">Teknoloji</span><span class="qa-news-item-meta-separator" aria-hidden="true">&bull;</span>');
        }
        $this->post_meta_when($q_item, 'qa-q-item');
        $this->output('</div>');
        $this->q_item_title($q_item);
        if (qa_request() === '' || $this->template === 'questions' || $this->template === 'hot') {
            if ($excerpt !== '') {
                $this->output('<p class="w3-news-excerpt">', qa_html($excerpt), '</p>');
            }
            if (qa_request() === '') { $this->output('<button class="w3-news-save" type="button" aria-label="Haberi kaydet"><span aria-hidden="true">&#9825;</span> Kaydet</button>'); }
            else { $this->q_item_buttons($q_item); }
        } else {
            $this->q_item_content($q_item);
            $this->q_item_buttons($q_item);
        }
        $this->output('</div>');
        $this->output('</div>');
    }
    /**
     * Semantic article shell for the portal's news lists.
     */
    public function q_list_item($q_item)
    {
        $this->output('<article class="qa-q-list-item qa-news-list-item' . rtrim(' ' . ($q_item['classes'] ?? '')) . '" ' . ($q_item['tags'] ?? '') . ' itemscope itemtype="https://schema.org/NewsArticle">');
        $this->q_item_main($q_item);
        $this->q_item_stats($q_item);
        if ($this->template === 'questions' || $this->template === 'hot') {
            $this->output('<a class="w3-news-open" href="' . $q_item['url'] . '" aria-label="' . qa_html(strip_tags($q_item['title']) . ' — Haberi oku') . '">' . w3_news_icon('arrow') . '</a>');
        }
        $this->q_item_clear();
        $this->output('</article>');
    }
    /**
     * To add RSS feeds icon and closed icon for closed questions
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global array $flatbox
     */
    public function favorite()
    {
        if (($this->template === 'questions' || $this->template === 'hot') && !empty($this->content['categoryids'])) {
            // Keep the native form/input/CSRF/Ajax behaviour inside a persistent new icon wrapper.
            // Ajax replaces only .qa-favoriting's inner HTML, not the decorative code icon.
            $this->output('<span class="w3-news-category-badge">' . w3_home_section_icon('web-yazilim'));
            parent::favorite();
            $this->output('</span>');
            return;
        }
        parent::favorite();
    }

    public function page_title_error()
    {
        if (!empty($this->content['w3_article_page'])) {
            $this->output(w3_article_header_html($this->content));
            if (!empty($this->content['success'])) { $this->success($this->content['success']); }
            if (!empty($this->content['error'])) { $this->error($this->content['error']); }
            return;
        }
        if (!empty($this->content['w3_tool_page']) || !empty($this->content['w3_info_page']) || !empty($this->content['w3_about_page'])) { return; }
        if ($this->template === 'search') {
            if (empty($this->content['title'])) { $this->content['title'] = 'Site içinde ara'; }
            qa_html_theme_base::page_title_error();
            $query = qa_get('q');
            $this->output('<form class="w3-page-search" role="search" action="' . qa_path_html('search') . '" method="get"><label class="w3-footer-sr" for="w3-page-search-input">Haber ve rehberlerde ara</label><input id="w3-page-search-input" type="search" name="q" value="' . qa_html(is_string($query) ? $query : '') . '" placeholder="Haber veya konu ara…" required><button type="submit">Ara</button></form>');
            return;
        }
        if ($this->template === 'questions' || $this->template === 'hot') {
            if ($this->template === 'hot' && empty($this->content['navigation']['sub'])) {
                require_once QA_INCLUDE_DIR . 'app/q-list.php';
                $this->content['navigation']['sub'] = qa_qs_sub_navigation('hot', QA_ALLOW_UNINDEXED_QUERIES ? qa_request_parts(1) : array());
                $this->content['navigation']['sub']['hot']['url'] = qa_path_html(qa_request());
            }
            $rss = !empty($this->content['feed']['url']) ? '<a class="w3-news-heading-rss" href="' . $this->content['feed']['url'] . '" aria-label="Haberlerin RSS akışı">' . w3_news_icon('rss') . '</a>' : '';
            $this->output('<div class="w3-news-heading"><div class="w3-news-hero"><div class="w3-news-hero-intro"><nav class="w3-news-breadcrumb" aria-label="İçerik yolu"><a href="' . qa_path_html('') . '">' . w3_news_icon('home') . ' Anasayfa</a><span aria-hidden="true">›</span><a href="' . qa_path_html('questions') . '">Haberler</a>' . $rss . '</nav><div class="w3-news-title">');
            qa_html_theme_base::page_title_error();
            $this->output('</div><p>Teknoloji dünyasından haberler, güncel gelişmeler, yeni ürünler ve rehberler.</p>' . w3_news_hero_art() . '</div>');
            $this->output(w3_news_category_topics_html($this->content) . '</div>');
            if (!empty($this->content['navigation']['sub'])) {
                $originalnav = $this->content['navigation']['sub'];
                $this->output('<nav class="w3-news-tabs" aria-label="Haberleri sırala">');
                foreach (array('recent' => 'Yeniler', 'hot' => 'Popüler', 'votes' => 'En çok oylanan', 'answers' => 'En çok yorumlanan', 'views' => 'En çok okunan') as $key => $label) {
                    if (isset($this->content['navigation']['sub'][$key])) { $this->content['navigation']['sub'][$key]['label'] = w3_news_icon($key) . '<span>' . qa_html($label) . '</span>'; }
                }
                $this->nav('sub');
                $this->content['navigation']['sub'] = $originalnav;
                $this->output('</nav>');
            }
            $this->output('</div>');
            return;
        }
        if ($this->template === 'users') {
            $count = number_format((int) qa_opt('cache_userpointscount'), 0, ',', '.');
            $this->output('<div class="w3-members-heading"><div class="w3-members-intro"><span class="w3-members-symbol" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="7" r="3"></circle><circle cx="4.5" cy="8.5" r="2.5"></circle><circle cx="19.5" cy="8.5" r="2.5"></circle><path d="M7 20v-4a5 5 0 0 1 10 0v4H7ZM5.2 12.1A5.5 5.5 0 0 0 2 12.5 4 4 0 0 0 0 16v3h5v-3c0-1.4.4-2.8 1.1-3.9h-.9ZM18.8 12.1a5.5 5.5 0 0 1 3.2.4 4 4 0 0 1 2 3.5v3h-5v-3c0-1.4-.4-2.8-1.1-3.9h.9Z"></path></svg></span><div class="w3-members-copy"><div class="w3-members-title"><h1>Üyeler</h1><span class="w3-members-total">' . qa_html($count) . ' üye</span></div><p>' . (isset($this->content['title']) ? $this->content['title'] : 'W3Uzman topluluğunu keşfedin.') . '</p></div></div>');
            if (!empty($this->content['navigation']['sub'])) {
                $originalnav = $this->content['navigation']['sub'];
                if (isset($this->content['navigation']['sub']['users$'])) {
                    $this->content['navigation']['sub']['users$']['label'] = '<svg class="w3-members-trophy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h10v6a5 5 0 0 1-10 0V3ZM7 5H3v3a4 4 0 0 0 5 4M17 5h4v3a4 4 0 0 1-5 4M12 14v6M8 21h8"></path></svg><span>' . $this->content['navigation']['sub']['users$']['label'] . '</span>';
                }
                $this->output('<nav class="w3-members-tabs" aria-label="Üye listeleri">');
                $this->nav('sub');
                $this->output('</nav>');
                $this->content['navigation']['sub'] = $originalnav;
            }
            $this->output('</div>');
            if (!empty($this->content['error'])) { $this->error($this->content['error']); }
            return;
        }
        if (!empty($this->content['w3_profile_redesign'])) {
            ob_start();
            if (isset($this->content['favorite'])) {
                $this->output('<form class="w3-profile-favorite" ' . $this->content['favorite']['form_tags'] . '>');
                $this->favorite();
                $this->form_hidden_elements(isset($this->content['favorite']['form_hidden']) ? $this->content['favorite']['form_hidden'] : null);
                $this->output('</form>');
            }
            $favoritehtml = ob_get_clean();
            $this->output(w3_profile_hero_html($this->content, $favoritehtml));
            $this->output('<nav class="w3-profile-tabs w3-profile-main-tabs" aria-label="Profil bölümleri">');
            $originalnav = isset($this->content['navigation']['sub']) ? $this->content['navigation']['sub'] : array();
            $this->content['navigation']['sub'] = w3_profile_navigation($originalnav);
            $this->nav('sub');
            $this->content['navigation']['sub'] = $originalnav;
            $this->output('</nav>');
            if (!empty($this->content['success'])) { $this->success($this->content['success']); }
            return;
        }
        if ($this->template === 'user' || strpos($this->template, 'user-') === 0) {
            $this->output('<div class="w3-profile-heading"><div class="w3-profile-identity">');
            $originaltitle = isset($this->content['title']) ? $this->content['title'] : '';
            if ($this->template === 'user') { $this->content['title'] = qa_html((string) qa_request_part(1)); }
            qa_html_theme_base::page_title_error();
            $this->content['title'] = $originaltitle;
            $this->output('<p>W3Uzman topluluk profili</p></div><div class="w3-profile-actions"><nav class="w3-profile-tabs" aria-label="Profil bölümleri">');
            $originalnav = isset($this->content['navigation']['sub']) ? $this->content['navigation']['sub'] : array();
            $this->content['navigation']['sub'] = w3_profile_navigation($originalnav);
            $this->nav('sub');
            $this->content['navigation']['sub'] = $originalnav;
            $this->output('</nav><a class="w3-profile-back" href="' . qa_path_html('users') . '">Tüm üyeler <span aria-hidden="true">→</span></a></div></div>');
            return;
        }
        if ($this->template === 'feedback') {
            $this->output('<nav class="w3-contact-breadcrumb" aria-label="İçerik yolu"><a href="' . qa_path_html('') . '">Anasayfa</a>' . $this->w3_contact_icon('chevron') . '<span aria-current="page">Bize Ulaşın</span></nav>');
            $this->output('<div class="w3-contact-heading"><div><h1>Bize Ulaşın</h1><p>Görüşleriniz, önerileriniz ve iş birliği talepleriniz bizim için değerli.<br> W3Uzman ekibine dilediğiniz konuda kolayca ulaşabilirsiniz.</p></div>' . $this->w3_contact_illustration() . '</div>');
            if (!empty($this->content['error'])) { $this->output('<div class="w3-contact-alert" role="alert">' . $this->content['error'] . '</div>'); }
            return;
        }
        if ($this->template === 'tags') {
            $count = number_format((int) qa_opt('cache_tagcount'), 0, ',', '.');
            $this->output('<div class="w3-tags-heading"><div><h1>Etiketler</h1><p>İlgini çeken konuyu seç, ilgili haberleri keşfet.</p></div><span class="w3-tags-total">' . qa_html($count) . ' etiket</span></div>');
            return;
        }
        if (qa_request() === '') {
            $this->output(w3_home_shortcuts_html(), $this->w3_home_ad_html('content'));
            $this->output('<div class="qa-main-heading"><h1>');
            $this->title();
            $this->output('</h1><a class="w3-home-all-news" href="' . qa_path_html('questions') . '">Tüm Haberler <span aria-hidden="true">→</span></a></div>');
            return;
        }
        if ($this->template === 'categories') {
            $this->output($this->content['w3_categories_breadcrumb']);
            $this->output('<div class="w3-cat-page-heading"><div class="w3-cat-title-block">');
            qa_html_theme_base::page_title_error();
            $this->output('<p class="w3-cat-intro">' . qa_html($this->content['w3_categories_intro']) . '</p><span class="w3-cat-reader">' . qa_html($this->content['w3_categories_summary']) . '</span></div>' . $this->content['w3_categories_toolbar'] . '</div>');
            return;
        }
        if (qa_request() !== 'whois-sorgulama') {
            qa_html_theme_base::page_title_error();
            return;
        }
        $this->output('<div class="w3-whois-page-heading">');
        qa_html_theme_base::page_title_error();
        if (!empty($this->content['whois_intro'])) {
            $this->output('<p class="w3-whois-intro">' . qa_html($this->content['whois_intro']) . '</p>');
        }
        $this->output('</div>');
    }

    /** Keep native form security, CAPTCHA and field errors inside the contact layout. */
    public function form($form)
    {
        if ($this->template === 'user' && isset($this->context['part'])) {
            if ($this->context['part'] === 'form_profile') {
                if (!empty($this->content['w3_profile_redesign'])) {
                    $this->output('<div class="w3-profile-card-heading"><h2 class="w3-profile-card-title">' . w3_profile_icon('user') . 'Profil Bilgileri</h2>');
                    $form['tags'] .= ' id="w3-profile-form"';
                    if (isset($form['buttons']['edit'])) {
                        $edit = $form['buttons']['edit'];
                        $edit['tags'] .= ' form="w3-profile-form"';
                        $this->form_button_data($edit, 'edit', 'wide');
                        unset($form['buttons']['edit']);
                    }
                    $this->output('</div>');
                    unset($form['fields']['avatar']);
                } else { $this->output('<h2 class="w3-profile-card-title">Profil bilgileri</h2>'); }
            }
            if ($this->context['part'] === 'form_activity') { $form['title'] = '<span id="activity">' . (!empty($this->content['w3_profile_redesign']) ? w3_profile_icon('chart') : '') . 'Topluluk İstatistikleri</span>'; }
        }
        if ($this->template !== 'feedback') { parent::form($form); return; }
        $contactemail = qa_html(isset($this->content['w3_contact_email']) ? $this->content['w3_contact_email'] : 'bilgi@w3uzman.com');
        $this->output('<div class="w3-contact-layout"><section class="w3-contact-card"><div class="w3-contact-form-heading"><span class="w3-contact-icon w3-contact-icon-gold">' . $this->w3_contact_icon('mail') . '</span><div><h2>Mesajınızı gönderin</h2><p>Size dönüş yapabilmemiz için formu eksiksiz doldurun.</p></div></div>');
        if (!empty($this->content['w3_newsletter_prefill'])) {
            $this->output('<p class="w3-newsletter-notice" role="status">Abonelik talebiniz hazırlandı, henüz gönderilmedi. Adınızı ve güvenlik kontrolünü tamamlayıp <strong>Mesajı gönder</strong> düğmesine basın.</p>');
        }
        parent::form($form);
        $this->output('</section><aside class="w3-contact-help" aria-label="İletişim hakkında"><section class="w3-contact-fast"><span class="w3-contact-icon w3-contact-icon-gold">' . $this->w3_contact_icon('headset') . '</span><div><h2>Daha hızlı iletişim</h2><p>Yazmadan önce sık sorulan sorulara göz atın. Doğrudan e-posta da gönderebilirsiniz:<br><a href="mailto:' . $contactemail . '">' . $contactemail . '</a></p><details class="w3-contact-faq"><summary>SSS’yi görüntüle <span aria-hidden="true">→</span></summary><div><h3>Yanıt nereden gelecek?</h3><p>Gerekli durumlarda formda belirttiğiniz e-posta adresinden size ulaşırız.</p><h3>Bir içerikte hata mı var?</h3><p>İçerik düzeltme konusunu seçin; ilgili bağlantıyı ve düzeltilmesi gereken bilgiyi paylaşın.</p><h3>Teknik sorunu nasıl bildirebilirim?</h3><p>Sayfa bağlantısını, cihazınızı ve karşılaştığınız sorunu mesajınıza ekleyin.</p></div></details></div></section>');
        $this->output('<section class="w3-contact-topics"><h2>Hangi konularda ulaşabilirsiniz?</h2><ul>');
        $topics = array(
            array('content', 'file', 'blue', 'İçerik ve düzeltme talepleri', 'Eksik ya da hatalı bilgiler'),
            array('business', 'people', 'green', 'İş birliği ve reklam', 'Tanıtım, reklam ve sponsorluk'),
            array('technical', 'bulb', 'purple', 'Teknik destek', 'Site ile ilgili teknik sorunlar'),
            array('general', 'star', 'orange', 'Öneri ve geri bildirim', 'Görüş, öneri ve yeni fikirler'),
        );
        foreach ($topics as $topic) {
            $this->output('<li><button type="button" data-contact-topic="' . $topic[0] . '"><span class="w3-contact-icon w3-contact-icon-' . $topic[2] . '">' . $this->w3_contact_icon($topic[1]) . '</span><span><strong>' . $topic[3] . '</strong><span>' . $topic[4] . '</span></span>' . $this->w3_contact_icon('chevron') . '</button></li>');
        }
        $this->output('</ul></section><section class="w3-contact-privacy"><span class="w3-contact-icon w3-contact-icon-blue">' . $this->w3_contact_icon('shield') . '</span><div><h2>Gizlilik ve güvenlik</h2><p>Şifre, ödeme bilgisi veya hassas kişisel verilerinizi mesajınıza eklemeyin.</p></div></section></aside></div>');
    }

    public function message_list_and_form($list)
    {
        if (!empty($this->content['w3_profile_redesign']) && !empty($list['title'])) { $list['title'] = w3_profile_icon('comment') . $list['title']; }
        parent::message_list_and_form($list);
    }

    public function message_list_form($list)
    {
        if (!empty($this->content['w3_profile_redesign']) && !empty($list['form']['fields']['message'])) {
            $list['form']['fields']['message']['tags'] .= ' placeholder="Bir mesaj yazın…" aria-label="Duvar mesajınız"';
            $avatar = '';
            if (qa_get_logged_in_userid() !== null && !QA_FINAL_EXTERNAL_USERS) {
                $avatar = qa_get_user_avatar_html(qa_get_logged_in_flags(), qa_get_logged_in_email(), qa_get_logged_in_handle(), qa_get_logged_in_user_field('avatarblobid'), qa_get_logged_in_user_field('avatarwidth'), qa_get_logged_in_user_field('avatarheight'), 40, true);
            }
            $this->output('<div class="w3-profile-compose"><span class="w3-profile-compose-avatar" aria-hidden="true">' . (!empty($avatar) ? w3_safe_avatar_html($avatar) : w3_profile_icon('user')) . '</span>');
            parent::message_list_form($list);
            $this->output('</div>');
            return;
        }
        parent::message_list_form($list);
    }

    public function message_buttons($item)
    {
        if (!empty($this->content['w3_profile_redesign']) && !empty($item['form'])) {
            $this->output('<details class="w3-profile-message-actions"><summary aria-label="Mesaj işlemleri">⋮</summary>');
            parent::message_buttons($item);
            $this->output('</details>');
            return;
        }
        parent::message_buttons($item);
    }

    /** Small local vector details; no external icon fonts or services. */
    private function w3_contact_icon($name)
    {
        $paths = array(
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 6 9 7 9-7"></path>',
            'headset' => '<path d="M4 14v-3a8 8 0 0 1 16 0v3M20 16v2a3 3 0 0 1-3 3h-3"></path><rect x="2" y="11" width="4" height="7" rx="2"></rect><rect x="18" y="11" width="4" height="7" rx="2"></rect>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 12h8M8 16h8"></path>',
            'people' => '<circle cx="9" cy="7" r="3"></circle><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 4v3"></path>',
            'bulb' => '<path d="M9 18h6M9 21h6M8 14a7 7 0 1 1 8 0c-1 1-1 2-1 4H9c0-2 0-3-1-4z"></path>',
            'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"></path>',
            'shield' => '<path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6z"></path>',
            'chevron' => '<path d="m9 6 6 6-6 6"></path>',
        );
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
    }

    private function w3_contact_illustration()
    {
        return '<svg class="w3-contact-illustration" viewBox="0 0 400 190" fill="none" aria-hidden="true" focusable="false"><defs><linearGradient id="w3-envelope" x1="232" y1="75" x2="280" y2="175" gradientUnits="userSpaceOnUse"><stop stop-color="#fff"></stop><stop offset="1" stop-color="#dce1eb"></stop></linearGradient><linearGradient id="w3-letter" x1="200" y1="85" x2="260" y2="145" gradientUnits="userSpaceOnUse"><stop stop-color="#fff9df"></stop><stop offset="1" stop-color="#ffe7a0"></stop></linearGradient><radialGradient id="w3-glow"><stop stop-color="#fff1b6" stop-opacity=".75"></stop><stop offset="1" stop-color="#fff7d8" stop-opacity="0"></stop></radialGradient><filter id="w3-soft-shadow" x="-30%" y="-30%" width="160%" height="180%"><feDropShadow dx="0" dy="7" stdDeviation="7" flood-color="#7b869e" flood-opacity=".13"></feDropShadow></filter></defs><ellipse cx="244" cy="112" rx="154" ry="80" fill="url(#w3-glow)"></ellipse><path d="M312 161c72-13 68-78 22-73-36 4-16 43 12 26" stroke="#ffd661" stroke-dasharray="3 5" stroke-linecap="round"></path><g filter="url(#w3-soft-shadow)"><path d="M198 29c-31 0-55 19-55 43 0 14 8 26 22 34l-7 21 29-13 11 1c31 0 55-19 55-43s-24-43-55-43z" fill="#fff"></path><path d="M172 62h50M172 79h27" stroke="#b7bdca" stroke-width="7" stroke-linecap="round"></path><rect x="214" y="86" width="108" height="78" rx="12" fill="url(#w3-envelope)"></rect><path d="m218 91 43 34a12 12 0 0 0 15 0l42-34" fill="url(#w3-letter)"></path><path d="m218 158 33-29m66 29-33-29" stroke="#cbd2df" stroke-opacity=".65" stroke-width="1.5"></path></g><path d="m337 45-28-11 55-16-17 45-10-18z" fill="#ffcd32"></path><path d="m337 45 27-27" stroke="#fff6ce" stroke-width="2"></path><path d="m303 57-10 10m-23-28-3 11m39 38 12-3m-206 30 7 10" stroke="#ffdc62" stroke-width="5" stroke-linecap="round"></path></svg>';
    }

    /** Compact only static profile content; native editable fields and security remain untouched. */
    public function form_field_rows($form, $columns, $field)
    {
        // Native table labels are not linked to their inputs; connect them without changing submissions.
        $type = isset($field['type']) ? $field['type'] : 'text';
        if (!empty($field['label']) && !empty($field['tags']) && in_array($type, array('text', 'textarea', 'password', 'number', 'select', 'file'), true) && stripos($field['label'], '<label') === false) {
            if (preg_match('/\bid=["\']([^"\']+)["\']/', $field['tags'], $match)) {
                $inputid = $match[1];
            } else {
                static $fieldnumber = 0;
                $inputid = 'w3-form-field-' . (++$fieldnumber);
                $field['tags'] .= ' id="' . $inputid . '"';
            }
            $field['label'] = '<label for="' . qa_html($inputid) . '">' . $field['label'] . '</label>';
            if (stripos($field['tags'], 'autocomplete=') === false && preg_match('/\bname=["\'](emailhandle|email|password)["\']/', $field['tags'], $match)) {
                $autocomplete = $match[1] === 'password' ? ($this->template === 'login' ? 'current-password' : 'new-password') : ($match[1] === 'email' ? 'email' : 'username');
                $field['tags'] .= ' autocomplete="' . $autocomplete . '"';
            }
        }
        if ($this->template === 'user' && isset($this->context['part']) && $this->context['part'] === 'form_profile' && isset($field['type']) && $field['type'] === 'static') {
            if ((!isset($field['value']) || trim(strip_tags((string) $field['value'])) === '') && empty($field['note']) && empty($field['error'])) { return; }
            if (isset($field['id']) && $field['id'] === 'permits') {
                $field['value'] = '<details class="w3-profile-permits"><summary>Yetkileri göster</summary><div>' . $field['value'] . '</div></details>';
            }
        }
        parent::form_field_rows($form, $columns, $field);
    }

    /** Compact tag and member directories; preserve prepared links and pagination. */
    public function ranking($ranking)
    {
        if ($this->template === 'users' && isset($ranking['type']) && $ranking['type'] === 'users') {
            $sort = isset($ranking['sort']) ? $ranking['sort'] : 'points';
            $caption = $sort === 'date' ? 'katılım' : ($sort === 'level' ? 'yetki' : 'puan');
            $start = qa_get_start();
            $this->part_title($ranking);
            $this->output('<ul class="w3-members-grid" aria-label="Topluluk üyeleri">');
            foreach ($ranking['items'] as $index => $item) {
                $rank = $start + $index + 1;
                $podium = $sort === 'points' && $rank <= 3;
                $cardclass = 'w3-member-card' . ($podium ? ' w3-member-podium w3-member-place-' . $rank : '') . ($podium && $rank === 1 ? ' w3-member-first' : '');
                $name = html_entity_decode(strip_tags($item['label']), ENT_QUOTES, 'UTF-8');
                $avatar = !empty($item['avatar']) ? w3_safe_avatar_html($item['avatar']) : '<span class="w3-member-initial">' . qa_html(qa_substr($name, 0, 1)) . '</span>';
                $level = isset($item['raw']['level']) ? qa_user_level_string((int) $item['raw']['level']) : 'Topluluk üyesi';
                $this->output('<li class="' . $cardclass . '"><span class="w3-member-rank">' . $rank . '</span><div class="w3-member-avatar">' . $avatar . '</div><div class="w3-member-info">' . $item['label'] . '<span class="w3-member-caption">' . qa_html($level) . '</span></div><span class="w3-member-score"><strong>' . (isset($item['score']) ? $item['score'] : '') . '</strong><small>' . $caption . '</small></span></li>');
            }
            $this->output('</ul>');
            if (empty($ranking['items'])) { $this->output('<p class="w3-members-empty">Bu listede henüz gösterilecek üye bulunmuyor.</p>'); }
            $this->part_footer($ranking);
            return;
        }
        if ($this->template !== 'tags' || !isset($ranking['type']) || $ranking['type'] !== 'tags') {
            parent::ranking($ranking);
            return;
        }
        $this->part_title($ranking);
        $this->output('<section class="w3-tags-panel" aria-label="Popüler etiketler"><div class="w3-tags-panel-heading"><h2>Popüler etiketler</h2><span>Haber sayısına göre</span></div>');
        if (!empty($ranking['items'])) {
            $this->output('<ul class="w3-tags-grid">');
            foreach ($ranking['items'] as $item) {
                $this->output('<li class="w3-tag-card"><span class="w3-tag-icon" aria-hidden="true">#</span>' . $item['label'] . '<span class="w3-tag-count" aria-label="' . qa_html(strip_tags($item['count'])) . ' haber">' . $item['count'] . '</span></li>');
            }
            $this->output('</ul>');
        } else {
            $this->output('<p class="w3-tags-empty">Henüz gösterilecek etiket bulunmuyor.</p>');
        }
        $this->output('</section>');
        $this->part_footer($ranking);
    }

    /** Keep native page URLs; only simplify directory pagination controls. */
    public function page_links_list($page_items)
    {
        if ($this->template === 'users' && !empty($page_items)) {
            $types = array_column($page_items, 'type');
            if (!in_array('prev', $types, true)) { array_unshift($page_items, array('type' => 'w3-prev-disabled', 'ellipsis' => false)); }
            if (!in_array('next', $types, true)) { $page_items[] = array('type' => 'w3-next-disabled', 'ellipsis' => false); }
        }
        parent::page_links_list($page_items);
    }

    public function page_link_content($page_link)
    {
        if ($this->template === 'users') {
            $type = isset($page_link['type']) ? $page_link['type'] : '';
            if ($type === 'prev' || $type === 'next' || $type === 'w3-prev-disabled' || $type === 'w3-next-disabled') {
                $previous = $type === 'prev' || $type === 'w3-prev-disabled';
                $label = $previous ? 'Önceki sayfa' : 'Sonraki sayfa';
                $icon = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . ($previous ? 'm12 6-4 4 4 4' : 'm8 6 4 4-4 4') . '"></path></svg>';
                if ($type === 'prev' || $type === 'next') {
                    $this->output('<a href="' . $page_link['url'] . '" class="qa-page-' . $type . '" aria-label="' . $label . '">' . $icon . '</a>');
                } else {
                    $this->output('<span class="w3-page-disabled" aria-disabled="true" aria-label="' . $label . '">' . $icon . '</span>');
                }
                return;
            }
            if ($type === 'this') {
                $this->output('<span class="qa-page-selected" aria-current="page">' . $page_link['label'] . '</span>');
                return;
            }
        }
        parent::page_link_content($page_link);
    }

    public function title()
    {
        global $flatbox;

        $q_view = @$this->content['q_view'];

        // link title where appropriate
        $url = isset($q_view['url']) ? $q_view['url'] : false;

        // add closed image
        $closed = (!empty($q_view['closed']) ?
                        '<img src="' . $this->rooturl . $flatbox->icon_url . '/closed-q-view.png" class="qam-q-view-close-icon" alt="question-closed" width="24" height="24" title="' . qam_lang('closed_question') . '" />' : NULL );

        if (isset($this->content['title'])) {
            $this->output(
                    $closed, $url ? '<a href="' . $url . '">' : '', $this->content['title'], $url ? '</a>' : ''
            );
        }

        $feed = @$this->content['feed'];

        if (!empty($feed) && $this->template !== 'questions' && $this->template !== 'hot') {
            $this->output('<a href="' . $feed['url'] . '" title="' . @$feed['label'] . '"><i class="icon-rss qam-title-rss"></i></a>');
        }
    }

    /**
     * To add view counter
     *
     * @access public
     * @since FlatBox 1.0.0
     * @param array $q_item
     */

/* K'lı Sayı Basamağı Görünümü | Bu Fonksiyon Gelen Sayıyı Yuvarlayıp Sonuna İlgili Harfi Ekliyor */
     function short_num($num, $precision = 1) {
               if ($num >= 1000 && $num < 1000000) {
                $n_format = number_format($num/1000,$precision).'K';
                } else if ($num >= 1000000 && $num < 1000000000) {
                $n_format = number_format($num/1000000,$precision).'M';
               } else if ($num >= 1000000000) {
               $n_format=number_format($num/1000000000,$precision).'T';
               } else {
               $n_format = $num;
                }
              return $n_format;
            }
/* K'lı Sayı Basamağı Görünümü Fonksiyon SON */

    function q_item_stats($q_item)
    { // add view count to question list
        $this->output('<div class="qa-q-item-stats">');

        $this->voting($q_item);
        $this->a_count($q_item);
        //  qa_html_theme_base::view_count($q_item);    //bu kod alttaki k'lı basamak görünümünden sonra iptal edildi
        
        /* Harfli Basamak Görünümü  */
        $this->output(
                '<SPAN CLASS="qa-view-count"><span class="qa-view-count-data">',
                array_key_exists('views_raw', $q_item) ? $this->short_num($q_item['views_raw']) : '0',
                '</span><span class="qa-view-count-pad"> Görünüm</span></SPAN>'
            );
        /* Harfli Basamak Görünümü  */


        $this->output('</div>');
    }

    /**
     * Prevent display view counter on usual place
     *
     * @access public
     * @since FlatBox 1.0.0
     * @param type $q_item
     */
    function view_count($q_item)
    { // Prevent display view counter on usual place
    }

    /**
     * To add view counter
     *
     * @access public
     * @since FlatBox 1.0.0
     * @param type $q_view
     */
    public function q_view_stats($q_view)
    {
        $this->output('<div class="qa-q-view-stats">');

        $this->voting($q_view);
        if (empty($this->content['w3_article_page'])) { $this->a_count($q_view); }
        qa_html_theme_base::view_count($q_view);

        $this->output('</div>');
    }

    public function q_view($q_view)
    {
        if (empty($this->content['w3_article_page'])) { parent::q_view($q_view); return; }
        if (empty($q_view)) { return; }
        $this->output('<div class="qa-q-view' . rtrim(' ' . (isset($q_view['classes']) ? $q_view['classes'] : '')) . '"' . rtrim(' ' . (isset($q_view['tags']) ? $q_view['tags'] : '')) . '>');
        $this->output('<div class="w3-article-meta">');
        $this->output(w3_article_byline_html($q_view));
        // Sibling forms: native voting and account favourite must never be nested.
        $this->output('<div class="w3-article-engagement">');
        if (isset($q_view['main_form_tags'])) { $this->output('<form ' . $q_view['main_form_tags'] . '>'); }
        $this->q_view_stats($q_view);
        if (isset($q_view['main_form_tags'])) {
            $this->form_hidden_elements(isset($q_view['voting_form_hidden']) ? $q_view['voting_form_hidden'] : null);
            $this->output('</form>');
        }
        $this->output('</div>');
        $this->output(w3_article_toolbar($q_view, isset($this->content['favorite']) ? $this->content['favorite'] : null));
        $this->output('</div>');
        $this->q_view_main($q_view);
        $this->q_view_clear();
        $this->output('</div> <!-- END qa-q-view -->');
    }

    /**
     * To modify user whometa, move to top
     *
     * @access public
     * @since FlatBox 1.0.0
     * @param type $q_view
     */
    public function q_view_content($q_view)
    {
        if (!empty($this->content['w3_article_page'])) {
            $data = $this->content['w3_article'];
            if ($data['hero'] !== '') {
                $this->output('<figure class="w3-article-hero"><img src="' . qa_html($data['hero']) . '" alt="' . qa_html($data['hero_alt']) . '" width="1200" height="675" fetchpriority="high" decoding="async" data-article-hero></figure>');
            }
            $q_view['content'] = $data['body'];
        }
        parent::q_view_content($q_view);
    }

    public function q_view_buttons($q_view)
    {
        if (empty($this->content['w3_article_page'])) { parent::q_view_buttons($q_view); return; }
        if (!empty($q_view['form'])) {
            // Keep unusual plugin-provided fields/outer forms on Q2A's normal rendering path.
            if (!empty($q_view['form']['fields']) || !empty($q_view['form']['tags'])) { parent::q_view_buttons($q_view); return; }
            $this->output(w3_article_actions_html($q_view['form']));
        }
    }

    public function q_view_main($q_view)
    {
        $this->output('<div class="qa-q-view-main">');

        if (isset($q_view['main_form_tags']))
            $this->output('<form ' . $q_view['main_form_tags'] . '>'); // form for buttons on question

        if (empty($this->content['w3_article_page'])) { $this->post_avatar_meta($q_view, 'qa-q-view'); }
        $this->q_view_content($q_view);
        $this->q_view_extra($q_view);
        $this->q_view_follows($q_view);
        $this->q_view_closed($q_view);
        $this->post_tags($q_view, 'qa-q-view');
        $this->q_view_buttons($q_view);
        $this->c_list(@$q_view['c_list'], 'qa-q-view');

        if (isset($q_view['main_form_tags'])) {
            $this->form_hidden_elements(@$q_view['buttons_form_hidden']);
            $this->output('</form>');
        }

        $this->c_form(@$q_view['c_form']);

        $this->output('</div> <!-- END qa-q-view-main -->');
    }

    /**
     * To move user whometa to top in answer
     *
     * @access public
     * @since FlatBox 1.0.0
     * @param type $a_item
     */
    public function a_item_main($a_item)
    {
        $this->output('<div class="qa-a-item-main">');

        $this->post_avatar_meta($a_item, 'qa-a-item');

        if (isset($a_item['main_form_tags']))
            $this->output('<form ' . $a_item['main_form_tags'] . '>'); // form for buttons on answer

        if ($a_item['hidden'])
            $this->output('<div class="qa-a-item-hidden">');
        elseif ($a_item['selected'])
            $this->output('<div class="qa-a-item-selected">');

        $this->a_selection($a_item);
        $this->error(@$a_item['error']);
        $this->a_item_content($a_item);

        if ($a_item['hidden'] || $a_item['selected'])
            $this->output('</div>');

        $this->a_item_buttons($a_item);

        $this->c_list(@$a_item['c_list'], 'qa-a-item');

        if (isset($a_item['main_form_tags'])) {
            $this->form_hidden_elements(@$a_item['buttons_form_hidden']);
            $this->output('</form>');
        }

        $this->c_form(@$a_item['c_form']);

        $this->output('</div> <!-- END qa-a-item-main -->');
    }

    /**
     * To move user whometa to top in comment
     *
     * @access public
     * @since FlatBox 1.0.0
     * @param type $c_item
     */
    public function c_item_main($c_item)
    {
        $this->post_avatar_meta($c_item, 'qa-c-item');

        $this->error(@$c_item['error']);

        if (isset($c_item['expand_tags']))
            $this->c_item_expand($c_item);
        elseif (isset($c_item['url']))
            $this->c_item_link($c_item);
        else
            $this->c_item_content($c_item);

        $this->output('<div class="qa-c-item-footer">');
        $this->c_item_buttons($c_item);
        $this->output('</div>');
    }

    /**
     * Footer wrapper
     *
     * @access public
     * @since FlatBox 1.0.0
     */
    function footer()
    { // prevent display of regular footer content (see body_suffix()) and replace with closing new <div>s
        $this->output('</main> <!-- END main-wrapper -->');
    }

    /**
     * Markup and icon info
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global array $flatbox
     */
    function body_suffix()
    { // to replace standard Q2A footer
        global $flatbox;

        $this->output($flatbox->footer_custom_content);
        $this->output('<div class="qam-footer-box">');

        $this->output(
            '<footer class="w3-corporate-footer">',
                '<div class="w3-footer-grid">',
                    '<div class="w3-footer-brand">',
                        '<a class="w3-footer-logo" href="' . qa_path_html('') . '" aria-label="W3 Uzman ana sayfa"><svg viewBox="0 0 30 34" aria-hidden="true"><path d="m15 5-10 23m10-23 10 23M5 28l10-7 10 7" fill="none" stroke="currentColor" stroke-width="2.6"></path><circle cx="15" cy="5" r="4" fill="currentColor"></circle><circle cx="5" cy="28" r="4" fill="currentColor"></circle><circle cx="25" cy="28" r="4" fill="currentColor"></circle><circle cx="15" cy="21" r="3" fill="currentColor"></circle></svg><span><b>W3</b>UZMAN<small>Teknolojiye Uzman Bakış</small></span></a>',
                        '<p>Web, yazılım, yapay zekâ ve dijital dünyadaki gelişmeleri takip edebileceğiniz bağımsız Türkçe teknoloji haber platformu.</p>',
                        '<section class="w3-footer-newsletter" aria-labelledby="w3-footer-newsletter-title">',
                            '<div class="w3-footer-newsletter-intro"><span class="w3-footer-newsletter-icon" aria-hidden="true">' . $this->w3_footer_icon('mail') . '</span><div><h2 id="w3-footer-newsletter-title">Teknoloji gündemini kaçırma</h2><p>En yeni haberler ve rehberler için takipte kalın.</p></div></div>',
                            '<form class="w3-footer-newsletter-form" action="' . qa_path_html('feedback') . '" method="post">',
                                '<label class="w3-footer-sr" for="w3-footer-newsletter-email">E-posta adresiniz</label>',
                                '<span class="w3-footer-input-icon" aria-hidden="true">' . $this->w3_footer_icon('mail') . '</span>',
                                '<input id="w3-footer-newsletter-email" type="email" name="newsletter_email" placeholder="E-posta adresiniz" autocomplete="email" maxlength="254" required>',
                                '<input type="hidden" name="w3_newsletter" value="1">',
                                '<input type="hidden" name="newsletter_code" value="' . qa_html(qa_get_form_security_code('w3_newsletter')) . '">',
                                '<button type="submit" aria-label="Bülten abonelik talebine devam et" title="Abonelik talebine devam et">' . $this->w3_footer_icon('send') . '</button>',
                            '</form>',
                            '<small>Talebinizi iletişim formunda tamamlayın.</small>',
                        '</section>',
                        $this->w3_footer_socials(),
                    '</div>',
                    '<nav class="w3-footer-column" aria-label="Kurumsal bağlantılar">',
                        '<h2>Kurumsal</h2>',
                        '<ul>',
                            $this->w3_footer_link(qa_path_html('hakkimizda'), 'Hakkımızda', 'info'),
                            $this->w3_footer_link(qa_path_html('feedback'), 'İletişim', 'mail'),
                            $this->w3_footer_link(qa_path_html('feedback'), 'Bize Ulaşın', 'send'),
                            $this->w3_footer_link(qa_path_html('feedback'), 'Reklam', 'megaphone'),
                            $this->w3_footer_link($this->w3_footer_policy_url('gizlilik|privacy'), 'Gizlilik Politikası', 'shield'),
                        '</ul>',
                    '</nav>',
                    '<nav class="w3-footer-column" aria-label="İçerik bağlantıları">',
                        '<h2>Keşfet</h2>',
                        '<ul>',
                            $this->w3_footer_link(qa_path_html('questions'), 'Tüm Haberler', 'document'),
                            $this->w3_footer_link(qa_path_html('questions', array('sort' => 'views')), 'En Çok Okunanlar', 'eye'),
                            $this->w3_footer_link(qa_path_html('kategoriler'), 'Kategoriler', 'grid'),
                            $this->w3_footer_link(qa_path_html('etiketler'), 'Etiketler', 'tag'),
                            $this->w3_footer_link(qa_path_html('dersler'), 'Dersler', 'book'),
                        '</ul>',
                    '</nav>',
                    '<nav class="w3-footer-column w3-footer-community" aria-label="Topluluk bağlantıları">',
                        '<h2>Topluluk</h2>',
                        '<ul>',
                            $this->w3_footer_link(qa_path_html('uyeler'), 'Kullanıcılar', 'users'),
                            $this->w3_footer_link(qa_path_html('hot'), 'Popüler Haberler', 'trending'),
                            $this->w3_footer_link(qa_path_html('feed/questions.rss'), 'RSS Akışı', 'rss'),
                            $this->w3_footer_link(qa_path_html('sitemap.xml'), 'Site Haritası', 'sitemap'),
                            $this->w3_footer_link(qa_path_html('whois-sorgulama'), 'Whois', 'globe'),
                        '</ul>',
                    '</nav>',
                    $this->w3_footer_tools(),
                '</div>',
                '<div class="w3-footer-bottom">',
                    '<span class="w3-footer-copyright">&copy; ' . date('Y') . ' W3Uzman. Tüm hakları saklıdır.<small>Powered by QDijital</small></span>',
                    '<a class="w3-footer-credit-logo" href="' . qa_path_html('') . '" aria-label="Q Software Solutions"><img src="' . qa_path_to_root() . 'qa-theme/FlatBox/images/footer-q-software.svg" alt="Q Software Solutions" width="260" height="80" loading="lazy"></a>',
                    '<nav class="w3-footer-legal" aria-label="Yasal ve iletişim bağlantıları"><a href="' . $this->w3_footer_policy_url('kullan.m|terms') . '">Kullanım Şartları</a><a href="' . $this->w3_footer_policy_url('çerez|cerez|cookie') . '">Çerez Politikası</a><a href="' . qa_path_html('feedback') . '">İletişim</a></nav>',
                '</div>',
            '</footer>'
        );

        // show / hide icons info in footer
        (qam_opt('icons_info') ? $this->output($flatbox->footers['icons_info']) : NULL);

        qa_html_theme_base::footer();
        $this->output('</div> <!-- END qam-footer-box -->', '');
    }

    /** Share one tool inventory between the header and footer. */
    private function w3_web_tools()
    {
        return array(
            'domain-oneri' => 'Domain Öneri Aracı',
            'whois-sorgulama' => 'Whois Sorgulama',
            'domain-sorgulama' => 'Domain Sorgulama',
            'dns-kayit-sorgulama' => 'DNS Kayıt Sorgulama',
            'ssl-sertifika-kontrolu' => 'SSL Sertifika Kontrolü',
            'qr-kod-olusturucu' => 'QR Kod Oluşturucu',
            'resimden-pdf' => 'Resimden PDF',
            'sayfa-seo-kontrolu' => 'Sayfa SEO Kontrolü',
            'metin-araclari' => 'Metin Araçları',
            'json-duzenleyici' => 'JSON Düzenleyici',
            'whatismyip' => 'WhatisMyip',
            'responsive-kontrol' => 'Responsive Kontrol',
        );
    }

    private function w3_footer_link($href, $title, $icon)
    {
        return '<li><a href="' . $href . '"><span class="w3-footer-tool-icon" aria-hidden="true">' . $this->w3_footer_icon($icon) . '</span><span>' . qa_html($title) . '</span></a></li>';
    }

    private function w3_footer_tools()
    {
        $tools = $this->w3_web_tools();
        $order = array_unique(array_merge(array('whois-sorgulama', 'domain-sorgulama', 'resimden-pdf'), array_keys($tools)));
        $icons = array('whois-sorgulama'=>'globe', 'domain-sorgulama'=>'search', 'resimden-pdf'=>'image', 'domain-oneri'=>'idea', 'dns-kayit-sorgulama'=>'database', 'ssl-sertifika-kontrolu'=>'shield', 'qr-kod-olusturucu'=>'qr', 'sayfa-seo-kontrolu'=>'chart', 'metin-araclari'=>'document', 'json-duzenleyici'=>'code', 'whatismyip'=>'pin', 'responsive-kontrol'=>'mobile');
        $html = '<nav class="w3-footer-column w3-footer-tools" aria-label="Araç bağlantıları"><h2>Araçlar</h2><ul>';
        foreach ($order as $request) {
            $html .= '<li><a href="' . qa_path_html($request) . '"><span class="w3-footer-tool-icon" aria-hidden="true">' . $this->w3_footer_icon($icons[$request]) . '</span><span>' . qa_html($tools[$request]) . '</span></a></li>';
        }
        return $html . '</ul><a class="w3-footer-all-tools" href="' . qa_path_html('araclar') . '">Tüm araçları gör <span aria-hidden="true">' . $this->w3_footer_icon('arrow') . '</span></a></nav>';
    }

    /** Reuse configured accounts; unconfigured destinations go home as requested. */
    private function w3_footer_socials()
    {
        $home = qa_path_html('');
        $profiles = array(
            'x' => array('X', qam_opt('twitter'), 'twitter.com|x.com'),
            'facebook' => array('Facebook', qam_opt('facebook'), 'facebook.com|fb.com'),
            'youtube' => array('YouTube', qam_opt('youtube'), 'youtube.com|youtu.be'),
            'instagram' => array('Instagram', qa_opt_if_loaded('qam_flatbox_instagram'), 'instagram.com'),
        );
        $html = '<nav class="w3-footer-socials" aria-label="Sosyal medya hesapları">';
        foreach ($profiles as $icon => $profile) {
            $url = is_string($profile[1]) ? trim($profile[1]) : '';
            $parts = parse_url($url);
            $valid = is_array($parts) && isset($parts['scheme'], $parts['host']) && in_array(strtolower($parts['scheme']), array('http', 'https'), true) && preg_match('~(^|\.)(' . str_replace('.', '\.', $profile[2]) . ')$~i', $parts['host']);
            $href = $valid ? qa_html(preg_replace('~^http:~i', 'https:', $url)) : $home;
            $title = $profile[0] . ($valid ? '' : ' – Ana sayfa');
            $html .= '<a href="' . $href . '" aria-label="' . qa_html($title) . '" title="' . qa_html($title) . '"' . ($valid ? ' target="_blank" rel="noopener noreferrer"' : ' data-fallback="home"') . '>' . $this->w3_footer_icon($icon) . '</a>';
        }
        return $html . '<a href="' . qa_path_html('feed/questions.rss') . '" aria-label="RSS akışı" title="RSS akışı">' . $this->w3_footer_icon('rss') . '</a></nav>';
    }

    /** Use existing native policy pages when present, otherwise the home fallback. */
    private function w3_footer_policy_url($pattern)
    {
        $pages = array('gizlilik|privacy' => 'gizlilik-politikasi', 'kullan.m|terms' => 'kullanim-sartlari', 'çerez|cerez|cookie' => 'cerez-politikasi');
        if (isset($pages[$pattern])) { return qa_path_html($pages[$pattern]); }
        foreach (array('footer', 'main') as $location) {
            foreach (isset($this->content['navigation'][$location]) ? $this->content['navigation'][$location] : array() as $item) {
                if (!empty($item['url']) && isset($item['label']) && preg_match('~' . $pattern . '~iu', strip_tags($item['label']))) { return qa_html($item['url']); }
            }
        }
        return qa_path_html('');
    }

    private function w3_footer_icon($name)
    {
        $icons = array(
            'info' => '<g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v6M12 7v.1"></path></g>',
            'megaphone' => '<g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h5l11-5v16L9 15H4zM7 15l2 6h4l-2-5M2 10v4"></path></g>',
            'eye' => '<g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></g>',
            'grid' => '<g fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></g>',
            'tag' => '<g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M3 3h8l10 10-8 8L3 11Z"></path><circle cx="7.5" cy="7.5" r="1.3"></circle></g>',
            'book' => '<g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M12 5c-3-2-6-2-10-1v15c4-1 7-1 10 1 3-2 6-2 10-1V4c-4-1-7-1-10 1ZM12 5v15"></path></g>',
            'users' => '<g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="9" cy="7" r="4"></circle><path d="M2 21v-2a7 7 0 0 1 14 0v2M17 3a4 4 0 0 1 0 8M18 14a6 6 0 0 1 4 5v2"></path></g>',
            'trending' => '<path d="m3 17 6-6 4 4 8-10M15 5h6v6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>',
            'sitemap' => '<g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M9 2h6v5H9zM2 17h6v5H2zM16 17h6v5h-6zM12 7v5M5 17v-5h14v5"></path></g>',
            'x' => '<path d="M4 3h4l12 18h-4L4 3zM20 3 4 21" fill="none" stroke="currentColor" stroke-width="1.8"></path>',
            'facebook' => '<path d="M14 22V13h3l.5-4H14V7c0-1.2.4-2 2-2h2V1.5A24 24 0 0 0 15 1c-3 0-5 1.9-5 5v3H7v4h3v9z" fill="currentColor"></path>',
            'youtube' => '<rect x="2" y="5" width="20" height="14" rx="4" fill="currentColor"></rect><path d="m10 9 6 3-6 3z" fill="#18222f"></path>',
            'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="1.8"></rect><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"></circle><circle cx="17.5" cy="6.5" r="1.1" fill="currentColor"></circle>',
            'rss' => '<circle cx="5" cy="19" r="2" fill="currentColor"></circle><path d="M3 11a10 10 0 0 1 10 10M3 4a17 17 0 0 1 17 17" fill="none" stroke="currentColor" stroke-width="2.5"></path>',
            'send' => '<path d="m21 3-6 18-4-8-8-4zM11 13 21 3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>',
            'mail' => '<rect x="2.5" y="4.5" width="19" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.7"></rect><path d="m3.5 6 8.5 7 8.5-7" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"></path>',
            'globe' => '<g fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"></circle><ellipse cx="12" cy="12" rx="4" ry="9"></ellipse><path d="M3 12h18M5 6.5c4 2 10 2 14 0M5 17.5c4-2 10-2 14 0"></path></g>',
            'search' => '<g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="10.5" cy="10.5" r="7.5"></circle><path d="m16 16 5 5"></path></g>',
            'image' => '<g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1.5"></rect><circle cx="8" cy="8" r="1.5"></circle><path d="m3 17 6-6 4 4 3-3 5 5"></path></g>',
            'idea' => '<g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M8 15c0-2-3-3-3-7a7 7 0 0 1 14 0c0 4-3 5-3 7v3H8v-3Z M9 21h6M8 15h8"></path></g>',
            'database' => '<g fill="none" stroke="currentColor" stroke-width="1.6"><ellipse cx="12" cy="5" rx="8" ry="3"></ellipse><path d="M4 5v14c0 4 16 4 16 0V5M4 12c0 4 16 4 16 0M4 17c0 4 16 4 16 0"></path></g>',
            'shield' => '<g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 9 4v6c0 6-9 10-9 10S3 18 3 12V6l9-4Z"></path><path d="m8 12 3 3 5-6"></path></g>',
            'qr' => '<path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h3v3h3v3h-6v-3m6-6v3M12 3v3m0 6v3m0 6v1M3 12h3m3 0h6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"></path>',
            'chart' => '<path d="M4 21v-5h3v5M11 21V9h3v12M18 21V3h3v18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"></path>',
            'document' => '<g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M5 2h9l5 5v15H5V2Z M14 2v5h5M8 11h8M8 15h8M8 18h5"></path></g>',
            'code' => '<path d="M9 3H7c-3 0-1 6-4 9 3 3 1 9 4 9h2M15 3h2c3 0 1 6 4 9-3 3-1 9-4 9h-2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"></path>',
            'pin' => '<g fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></g>',
            'mobile' => '<g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><rect x="5" y="2" width="14" height="20" rx="2"></rect><path d="M10 19h4"></path></g>',
            'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>',
        );
        return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[$name] . '</svg>';
    }

    /** Keep other custom footer HTML while removing the old, separately rendered logo. */
    public function body_footer()
    {
        $html = isset($this->content['body_footer']) ? $this->content['body_footer'] : '';
        $image = '<img\b(?=[^>]*\bsrc\s*=\s*["\'](?:[^"\']*/)?qq-software-logo\.png(?:\?[^"\']*)?["\'])[^>]*>';
        $link = '<a\b[^>]*>\s*' . $image . '\s*</a>';
        // Only remove whole wrappers when they contain nothing except this logo.
        $html = preg_replace('~<div\b[^>]*>\s*' . $link . '\s*</div>~i', '', $html);
        $html = preg_replace('~' . $link . '~i', '', $html);
        $html = preg_replace('~' . $image . '~i', '', $html);
        $this->output_raw($html);
    }

    /**
     * To add Q2A Market attribution
     *
     * *************************************************************************
     * IMPORTANT!
     * -------------------------------------------------------------------------
     * FlatBox Theme by Q2A Market is released under GPL-v3.0
     * License URL: http://www.gnu.org/copyleft/gpl.html
     *
     * According to the GPL-v3.0 license, you should not remove below credits
     * for FlatBox Theme by Q2A Market. If you do so than you are violating the
     * license agreement.
     *
     * You are free to add your credits with remaining Q2A Market credits note
     * in case if you modify any of the files includes with the FlatBox Theme Package
     *
     * For more information read full license.txt file
     *
     * *************************************************************************
     *
     * @access public
     * @since FlatBox 1.0.0
     * @global array $flatbox
     */
    function attribution()
    {
        // Footer attribution is rendered by the corporate footer in body_suffix().
    }

    /**
     * To add search-box wrapper with extra class for color scheme
     *
     * @access public
     * @since FlatBox 1.0.0
     * @version 1.0
     * @global array $flatbox
     *
     * @author Q2A Market <http://www.q2amarket.com>
     * @copyright (c) 2014, Q2A Market
     * @license http://www.gnu.org/copyleft/gpl.html
     */
    function qam_search($addon_class = FALSE, $ids = FALSE)
    {
        global $flatbox;

        $id = (($ids) ? ' id="' . $ids . '"' : NULL);

        $this->output('<div class="qam-search ' . $flatbox->ask_search_box_color . ' ' . $addon_class . '" ' . $id . ' >');
        qa_html_theme_base::search();
        $this->output('</div>');
    }

}

/*
	Omit PHP closing tag to help avoid accidental output
*/

// End of qa-theme.php
