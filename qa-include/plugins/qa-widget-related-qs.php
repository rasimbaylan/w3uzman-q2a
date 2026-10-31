<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Widget module class for related questions


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_related_qs
{
	private function related_cover_url($postid)
	{
		require_once QA_INCLUDE_DIR . 'db/metas.php';
		$cover = qa_db_postmeta_get($postid, 'w3_cover_image');
		if (!empty($cover))
			return html_entity_decode(trim($cover), ENT_QUOTES, 'UTF-8');

		$content = qa_db_read_one_value(qa_db_query_sub('SELECT content FROM ^posts WHERE postid=#', $postid), true);
		if (empty($content))
			return '';

		if (preg_match('/<img[^>]+src\\s*=\\s*["\\\']([^"\\\']+)/i', $content, $match))
			return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
		if (preg_match('/!\\[[^\\]]*\\]\\(([^\\s\\)]+)/', $content, $match))
			return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
		if (preg_match('/^\\s*\\[[0-9]+\\]:\\s*(\\S+)/m', $content, $match))
			return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');

		return '';
	}
	public function allow_template($template)
	{
		return $template == 'question';
	}

	public function allow_region($region)
	{
		return in_array($region, array('side', 'main', 'full'));
	}

	public function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		require_once QA_INCLUDE_DIR . 'util/w3-category-covers.php';

		if (!isset($qa_content['q_view']['raw']['type']) || $qa_content['q_view']['raw']['type'] != 'Q') // question might not be visible, etc...
			return;

		$questionid = $qa_content['q_view']['raw']['postid'];

		$userid = qa_get_logged_in_userid();
		$cookieid = qa_cookie_get();

		$questions = qa_db_single_select(qa_db_related_qs_selectspec($userid, $questionid, qa_opt('page_size_related_qs')));

		$minscore = qa_match_to_min_score(qa_opt('match_related_qs'));

		foreach ($questions as $key => $question) {
			if ($question['score'] < $minscore)
				unset($questions[$key]);
		}

		$titlehtml = qa_lang_html(count($questions) ? 'main/related_qs_title' : 'main/no_related_qs_title');

		if ($region == 'side') {
			$themeobject->output(
				'<div class="qa-related-qs">',
				'<h2 style="margin-top:0; padding-top:0;">',
				$titlehtml,
				'</h2>'
			);

			$themeobject->output('<ul class="qa-related-q-list">');

			foreach ($questions as $question) {
				$title = qa_html($question['title']);
				$cover = $this->related_cover_url($question['postid']);
				$fallback = w3_category_cover_url(isset($question['categoryid']) ? $question['categoryid'] : null, $question['title']);
				$coverhtml = qa_html(empty($cover) ? $fallback : $cover);
				$themeobject->output(
					'<li class="qa-related-q-item">' .
					'<a class="qa-related-q-link" href="' . qa_q_path_html($question['postid'], $question['title']) . '">' .
					'<span class="qa-related-q-thumb"><img src="' . $coverhtml . '" alt="' . $title . '" loading="lazy" onerror="' . qa_html('this.onerror=null;this.src=' . qa_js($fallback) . ';') . '"></span>' .
					'<span class="qa-related-q-title">' . $title . '</span>' .
					'</a>' .
					'</li>'
				);
			}

			$themeobject->output(
				'</ul>',
				'</div>'
			);
		} else {
			$themeobject->output(
				'<h2>',
				$titlehtml,
				'</h2>'
			);

			$q_list = array(
				'form' => array(
					'tags' => 'method="post" action="' . qa_self_html() . '"',
					'hidden' => array(
						'code' => qa_get_form_security_code('vote'),
					),
				),
				'qs' => array(),
			);

			$defaults = qa_post_html_defaults('Q');
			$usershtml = qa_userids_handles_html($questions);

			foreach ($questions as $question) {
				$q_list['qs'][] = qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, qa_post_html_options($question, $defaults));
			}

			$themeobject->q_list_and_form($q_list);
		}
	}
}
