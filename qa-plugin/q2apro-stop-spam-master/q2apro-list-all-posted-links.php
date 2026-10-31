<?php

	class q2apro_list_all_posted_links {
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		// for display in admin interface under admin/pages
		function suggest_requests() 
		{	
			return array(
				array(
					'title' => 'Paylaşılan bağlantılar', // title of page
					'request' => 'listlinks', // request name
					'nav' => null, // Administrative report, not a public navigation item.
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='listlinks') {
				return true;
			}

			return false;
		}

		function process_request($request)
		{
		
			/* start */
			$qa_content=qa_content_prepare();
			$qa_content['title'] = 'Paylaşılan bağlantılar';
			$qa_content['head_lines'][] = '<meta name="robots" content="noindex, nofollow">';

			// return if not admin!
			if(qa_get_logged_in_level() < QA_USER_LEVEL_ADMIN) {
				http_response_code(403);
				$qa_content['error'] = 'Bu raporu görüntüleme yetkiniz bulunmuyor.';
				return $qa_content;
			}
			
			// query 
			$queryAllPosts = qa_db_query_sub('SELECT postid,type,parentid,created,userid,content
											FROM `^posts`
											ORDER BY created DESC'); 

			// initiate output string
			$postlisting = '<table class="linklisting"> 
								<thead><tr>
									<th style="width:10%;">Tarih</th> 
									<th style="width:10%;">İçerik / Kimlik</th> 
									<th style="width:15%;">Paylaşan</th> 
									<th style="width:65%;">Bağlantılar</th> 
								</tr></thead>';

			// for function qa_post_userid_to_handle
			require_once QA_INCLUDE_DIR.'qa-app-posts.php';
			
			// $ignorelinks = array(qa_opt('site_url'),'www.echteinfach.tv', 'wikipedia.org', 'wolframalpha.com', 'wolfram.com');
			$ignorelinks = array(qa_opt('site_url'));
			
			while( ($post = qa_db_read_one_assoc($queryAllPosts,true)) !== null ) {
				// clear link array
				$links = array();
				// get links from post content
				$links = q2apro_list_all_getUrls($post['content']);
				if(count($links)>0) {
					// remove duplicate links
						$links = array_values(array_unique($links));
					$linkstring = '';
					for($i=0;$i<count($links);$i++) {
						if(isset($links[$i])) {
							// OPTION: dont display own forum links and other links specified
							$ignore = false;
							// check against list of ignore words
							foreach($ignorelinks as $ignorelink) {
								if(strpos($links[$i],$ignorelink) !== false) {
									$ignore = true;
								}
							}
							if(!$ignore) {
								// sometimes the string contains still html tags
								$url = strip_tags($links[$i]);
								$linkstring .= '<a class="q2apro_link" target="_blank" rel="noopener noreferrer" href="'.qa_html($url).'">'.qa_html($url).'</a>';
							}
						}
					}
					// display if links
					if(!empty($linkstring)) {
						$postlisting .= '<tr>
							<td>'.qa_html(substr($post['created'],0,10)).'</td> 
							<td>'.qa_html($post['type']).': '.(int)$post['postid'].'</td> 
							<td>'.(isset($post['userid']) ? qa_html(qa_post_userid_to_handle($post['userid'])) : 'Misafir').'</td> 
							<td>'.$linkstring.'</td> 
							</tr>';
					}
				}
			}
			$postlisting .= '</table>';

			
			// output frontend
			$qa_content['custom'] = ''; // init
			
			$qa_content['custom'] .=  $postlisting;
			
			// make newest users list bigger on page
			$qa_content['custom'] .= '<style type="text/css">
			table.linklisting {
				background:#F5F5F5;
				margin:30px 0 15px;
				text-align:left;
				border-collapse:collapse; 
				table-layout: fixed;
				width:100%; 
			} 
			table.linklisting thead tr th {
				background-color:#cfc;
				border:1px solid #CCC;
				padding:4px;
			} 
			table.linklisting td {
				border:1px solid #CCC;padding:1px 10px;line-height:25px;
				word-wrap:break-word;
			}
			table.linklisting tr:hover{background:#ffc} 
			table.linklisting th {text-align:center; } 
			table.linklisting td img { 
				border:1px solid #DDD !important; margin-right:5px;
			} 
			table.linklisting td a.q2apro_link {
				display:block;
			}
			</style>';
			
			return $qa_content;
		}
		
	};
	
	function q2apro_list_all_getUrls($string) {
		// $regex = '/https?\:\/\/[^\" ]+/i';
		$regex = '#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#';
		preg_match_all($regex, $string, $matches);
		return ($matches[0]);
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
