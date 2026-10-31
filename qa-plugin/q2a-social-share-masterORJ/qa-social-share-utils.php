<?php

function ami_generate_social_buttons($data , $template = null )
{
    $url = urldecode(isset($data['{{page_url}}']) ? $data['{{page_url}}'] : '');
    $title = urldecode(isset($data['{{page_title}}']) ? $data['{{page_title}}'] : '');
    if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), array('http', 'https'), true)) { return ''; }
    $u = rawurlencode($url); $t = rawurlencode($title);
    $items = array(
        array('whatsapp', 'WhatsApp', 'https://wa.me/?text=' . rawurlencode($title . ' ' . $url), true),
        array('x', 'X', 'https://x.com/intent/tweet?url=' . $u . '&text=' . $t, qa_opt(qa_sss_opt::TW_BUTTON)),
        array('facebook', 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $u, qa_opt(qa_sss_opt::FB_BUTTON)),
        array('linkedin', 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u, qa_opt(qa_sss_opt::LI_BUTTON)),
        array('telegram', 'Telegram', 'https://t.me/share/url?url=' . $u . '&text=' . $t, true),
    );
    $html = '<section class="w3-share" aria-label="İçeriği paylaş"><span class="w3-share-label">Paylaş</span><div class="w3-share-actions">';
    foreach ($items as $item) {
        if (!$item[3]) { continue; }
        $html .= '<a class="w3-share-button w3-share--' . $item[0] . '" href="' . qa_html($item[2]) . '" target="_blank" rel="noopener noreferrer nofollow" aria-label="' . $item[1] . ' üzerinde paylaş" title="' . $item[1] . ' üzerinde paylaş">' . w3_share_icon($item[0]) . '<span>' . $item[1] . '</span></a>';
    }
    return $html . '<button class="w3-share-button w3-share-copy" type="button" data-share-url="' . qa_html($url) . '" aria-label="Bağlantıyı kopyala">' . w3_share_icon('copy') . '<span>Bağlantıyı kopyala</span></button></div><span class="w3-share-status" role="status" aria-live="polite"></span></section>';
}

function w3_share_icon($name)
{
    $icons = array(
        'whatsapp' => '<path d="M20.5 11.8a8.5 8.5 0 0 1-12.6 7.5L3 20.8l1.5-4.7a8.5 8.5 0 1 1 16-4.3Z"></path><path d="M8.5 7.5c-.5.2-1 1-.8 2 .5 2.5 3.3 5.3 5.8 5.8 1 .2 1.8-.3 2-.8l.5-1-2.4-1.1-.8.8c-1.3-.6-2.3-1.6-2.9-2.9l.8-.8-1.1-2.4-.9.4Z"></path>',
        'x' => '<path d="m5 4 14 16M19 4 5 20M3 4h4l14 16h-4L3 4Z"></path>',
        'facebook' => '<path d="M14.5 21v-8h2.8l.4-3h-3.2V8.2c0-.9.3-1.5 1.6-1.5H18V4.1c-.4-.1-1.6-.1-2.7-.1-2.7 0-4.3 1.6-4.3 4.4V10H8v3h3v8"></path>',
        'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="3"></rect><path d="M7 10v7m0-10v.1M11 17v-7m0 3c0-4 6-4 6 0v4"></path>',
        'telegram' => '<path d="m3 11 18-7-4 17-6-6-4 3 1-6 13-8-10 11M3 11l5 1"></path>',
        'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"></rect><path d="M15 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h3"></path>',
    );
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[$name] . '</svg>';
}

function get_social_button($type , $data , $template = null )
{
	if ($type == 'vk' && $template == 'question') {
		// if it is a question url for vk.com trim the title from the url as it is not supported 
		$url = qa_opt('site_url').qa_request() ;
		$data['{{page_url}}'] = urlencode(substr($url, 0, strrpos( $url, '/' )+1)) ;
	}

	$url       = qa_sss_opt::get_url_subs($type , $data);
	$class     = ami_get_social_class($type);
	$icon      = ami_sss_icon_i($type);
	$title     = qa_lang('sss_lang/'.$type);	
	$text      = qa_lang('sss_lang/'.$type);
	$separator = ami_sss_get_separator($type);
	
	return qa_sss_opt::get_social_btn_sub(qa_opt(qa_sss_opt::SHARE_TYPE_OPTION) , array(
		'{{url}}'       => $url ,
		'{{class}}'     => $class ,
		'{{icon}}'      => $icon ,
		'{{title}}'     => $title ,
		'{{text}}'      => $text ,
		'{{separator}}' => $separator ,
		));	
}

