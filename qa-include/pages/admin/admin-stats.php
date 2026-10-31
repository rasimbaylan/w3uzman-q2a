<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for admin page showing usage statistics and clean-up buttons


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'db/recalc.php';
require_once QA_INCLUDE_DIR . 'app/admin.php';
require_once QA_INCLUDE_DIR . 'db/admin.php';
require_once QA_INCLUDE_DIR . 'app/format.php';
require_once QA_INCLUDE_DIR . 'app/blobs.php';


// Check admin privileges (do late to allow one DB query)

if (!qa_admin_check_privileges($qa_content))
	return $qa_content;


// Get the information to display

$qcount = (int)qa_opt('cache_qcount');
$qcount_anon = qa_db_count_posts('Q', false);
$qcount_unans = (int)qa_opt('cache_unaqcount');

$acount = (int)qa_opt('cache_acount');
$acount_anon = qa_db_count_posts('A', false);

$ccount = (int)qa_opt('cache_ccount');
$ccount_anon = qa_db_count_posts('C', false);


// Prepare content for theme

$qa_content = qa_content_prepare();

$qa_content['title'] = qa_lang_html('admin/admin_title') . ' - ' . qa_lang_html('admin/stats_title');

$qa_content['error'] = qa_admin_page_error();

$qa_content['form'] = array(
	'style' => 'wide',

	'fields' => array(
		'q2a_version' => array(
			'label' => qa_lang_html('admin/q2a_version'),
			'value' => qa_html(QA_VERSION),
		),

		'q2a_date' => array(
			'label' => qa_lang_html('admin/q2a_build_date'),
			'value' => qa_html(QA_BUILD_DATE),
		),

		'q2a_latest' => array(
			'label' => qa_lang_html('admin/q2a_latest_version'),
			'type' => 'custom',
			'html' => '<span id="q2a-version">...</span>',
		),

		'break0' => array(
			'type' => 'blank',
		),

		'db_version' => array(
			'label' => qa_lang_html('admin/q2a_db_version'),
			'value' => qa_html(qa_opt('db_version')),
		),

		'db_size' => array(
			'label' => qa_lang_html('admin/q2a_db_size'),
			'value' => qa_html(qa_format_number(qa_db_table_size() / 1048576, 1) . ' MB'),
		),

		'break1' => array(
			'type' => 'blank',
		),

		'php_version' => array(
			'label' => qa_lang_html('admin/php_version'),
			'value' => qa_html(phpversion()),
		),

		'mysql_version' => array(
			'label' => qa_lang_html('admin/mysql_version'),
			'value' => qa_html(qa_db_mysql_version()),
		),

		'break2' => array(
			'type' => 'blank',
		),

		'qcount' => array(
			'label' => qa_lang_html('admin/total_qs'),
			'value' => qa_html(qa_format_number($qcount)),
		),

		'qcount_unans' => array(
			'label' => qa_lang_html('admin/total_qs_unans'),
			'value' => qa_html(qa_format_number($qcount_unans)),
		),

		'qcount_users' => array(
			'label' => qa_lang_html('admin/from_users'),
			'value' => qa_html(qa_format_number($qcount - $qcount_anon)),
		),

		'qcount_anon' => array(
			'label' => qa_lang_html('admin/from_anon'),
			'value' => qa_html(qa_format_number($qcount_anon)),
		),

		'break3' => array(
			'type' => 'blank',
		),

		'acount' => array(
			'label' => qa_lang_html('admin/total_as'),
			'value' => qa_html(qa_format_number($acount)),
		),

		'acount_users' => array(
			'label' => qa_lang_html('admin/from_users'),
			'value' => qa_html(qa_format_number($acount - $acount_anon)),
		),

		'acount_anon' => array(
			'label' => qa_lang_html('admin/from_anon'),
			'value' => qa_html(qa_format_number($acount_anon)),
		),

		'break4' => array(
			'type' => 'blank',
		),

		'ccount' => array(
			'label' => qa_lang_html('admin/total_cs'),
			'value' => qa_html(qa_format_number($ccount)),
		),

		'ccount_users' => array(
			'label' => qa_lang_html('admin/from_users'),
			'value' => qa_html(qa_format_number($ccount - $ccount_anon)),
		),

		'ccount_anon' => array(
			'label' => qa_lang_html('admin/from_anon'),
			'value' => qa_html(qa_format_number($ccount_anon)),
		),

		'break5' => array(
			'type' => 'blank',
		),

		'users' => array(
			'label' => qa_lang_html('admin/users_registered'),
			'value' => QA_FINAL_EXTERNAL_USERS ? '' : qa_html(qa_format_number(qa_db_count_users())),
		),

		'users_active' => array(
			'label' => qa_lang_html('admin/users_active'),
			'value' => qa_html(qa_format_number((int)qa_opt('cache_userpointscount'))),
		),

		'users_posted' => array(
			'label' => qa_lang_html('admin/users_posted'),
			'value' => qa_html(qa_format_number(qa_db_count_active_users('posts'))),
		),

		'users_voted' => array(
			'label' => qa_lang_html('admin/users_voted'),
			'value' => qa_html(qa_format_number(qa_db_count_active_users('uservotes'))),
		),
	),
);

