<?php

class qa_html_theme_layer extends qa_html_theme_base
{

	function head_css() 
	{
		qa_html_theme_base::head_css();			
		$this->output('<link rel="stylesheet" href="' . qa_html(qa_path_to_root() . 'qa-plugin/' . SOCIAL_SHARE_PLUGIN_DIR_NAME . '/w3-share.css?v=1') . '">');
		
	}

    function head_script()
    {
        qa_html_theme_base::head_script();
        $this->output('<script defer src="' . qa_html(qa_path_to_root() . 'qa-plugin/' . SOCIAL_SHARE_PLUGIN_DIR_NAME . '/w3-share.js?v=1') . '"></script>');
    }

	function q_view_buttons($q_view)
	{
		if ((int)qa_opt(qa_sss_opt::BUTTON_STATUS)) {
			$page_url = urlencode(qa_q_path($q_view['raw']['postid'], $q_view['raw']['title'], true));
			$page_title = urlencode($q_view['raw']['title']);
			$social_button = ami_generate_social_buttons(array(
					'{{page_url}}'   => $page_url ,
					'{{page_title}}' => $page_title ,
				), $this->template );
			$this->output($social_button) ;
		}
		qa_html_theme_base::q_view_buttons($q_view);
	}

}

/*
	Omit PHP closing tag to help avoid accidental output
*/