function ami_get_social_class($social_type)
{
	$class = '' ;
	$button_with_text = 'btn btn-social btn-' ;
	$button_with_text_no_icon = 'btn btn-social btn-no-icon btn-' ;
	$button_no_text = 'btn btn-social-icon btn-' ;
	$type = qa_opt(qa_sss_opt::SHARE_TYPE_OPTION) ; 
	switch ($type) {
		case qa_sss_opt::SHARE_TYPE_IMAGE:
			$class .= 'qa-sss-img-'.$social_type ;
			break;
		case qa_sss_opt::SHARE_TYPE_TEXT:
			$class .= 'qa-sss-text-'.$social_type ;
			break;
		case qa_sss_opt::SHARE_TYPE_COLORED_BTNS:
			$class .= $button_with_text_no_icon . $social_type ;
			break;
		case qa_sss_opt::SHARE_TYPE_COLORED_BTNS_WITH_ICON:
			$class .= $button_with_text . $social_type ;
			break;
		case qa_sss_opt::SHARE_TYPE_ANIMATED_FI:
			$class .= $button_no_text . $social_type ;
			$class .= ' btn-sqr-animated' ;
			break;

		case qa_sss_opt::SHARE_TYPE_FI_SQ:
			$class .= $button_no_text . $social_type ;
			$class .= ' btn-sqr' ;
			break;
		case qa_sss_opt::SHARE_TYPE_FI_SEMI_ROUNDED:
			$class .= $button_no_text . $social_type ;
			$class .= ' btn-semi-rounded btn-shadowed' ;
			break;
		case qa_sss_opt::SHARE_TYPE_FI_ROUNDED:
			$class .= $button_no_text . $social_type ;
			$class .= ' btn-rounded' ;
			break;
		default:
			break;
	}
	return $class ;

}

function ami_print_social_buttons($value='')
{
	echo ami_generate_social_buttons();
}

function ami_sss_icon($type)
{
	$icon = "" ;
	switch ($type) {
		case 'fb':
			$icon = 'icon-facebook' ; 
			break;
		case 'gp':
			$icon = 'icon-google-plus' ; 
			break;
		case 'tw':
			$icon = 'icon-twitter' ; 
			break;
		case 'li':
			$icon = 'icon-linkedin-square' ; 
			break;
		case 're':
			$icon = 'icon-reddit' ; 
			break;
		case 'vk':
			$icon = 'icon-vk' ; 
			break;
		case 'em':
			$icon = 'icon-envelope-o' ; 
			break;
		default:
			break;
	}
	return $icon ;
}

function ami_sss_icon_i($type)
{
	return '<i class="'.ami_sss_icon($type).'"></i>';
}

function ami_sss_icon_span($type)
{
	return '<span class="'.ami_sss_icon($type).'"></span>';
}

function ami_sss_class_for_type($class , $social_type)
{
	return $class . '-' . $social_type ;
}

function ami_sss_get_separator( $type )
{
	$all_array = array(
			'gp' => qa_opt(qa_sss_opt::GP_BUTTON ),
			'fb' => qa_opt(qa_sss_opt::FB_BUTTON ),
			'tw' => qa_opt(qa_sss_opt::TW_BUTTON ),
			'li' => qa_opt(qa_sss_opt::LI_BUTTON ),
			're' => qa_opt(qa_sss_opt::RE_BUTTON ),
			'vk' => qa_opt(qa_sss_opt::VK_BUTTON ),
			'em' => qa_opt(qa_sss_opt::EM_BUTTON ),
		);

	$how_many = ami_sss_how_many($all_array) ;
	if (ami_sss_is_first($all_array , $type , $how_many)) {
			return "";
	}
	
	if (ami_sss_is_last($all_array , $type , $how_many)) {
		return qa_lang('sss_lang/or');
	}

	return ",";

}

function ami_sss_is_last( $all_array , $type , $how_many )
{
	if ($how_many <= 1) {
		return true ;
	}
	$last  = false ;
	$found = false ;

	foreach ($all_array as $key => $value) {
		if ($key == $type && !!$value && !$found ) {
			$last  = true ;
			$found = true ;
		}
		if ($found && $key != $type && !!$value) {
				$last = false ;
		} 
	}
	return $last ;
}

function ami_sss_is_first($all_array , $type , $how_many)
{

	$found = false ;
	$first = false  ;
	$found_index = 0 ;
	if ($how_many == 1) {
		return true ;
	}else {
		foreach ($all_array as $key => $value) {
			if (!!$value) {
				if ($found_index==0 && $key == $type) {
					return true ;
				}
				$found_index++ ;
			}
		}
	}
	return false ;
}

function ami_sss_how_many($all_array)
{
	$num = 0 ;
	foreach ($all_array as $key => $value) {
		if (!!$value) {
			$num++ ;
		}
	}
	return $num ;
}
