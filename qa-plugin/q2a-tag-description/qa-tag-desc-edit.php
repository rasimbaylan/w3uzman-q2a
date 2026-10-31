<?php

class qa_tag_descriptions_edit_page {
	
	function match_request($request)
	{
		$parts=explode('/', $request);
		
		return $parts[0]=='tag-edit';
	}
	
	function process_request($request)
	{
		$parts=explode('/', $request);
		$tag=isset($parts[1]) ? $parts[1] : '';
		if ($tag === '') { return include QA_INCLUDE_DIR.'qa-page-not-found.php'; }
		
		$qa_content=qa_content_prepare();			
		$qa_content['title']=qa_lang_html_sub('plugin_tag_desc/edit_desc_for_x', qa_html($tag));
		
		if (qa_user_permit_error('plugin_tag_desc_permit_edit')) {
			$qa_content['error']=qa_lang_html('users/no_permission');
			return $qa_content;
		}

		require_once QA_INCLUDE_DIR.'qa-db-metas.php';
		
		if (qa_clicked('dosave')) {
			if (!qa_is_http_post() || !qa_check_form_security_code('tag-description-edit', qa_post_text('code'))) {
				$qa_content['error'] = qa_lang_html('misc/form_security_reload');
			} else {
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			$taglc=qa_strtolower($tag);
			qa_db_tagmeta_set($taglc, 'title', qa_post_text('tagtitle'));
			qa_db_tagmeta_set($taglc, 'description', qa_sanitize_html((string) qa_post_text('tagdesc')));
			qa_db_tagmeta_set($taglc, 'icon', qa_post_text('tagicon'));
			qa_redirect('tag/'.$tag);
			}
		}

		$qa_content['form']=array(
			'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
			
			'style' => 'tall', // could be 'wide'
			'hidden' => array('code' => qa_get_form_security_code('tag-description-edit')),
			
			
			'fields' => array(		
				array(
					'label' => 'Başlık:',
					'type' => 'text',
					'rows' => 2,
					'tags' => 'NAME="tagtitle" ID="tagtitle"',
					'value' => qa_html(qa_db_tagmeta_get($tag, 'title')),
				),
				array(
					'label' => 'Açıklama:',
					'type' => 'text',
					'rows' => 4,
					'tags' => 'NAME="tagdesc" ID="tagdesc"',
					'value' => qa_html(qa_db_tagmeta_get($tag, 'description')),
				),
				array(
					'label' => 'Simge görselinin adresi:',
					'type' => 'text',
					'rows' => 1,
					'tags' => 'NAME="tagicon" ID="tagicon"',
					'value' => qa_html(qa_db_tagmeta_get($tag, 'icon')),
				),
			),			
			'buttons' => array(
				array(
					'tags' => 'NAME="dosave"',
					'label' => qa_lang_html('plugin_tag_desc/save_desc_button'),
				),
			),			
		);
		
		$qa_content['focusid']='tagdesc';

		return $qa_content;
	}
	
}
