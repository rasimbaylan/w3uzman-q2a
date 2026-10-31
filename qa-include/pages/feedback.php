<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for feedback page


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
	header('Location: ../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'app/captcha.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'util/string.php';


// Get useful information on the logged in user

$userid = qa_get_logged_in_userid();

if (isset($userid) && !QA_FINAL_EXTERNAL_USERS) {
	list($useraccount, $userprofile) = qa_db_select_with_pending(
		qa_db_user_account_selectspec($userid, true),
		qa_db_user_profile_selectspec($userid, true)
	);
}

$usecaptcha = qa_opt('captcha_on_feedback') && qa_user_use_captcha();

// Check feedback is enabled and the person isn't blocked

if (!qa_opt('feedback_enabled'))
	return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

if (qa_user_permit_error()) {
	$qa_content = qa_content_prepare();
	$qa_content['error'] = qa_lang_html('users/no_permission');
	return $qa_content;
}


// Send the feedback form


$feedbacksent = false;
$contactemail = 'bilgi@w3uzman.com';

$errors = array();
$topics = array('general' => 'Görüş ve öneri', 'content' => 'İçerik düzeltme', 'technical' => 'Teknik sorun', 'business' => 'Reklam ve iş birliği');
$sessionkey = function_exists('qa_session_var_suffix') ? 'w3_feedback_sent_' . qa_session_var_suffix() : null;
$newsletterprefill = false;

// The footer prepares an explicit subscription request; it never subscribes or
// sends mail silently. The visitor completes the normal protected contact form.
if (qa_clicked('w3_newsletter')) {
	if ((isset($_POST['newsletter_email']) && !is_scalar($_POST['newsletter_email'])) || (isset($_POST['newsletter_code']) && !is_scalar($_POST['newsletter_code']))) {
		$pageerror = 'E-posta adresinizi kontrol edip yeniden deneyin.';
	} elseif (!qa_check_form_security_code('w3_newsletter', qa_post_text('newsletter_code'))) {
		$pageerror = qa_lang_html('misc/form_security_again');
	} elseif (!qa_email_validate((string) qa_post_text('newsletter_email')) || qa_strlen((string) qa_post_text('newsletter_email')) > 254) {
		$errors['email'] = 'Lütfen geçerli bir e-posta adresi yazın.';
	} else {
		$inemail = qa_post_text('newsletter_email');
		$intopic = 'general';
		$inmessage = 'Teknoloji bülteniniz için abonelik talebimi iletiyorum.';
		$newsletterprefill = true;
	}
}

if (qa_clicked('dofeedback')) {
	require_once QA_INCLUDE_DIR . 'app/emails.php';

	$malformed = false;
	foreach (array('message', 'name', 'email', 'referer', 'topic', 'website', 'code') as $key) {
		if (isset($_POST[$key]) && !is_scalar($_POST[$key])) { $malformed = true; }
	}
	$inmessage = $malformed ? '' : qa_post_text('message');
	$inname = $malformed ? '' : qa_post_text('name');
	$inemail = $malformed ? '' : qa_post_text('email');
	$inreferer = $malformed ? '' : qa_post_text('referer');
	$intopic = $malformed ? '' : qa_post_text('topic');

	if ($malformed)
		$pageerror = 'Form verileri geçersiz. Lütfen alanları kontrol edin.';
	elseif (!qa_check_form_security_code('feedback', qa_post_text('code')))
		$pageerror = qa_lang_html('misc/form_security_again');
	elseif (strlen((string) qa_post_text('website')))
		$pageerror = 'İsteğiniz doğrulanamadı. Lütfen formu yeniden açıp deneyin.';
	elseif ($sessionkey !== null && isset($_SESSION[$sessionkey]) && time() - (int) $_SESSION[$sessionkey] < 60)
		$pageerror = 'Mesajınız kısa süre önce gönderildi. Yeni bir mesaj için lütfen bir dakika bekleyin.';

	else {
		if (qa_strlen((string) $inname) < 2 || qa_strlen((string) $inname) > 100 || preg_match('/[\r\n]/', (string) $inname))
			$errors['name'] = 'Adınızı 2–100 karakter arasında yazın.';
		if (!qa_email_validate((string) $inemail) || qa_strlen((string) $inemail) > 254)
			$errors['email'] = 'Size yanıt verebilmemiz için geçerli bir e-posta adresi yazın.';
		if (qa_strlen((string) $inmessage) < 20 || qa_strlen((string) $inmessage) > 6000)
			$errors['message'] = 'Mesajınızı 20–6.000 karakter arasında yazın.';
		if (!isset($topics[$intopic]))
			$errors['topic'] = 'Lütfen geçerli bir konu seçin.';

		if ($usecaptcha)
			qa_captcha_validate_post($errors);

		if (empty($errors)) {
			$subs = array(
				'^message' => $inmessage,
				'^name' => empty($inname) ? '-' : $inname,
				'^email' => empty($inemail) ? '-' : $inemail,
				'^previous' => empty($inreferer) ? '-' : $inreferer,
				'^url' => isset($userid) ? qa_path_absolute('user/' . qa_get_logged_in_handle()) : '-',
				'^ip' => qa_remote_ip_address(),
				'^browser' => @$_SERVER['HTTP_USER_AGENT'],
			);

			if (qa_send_email(array(
				'fromemail' => qa_opt('from_email'),
				'fromname' => $inname,
				'replytoemail' => qa_email_validate(@$inemail) ? $inemail : null,
				'replytoname' => $inname,
				'toemail' => $contactemail,
				'toname' => qa_opt('site_title'),
				'subject' => qa_opt('site_title') . ' — ' . $topics[$intopic],
				'body' => "Konu: " . $topics[$intopic] . "\nAd: " . $inname . "\nE-posta: " . $inemail . "\n\nMesaj:\n" . $inmessage . "\n\nÖnceki sayfa: " . $subs['^previous'] . "\nKullanıcı: " . $subs['^url'] . "\nIP: " . $subs['^ip'],
				'html' => false,
			))) {
				$feedbacksent = true;
				if ($sessionkey !== null) { $_SESSION[$sessionkey] = time(); }
			} else {
				$pageerror = 'Mesajınız şu anda gönderilemedi. Yazdıklarınız korundu; lütfen daha sonra tekrar deneyin.';
			}

			if ($feedbacksent) { qa_report_event('feedback', $userid, qa_get_logged_in_handle(), qa_cookie_get(), array(
				'email' => $inemail,
				'name' => $inname,
				'message' => $inmessage,
				'previous' => $inreferer,
				'browser' => @$_SERVER['HTTP_USER_AGENT'],
			)); }
		}
	}
}


// Prepare content for theme

$qa_content = qa_content_prepare();

$qa_content['title'] = 'Bize Ulaşın';
$qa_content['description'] = 'W3Uzman için görüş, öneri, içerik düzeltme, teknik destek ve iş birliği taleplerinizi iletin.';
$qa_content['canonical'] = qa_path_html('feedback', null, qa_opt('site_url'));
$qa_content['w3_contact_email'] = $contactemail;
$qa_content['w3_newsletter_prefill'] = $newsletterprefill;

$qa_content['error'] = @$pageerror;

$qa_content['form'] = array(
	'tags' => 'method="post" action="' . qa_self_html() . '"',

	'style' => 'tall',

	'fields' => array(
		'name' => array(
			'id' => 'w3-contact-name-row',
			'label' => '<label for="w3-contact-name">Adınız soyadınız</label>',
			'tags' => 'name="name" id="w3-contact-name" autocomplete="name" placeholder="Adınız soyadınız" minlength="2" maxlength="100" required',
			'value' => qa_html(isset($inname) ? $inname : @$userprofile['name']),
			'error' => qa_html(isset($errors['name']) ? $errors['name'] : null),
		),
		'email' => array(
			'id' => 'w3-contact-email-row',
			'type' => 'custom',
			'label' => '<label for="w3-contact-email">E-posta adresiniz</label>',
			'value' => qa_html(isset($inemail) ? $inemail : qa_get_logged_in_email()),
			'html' => '<input type="email" class="qa-form-tall-text" id="w3-contact-email" name="email" autocomplete="email" maxlength="254" placeholder="ornek@eposta.com" required value="' . qa_html(isset($inemail) ? $inemail : qa_get_logged_in_email()) . '">',
			'error' => qa_html(isset($errors['email']) ? $errors['email'] : null),
		),
		'topic' => array(
			'id' => 'w3-contact-topic-row', 'type' => 'select', 'loose' => true, 'match_by' => 'key',
			'label' => '<label for="w3-contact-topic">Konu</label>',
			'tags' => 'name="topic" id="w3-contact-topic" required',
			'options' => $topics, 'value' => isset($intopic) && isset($topics[$intopic]) ? $intopic : 'general',
			'error' => qa_html(isset($errors['topic']) ? $errors['topic'] : null),
		),
		'message' => array(
			'id' => 'w3-contact-message-row',
			'label' => '<label for="w3-contact-message">Mesajınız</label>',
			'tags' => 'name="message" id="w3-contact-message" minlength="20" maxlength="6000" aria-describedby="w3-contact-counter" placeholder="Nasıl yardımcı olabiliriz? Varsa ilgili sayfanın bağlantısını da ekleyin." required',
			'value' => qa_html(@$inmessage),
			'rows' => 6,
			'note' => '<span id="w3-contact-counter" class="w3-contact-counter">' . qa_strlen((string) @$inmessage) . ' / 6.000</span>',
			'error' => qa_html(isset($errors['message']) ? $errors['message'] : null),
		),

		'website' => array(
			'id' => 'w3-contact-trap', 'type' => 'custom',
			'html' => '<div aria-hidden="true"><label for="w3-contact-website">Bu alanı boş bırakın</label><input type="text" name="website" id="w3-contact-website" tabindex="-1" autocomplete="off"></div>',
		),
	),

	'buttons' => array(
		'send' => array(
			'label' => 'Mesajı gönder →',
			'note' => 'E-posta adresiniz yalnızca size dönüş yapabilmemiz için kullanılır.',
		),
	),

	'hidden' => array(
		'dofeedback' => '1',
		'code' => qa_get_form_security_code('feedback'),
		'referer' => qa_html(isset($inreferer) ? $inreferer : @$_SERVER['HTTP_REFERER']),
	),
);

if ($usecaptcha && !$feedbacksent)
	qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], $errors);


unset($qa_content['focusid']);

if ($feedbacksent) {
	$qa_content['form']['ok'] = 'Teşekkürler! Mesajınız iletildi. Gerekli durumlarda belirttiğiniz e-posta adresinden size dönüş yapılacaktır.';
	$qa_content['form']['fields'] = array();
	unset($qa_content['form']['buttons']);
}


return $qa_content;
