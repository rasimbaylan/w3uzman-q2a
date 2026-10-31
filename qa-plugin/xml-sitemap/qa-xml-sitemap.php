<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/xml-sitemap/qa-xml-sitemap.php
	Description: Page module class for XML sitemap plugin


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

class qa_xml_sitemap
{
	private $emitted = array();
	public function option_default($option)
	{
		switch ($option) {
			case 'xml_sitemap_show_questions':
			case 'xml_sitemap_show_users':
			case 'xml_sitemap_show_tag_qs':
			case 'xml_sitemap_show_category_qs':
			case 'xml_sitemap_show_categories':
				return true;
		}
	}


	public function admin_form()
	{
		require_once QA_INCLUDE_DIR . 'util/sort.php';

		$saved = false;

		if (qa_clicked('xml_sitemap_save_button')) {
			qa_opt('xml_sitemap_show_questions', (int)qa_post_text('xml_sitemap_show_questions_field'));

			if (!QA_FINAL_EXTERNAL_USERS)
				qa_opt('xml_sitemap_show_users', (int)qa_post_text('xml_sitemap_show_users_field'));

			if (qa_using_tags())
				qa_opt('xml_sitemap_show_tag_qs', (int)qa_post_text('xml_sitemap_show_tag_qs_field'));

			if (qa_using_categories()) {
				qa_opt('xml_sitemap_show_category_qs', (int)qa_post_text('xml_sitemap_show_category_qs_field'));
				qa_opt('xml_sitemap_show_categories', (int)qa_post_text('xml_sitemap_show_categories_field'));
			}

			$saved = true;
		}

		$form = array(
			'ok' => $saved ? 'XML sitemap settings saved' : null,

			'fields' => array(
				'questions' => array(
					'label' => 'Include question pages',
					'type' => 'checkbox',
					'value' => (int)qa_opt('xml_sitemap_show_questions'),
					'tags' => 'name="xml_sitemap_show_questions_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="xml_sitemap_save_button"',
				),
			),
		);

		if (!QA_FINAL_EXTERNAL_USERS) {
			$form['fields']['users'] = array(
				'label' => 'Include user pages',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_users'),
				'tags' => 'name="xml_sitemap_show_users_field"',
			);
		}

		if (qa_using_tags()) {
			$form['fields']['tagqs'] = array(
				'label' => 'Include question list for each tag',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_tag_qs'),
				'tags' => 'name="xml_sitemap_show_tag_qs_field"',
			);
		}

		if (qa_using_categories()) {
			$form['fields']['categoryqs'] = array(
				'label' => 'Include question list for each category',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_category_qs'),
				'tags' => 'name="xml_sitemap_show_category_qs_field"',
			);

			$form['fields']['categories'] = array(
				'label' => 'Include category browser',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_categories'),
				'tags' => 'name="xml_sitemap_show_categories_field"',
			);
		}

		return $form;
	}


	public function suggest_requests()
	{
		return array(
			array(
				'title' => 'XML Sitemap',
				'request' => 'sitemap.xml',
				'nav' => null, // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}


	public function match_request($request)
	{
		return ($request == 'sitemap.xml');
	}


	public function process_request($request)
	{
		$this->emitted = array();
		@ini_set('display_errors', 0); // we don't want to show PHP errors inside XML

		header('Content-type: text/xml; charset=utf-8');

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		// Include the site's public entry points and enabled W3 tools/information pages.
		// Auth forms, admin routes, searches and query-string tool results are not index entries.
		foreach (array('' => 1.0, 'questions' => 0.8, 'hot' => 0.6, 'feedback' => 0.3) as $route => $priority) {
			$this->sitemap_output($route, $priority);
		}
		if (qa_using_tags()) { $this->sitemap_output('tags', 0.5); }
		if (!QA_FINAL_EXTERNAL_USERS && qa_opt('xml_sitemap_show_users')) { $this->sitemap_output('users', 0.4); }
		$publicModules = array('w3_info_pages', 'w3_about_page', 'w3_whois_page', 'w3_image_pdf_page', 'w3_seo_check_page', 'w3_domain_generator_page', 'w3_domain_search_page', 'w3_dns_records_page', 'w3_ssl_check_page', 'w3_whatismyip_page', 'w3_responsive_page', 'w3_qr_code_page', 'w3_text_json_page', 'w3_tools_directory_page', 'w3_ai_pages');
		foreach (qa_load_modules_with('page', 'suggest_requests') as $module) {
			if (!in_array(get_class($module), $publicModules, true)) { continue; }
			foreach ($module->suggest_requests() as $page) { $this->sitemap_output($page['request'], 0.3); }
		}
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		foreach (qa_db_select_with_pending(qa_db_pages_selectspec()) as $page) {
			if (isset($page['tags']) && in_array($page['tags'], array('hakkimizda', 'dersler'), true)) { $this->sitemap_output($page['tags'], 0.5); }
		}


		// Question pages

		if (qa_opt('xml_sitemap_show_questions')) {
			$hotstats = qa_db_read_one_assoc(qa_db_query_sub(
				"SELECT MIN(hotness) AS base, MAX(hotness)-MIN(hotness) AS spread FROM ^posts WHERE type='Q'"
			));

			$nextpostid = 0;

			while (1) {
				$questions = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT postid, title, hotness, updated FROM ^posts WHERE postid>=# AND type='Q' ORDER BY postid LIMIT 100",
					$nextpostid
				));

				if (!count($questions))
					break;

				foreach ($questions as $question) {
					$this->sitemap_output(qa_q_request($question['postid'], $question['title']),
						0.1 + 0.9 * ($question['hotness'] - $hotstats['base']) / (1 + $hotstats['spread']),
						$question['updated']);
					$nextpostid = max($nextpostid, $question['postid'] + 1);
				}
			}
		}


		// User pages

		if (!QA_FINAL_EXTERNAL_USERS && qa_opt('xml_sitemap_show_users')) {
			$nextuserid = 0;

			while (1) {
				$users = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT userid, handle FROM ^users WHERE userid>=# ORDER BY userid LIMIT 100",
					$nextuserid
				));

				if (!count($users))
					break;

				foreach ($users as $user) {
					$this->sitemap_output('user/' . $user['handle'], 0.25);
					$nextuserid = max($nextuserid, $user['userid'] + 1);
				}
			}
		}


