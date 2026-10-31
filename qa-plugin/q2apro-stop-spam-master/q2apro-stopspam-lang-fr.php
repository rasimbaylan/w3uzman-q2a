<?php

/*
	Plugin Name: Stop Spam
	Plugin URI: http://www.q2apro.com/plugins/stop-spam
	Plugin Description: An easy to use customizable captcha with a honeypot that keeps 99 % of the spam away
	Plugin Version: 1.0
	Plugin Date: 2014-02-20
	Plugin Author: q2apro.com
	Plugin Author URI: http://www.q2apro.com
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: http://www.q2apro.com/pluginupdate?id=7
	
	Licence: Copyright © q2apro.com - All rights reserved

*/


	return array(
		// default
		'enable_plugin' => 'Activer le plugin Stop-Spam-Captcha',
		'contact' => 'Si vous avez des questions, visitez ^1q2apro.com^2',
		
		'note' => 'Note:',
		'disable_captcha' => 'Si vous voulez désactiver le captcha, Allez dans le menu ^1Admin > Spam^2 et choisissez un autre module de captcha.',
		
		// plugin
		'label_q' => 'Question du captcha :',
		'label_a' => 'Option de réponse 1 :',
		'label_a2' => 'Option de réponse 2 :',
		'label_error' => 'Message d\'erreur',
		'captcha_question' => 'Quelle est la couleur du ciel?',
		'captcha_answer' => 'bleu',
		'captcha_answer2' => 'rouge',
		'captcha_errormsg' => 'Votre réponse est incorrecte.',
		'emails_notallowed' => '*Les e-mails ne sont pas autorisés*',
		'is_duplicate' => 'Une question avec ce titre existe déjà. Ajouter plus de détails s\'il vous plaît.',
		'emaildomain_unwanted' => 'Les boites e-mails jetables sont indésirables.',
		'contains_unwanted' => 'Votre message contient des mots indésirables :',
		'contains_unwanted_lang' => 'Votre message contient une langue refusée :',
		'too_many_links' => 'Votre message contient trop de liens.',
		'notify_mentioning' => 'Avertir l\'administrateur par e-mail si cette mention est trouvée :',
		'allow_links_count' => 'Nombre de liens authorisés par message :',
		'mask_emails' => 'Supprime les adresses e-mails des messages. Exemple : spam@forum.com deviendra *Emails are not allowed*',
		'remove_linebreaks' => 'Supprime les sauts de ligne trop nombreux afin qu\'il n\'en reste qu\'un seul. Supprime aussi les lignes vides à la fin des messages.',
		'prevent_spamvotes' => 'Empêche les utilisateurs avec la même IP de voter pour leurs messages ou de sélectionner leurs réponses comme étant les meilleurs.',
		'open_page' => 'Ouvrir la page:',
		'block_guestswithoutip' => 'Ne pas authoriser les invités sans IP à envoyer des messages dans le forum.',
		'moderate_post_with_links' => 'Modérer les messages (réponses, commentaires) avec des liens. Les envoies dans la ^1moderate queue^2.',
	);
	

/*
	Omit PHP closing tag to help avoid accidental output
*/