if (QA_FINAL_EXTERNAL_USERS)
	unset($qa_content['form']['fields']['users']);
else
	unset($qa_content['form']['fields']['users_active']);

foreach ($qa_content['form']['fields'] as $index => $field) {
	if (empty($field['type']))
		$qa_content['form']['fields'][$index]['type'] = 'static';
}

$media = qa_blob_storage_stats();
$mediaWritable = is_dir(qa_get_blob_storage_directory()) && is_writable(qa_get_blob_storage_directory());
$qa_content['form_media'] = array(
	'title' => 'Görsel ve dosya depolama',
	'style' => 'wide',
	'tags' => 'method="post" action="' . qa_path_html('admin/recalc') . '"',
	'fields' => array(
		'policy' => array('type' => 'static', 'label' => 'Yeni yüklemeler',
			'value' => 'Otomatik olarak dosyaya kaydedilir; SQL içine görsel içeriği yazılmaz.'),
		'directory' => array('type' => 'static', 'label' => 'Kayıt klasörü',
			'value' => qa_html(QA_BLOBS_DIRECTORY)),
		'writable' => array('type' => 'static', 'label' => 'Klasör durumu',
			'value' => $mediaWritable ? 'Yazılabilir' : 'Yazılamıyor. Yüklemeler SQL içine alınmaz; klasör izinlerini kontrol edin.'),
		'disk' => array('type' => 'static', 'label' => 'Dosyalarda saklanan',
			'value' => qa_html(qa_format_number($media['disk_files']) . ' dosya · ' . qa_format_number($media['disk_bytes'] / 1000000, 2) . ' MB')),
		'database' => array('type' => 'static', 'label' => 'SQL içinde kalan',
			'value' => qa_html(qa_format_number($media['database_files']) . ' dosya · ' . qa_format_number($media['database_bytes'] / 1000000, 2) . ' MB')),
		'missing' => array('type' => 'static', 'label' => 'Bulunamayan dosyalar',
			'value' => qa_html(qa_format_number($media['missing_files']))),
		'backup' => array('type' => 'static', 'label' => 'Yedekleme',
			'value' => 'SQL yedeğiyle birlikte görsellerin bulunduğu klasörü de yedekleyin. SQL içindeki kimlik ve dosya kayıtları korunmalıdır.'),
	),
	'hidden' => array('code' => qa_get_form_security_code('admin/recalc')),
);
if ($media['database_files'] > 0 && $mediaWritable) {
	$qa_content['form_media']['buttons']['blobs_to_disk'] = array(
		'label' => 'Eski yüklemeleri dosyalara taşı',
		'tags' => 'name="doblobstodisk" onclick="return qa_recalc_click(this.name, this, ' . qa_js(qa_lang_html('admin/blobs_stop')) . ', \'blobs_to_disk_note\');"',
		'note' => '<span id="blobs_to_disk_note">SQL içindeki eski dosyaları taşır; mevcut görsel bağlantıları değişmez. İşlemden sonra sayfayı yenileyin.</span>',
	);
}