		// Tag pages

		if (qa_using_tags() && qa_opt('xml_sitemap_show_tag_qs')) {
			$nextwordid = 0;

			while (1) {
				$tagwords = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT wordid, word, tagcount FROM ^words WHERE wordid>=# AND tagcount>0 ORDER BY wordid LIMIT 100",
					$nextwordid
				));

				if (!count($tagwords))
					break;

				foreach ($tagwords as $tagword) {
					$this->sitemap_output('tag/' . $tagword['word'], 0.5 / (1 + (1 / $tagword['tagcount']))); // priority between 0.25 and 0.5 depending on tag frequency
					$nextwordid = max($nextwordid, $tagword['wordid'] + 1);
				}
			}
		}


		// Question list for each category

		if (qa_using_categories() && qa_opt('xml_sitemap_show_category_qs')) {
			$nextcategoryid = 0;

			while (1) {
				$categories = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT categoryid, backpath FROM ^categories WHERE categoryid>=# AND qcount>0 ORDER BY categoryid LIMIT 2",
					$nextcategoryid
				));

				if (!count($categories))
					break;

				foreach ($categories as $category) {
					$this->sitemap_output('questions/' . implode('/', array_reverse(explode('/', $category['backpath']))), 0.5);
					$nextcategoryid = max($nextcategoryid, $category['categoryid'] + 1);
				}
			}
		}


		// Pages in category browser

		if (qa_using_categories() && qa_opt('xml_sitemap_show_categories')) {
			$this->sitemap_output('categories', 0.5);

			$nextcategoryid = 0;

			while (1) { // only find categories with a child
				$categories = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT parent.categoryid, parent.backpath FROM ^categories AS parent " .
					"JOIN ^categories AS child ON child.parentid=parent.categoryid WHERE parent.categoryid>=# GROUP BY parent.categoryid LIMIT 100",
					$nextcategoryid
				));

				if (!count($categories))
					break;

				foreach ($categories as $category) {
					$this->sitemap_output('categories/' . implode('/', array_reverse(explode('/', $category['backpath']))), 0.5);
					$nextcategoryid = max($nextcategoryid, $category['categoryid'] + 1);
				}
			}
		}

		echo "</urlset>\n";

		return null;
	}


	private function sitemap_output($request, $priority, $updated = null)
	{
		$url = qa_path($request, null, qa_opt('site_url'));
		if (isset($this->emitted[$url])) { return; }
		$this->emitted[$url] = true;
		$updatedtime = is_numeric($updated) ? (int)$updated : strtotime((string)$updated);
		$lastmod = ($updatedtime > 0) ? "\t\t<lastmod>" . gmdate('c', $updatedtime) . "</lastmod>\n" : '';
		echo "\t<url>\n" .
			"\t\t<loc>" . qa_xml($url) . "</loc>\n" .
			$lastmod .
			"\t\t<priority>" . max(0, min(1.0, $priority)) . "</priority>\n" .
			"\t</url>\n";
	}
}
