<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/tag-cloud-widget/qa-tag-cloud.php
	Description: Widget module class for tag cloud plugin


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

class qa_tag_cloud
{
	public function option_default($option)
	{
		switch ($option) {
			case 'tag_cloud_count_tags':
				return 100;
			case 'tag_cloud_font_size':
				return 24;
			case 'tag_cloud_minimal_font_size':
				return 12;
			case 'tag_cloud_size_popular':
				return true;
		}
	}

	private function bounded_setting($value, $minimum, $maximum, $default)
	{
		return is_numeric($value) ? max($minimum, min($maximum, (int)$value)) : $default;
	}

	private function settings()
	{
		$maximum = $this->bounded_setting(qa_opt('tag_cloud_font_size'), 12, 48, 24);
		return array(
			$this->bounded_setting(qa_opt('tag_cloud_count_tags'), 1, 200, 100),
			$maximum,
			$this->bounded_setting(qa_opt('tag_cloud_minimal_font_size'), 12, $maximum, 12),
		);
	}


	public function admin_form()
	{
		$saved = qa_clicked('tag_cloud_save_button');

		if ($saved) {
			$maximum = $this->bounded_setting(qa_post_text('tag_cloud_font_size_field'), 12, 48, 24);
			qa_opt('tag_cloud_count_tags', $this->bounded_setting(qa_post_text('tag_cloud_count_tags_field'), 1, 200, 100));
			qa_opt('tag_cloud_font_size', $maximum);
			qa_opt('tag_cloud_minimal_font_size', $this->bounded_setting(qa_post_text('tag_cloud_minimal_font_size_field'), 12, $maximum, 12));
			qa_opt('tag_cloud_size_popular', (int) qa_post_text('tag_cloud_size_popular_field'));
		}

		list($count, $maximum, $minimum) = $this->settings();
		return array(
			'ok' => $saved ? 'Etiket bulutu ayarları kaydedildi.' : null,

			'fields' => array(
				array(
					'label' => 'Gösterilecek en fazla etiket:',
					'type' => 'number',
					'value' => $count,
					'suffix' => 'etiket (1–200)',
					'tags' => 'name="tag_cloud_count_tags_field" min="1" max="200"',
				),

				array(
					'label' => 'En büyük yazı boyutu:',
					'suffix' => 'piksel (12–48)',
					'type' => 'number',
					'value' => $maximum,
					'tags' => 'name="tag_cloud_font_size_field" min="12" max="48"',
				),

				array(
					'label' => 'En küçük yazı boyutu:',
					'suffix' => 'piksel (en az 12; en büyük boyutu aşamaz)',
					'type' => 'number',
					'value' => $minimum,
					'tags' => 'name="tag_cloud_minimal_font_size_field" min="12" max="48"',
				),

				array(
					'label' => 'Yazı boyutunu etiketin kullanım sıklığına göre ayarla',
					'type' => 'checkbox',
					'value' => qa_opt('tag_cloud_size_popular'),
					'tags' => 'name="tag_cloud_size_popular_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Değişiklikleri kaydet',
					'tags' => 'name="tag_cloud_save_button"',
				),
			),
		);
	}


	public function allow_template($template)
	{
		$allowed = array(
			'activity', 'qa', 'questions', 'hot', 'ask', 'categories', 'question',
			'tag', 'tags', 'unanswered', 'user', 'users', 'search', 'admin', 'custom',
		);
		return in_array($template, $allowed);
	}


	public function allow_region($region)
	{
		return ($region === 'side');
	}


	public function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		require_once QA_INCLUDE_DIR.'db/selects.php';

		list($limit, $maxsize, $minsize) = $this->settings();
		$populartags = qa_db_single_select(qa_db_popular_tags_selectspec(0, $limit));

		$populartagslog = array();
		foreach ($populartags as $tag => $count) {
			if (is_numeric($count) && is_finite((float)$count) && $count > 0)
				$populartagslog[$tag] = log((float)$count) + 1;
		}

		$maxcount = $populartagslog ? max($populartagslog) : 1;

		$themeobject->output(sprintf('<h2 style="margin-top: 0; padding-top: 0;">%s</h2>', qa_lang_html('main/popular_tags')));

		$themeobject->output('<div style="font-size: 12px; line-height: 1.8; overflow-wrap: anywhere;">');

		$scale = qa_opt('tag_cloud_size_popular');
		$blockwordspreg = qa_get_block_words_preg();

		foreach ($populartagslog as $tag => $count) {
			$matches = qa_block_words_match_all($tag, $blockwordspreg);
			if (!empty($matches)) {
				continue;
			}

			if ($scale) {
				$size = max($minsize, min($maxsize, (int)round($maxsize * $count / $maxcount)));
			} else {
				$size = $maxsize;
			}

			$themeobject->output(sprintf('<a href="%s" style="font-size: %dpx; vertical-align: baseline; margin-inline-end: .35em;">%s</a>', qa_path_html('tag/' . $tag), $size, qa_html($tag)));
		}

		$themeobject->output('</div>');
	}
}