$qa_content['form_2'] = array(
	'tags' => 'method="post" action="' . qa_path_html('admin/recalc') . '"',

	'title' => qa_lang_html('admin/database_cleanup'),

	'style' => 'basic',

	'buttons' => array(
		'recount_posts' => array(
			'label' => qa_lang_html('admin/recount_posts'),
			'tags' => 'name="dorecountposts" onclick="return qa_recalc_click(this.name, this, ' . qa_js(qa_lang_html('admin/recount_posts_stop')) . ', \'recount_posts_note\');"',
			'note' => '<span id="recount_posts_note">' . qa_lang_html('admin/recount_posts_note') . '</span>',
		),

		'reindex_content' => array(
			'label' => qa_lang_html('admin/reindex_content'),
			'tags' => 'name="doreindexcontent" onclick="return qa_recalc_click(this.name, this, ' . qa_js(qa_lang_html('admin/reindex_content_stop')) . ', \'reindex_content_note\');"',
			'note' => '<span id="reindex_content_note">' . qa_lang_html('admin/reindex_content_note') . '</span>',
		),

		'recalc_points' => array(
			'label' => qa_lang_html('admin/recalc_points'),
			'tags' => 'name="dorecalcpoints" onclick="return qa_recalc_click(this.name, this, ' . qa_js(qa_lang_html('admin/recalc_stop')) . ', \'recalc_points_note\');"',
			'note' => '<span id="recalc_points_note">' . qa_lang_html('admin/recalc_points_note') . '</span>',
		),

		'refill_events' => array(
			'label' => qa_lang_html('admin/refill_events'),
			'tags' => 'name="dorefillevents" onclick="return qa_recalc_click(this.name, this, ' . qa_js(qa_lang_html('admin/recalc_stop')) . ', \'refill_events_note\');"',
			'note' => '<span id="refill_events_note">' . qa_lang_html('admin/refill_events_note') . '</span>',
		),

		'recalc_categories' => array(
			'label' => qa_lang_html('admin/recalc_categories'),
			'tags' => 'name="dorecalccategories" onclick="return qa_recalc_click(this.name, this, ' . qa_js(qa_lang_html('admin/recalc_stop')) . ', \'recalc_categories_note\');"',
			'note' => '<span id="recalc_categories_note">' . qa_lang_html('admin/recalc_categories_note') . '</span>',
		),

		'delete_hidden' => array(
			'label' => qa_lang_html('admin/delete_hidden'),
			'tags' => 'name="dodeletehidden" onclick="return qa_recalc_click(this.name, this, ' . qa_js(qa_lang_html('admin/delete_stop')) . ', \'delete_hidden_note\');"',
			'note' => '<span id="delete_hidden_note">' . qa_lang_html('admin/delete_hidden_note') . '</span>',
		),
	),

	'hidden' => array(
		'code' => qa_get_form_security_code('admin/recalc'),
	),
);

if (!qa_using_categories())
	unset($qa_content['form_2']['buttons']['recalc_categories']);

$qa_content['script_rel'][] = 'qa-content/qa-admin.js?' . QA_VERSION;
$qa_content['script_var']['qa_warning_recalc'] = qa_lang('admin/stop_recalc_warning');

$qa_content['script_onloads'][] = array(
	"qa_version_check('https://raw.githubusercontent.com/q2a/question2answer/master/VERSION.txt', " . qa_js(qa_html(QA_VERSION), true) . ", 'q2a-version', true);"
);

$qa_content['navigation']['sub'] = qa_admin_sub_navigation();


return $qa_content;
