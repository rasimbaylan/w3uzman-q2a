<?php

class qa_modern_upload
{
    public function match_request($request)
    {
        return $request === 'modern-editor-upload';
    }

    public function process_request($request)
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if (!qa_is_logged_in()) {
            http_response_code(401);
            echo json_encode(array('error' => 'Resim yüklemek için giriş yapmalısınız.'));
            return null;
        }

        if (!qa_is_http_post()) {
            header('Allow: POST');
            http_response_code(405);
            echo json_encode(array('error' => 'Resim yükleme için POST isteği gereklidir.'));
            return null;
        }
        if (!qa_check_form_security_code('modern-editor-upload', qa_post_text('code'))) {
            http_response_code(403);
            echo json_encode(array('error' => 'Güvenlik doğrulaması geçersiz. Sayfayı yenileyip tekrar deneyin.'));
            return null;
        }

        if (!is_array($_FILES) || !count($_FILES)) {
            http_response_code(400);
            echo json_encode(array('error' => 'Yüklenecek resim bulunamadı.'));
            return null;
        }

        require_once QA_INCLUDE_DIR . 'app/upload.php';
        $maxSize = qa_opt('wysiwyg_editor_upload_max_size');
        if (!$maxSize) $maxSize = min(qa_get_max_upload_size(), 5242880);
        $upload = qa_upload_file_one($maxSize, true, 1600, null);

        if (isset($upload['error'])) {
            echo json_encode(array('error' => $upload['error']));
        } else {
            echo json_encode(array('url' => $upload['bloburl']));
        }

        return null;
    }
}

