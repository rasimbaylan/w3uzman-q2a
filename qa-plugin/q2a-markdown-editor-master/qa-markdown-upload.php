<?php

class qa_markdown_upload
{

	private $imguplopt = 'markdown_uploadimage';

	public function match_request($request)
	{
		return ($request == 'qa-markdown-upload');
	}

	public function process_request($request)
	{
		$message = '';
		$url = '';
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		$code = qa_post_text('code');
		if ($code === null) { $code = qa_get('code'); }
		if (!qa_is_http_post() || !qa_check_form_security_code('qa-markdown-upload', $code)) {
			http_response_code(qa_is_http_post() ? 403 : 405);
			if (!qa_is_http_post()) { header('Allow: POST'); }
			echo json_encode(array('error' => qa_lang('misc/form_security_reload')));
			return null;
		}

		if (is_array($_FILES) && count($_FILES)) {
			$uploadimg = qa_opt($this->imguplopt) === '1';

			if (!$uploadimg) {
				$message = qa_lang('users/no_permission');
				
				header('Content-type: application/json');
				echo json_encode([
					'error'=>$message
				]);
			} else {
				require_once QA_INCLUDE_DIR.'app/upload.php';

				$upload = qa_upload_file_one(
					qa_get_max_upload_size(), // do not restrict upload size
					true, // force upload to image only
					500, // max width (px)
					500 // max height (px)
				);

				$message = @$upload['error'];
				$url = @$upload['bloburl'];
				$id = @$upload['blobid'];


				header('Content-type: application/json');
				echo json_encode([
					'error'=>$message,
					'url'=>$url,
					'id'=>$id,
				]);
			}
		}

		return null;
	}
}
