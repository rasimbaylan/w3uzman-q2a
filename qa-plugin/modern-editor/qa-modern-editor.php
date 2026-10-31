<?php

class qa_modern_editor
{
    private $urltoroot;

    public function load_module($directory, $urltoroot)
    {
        $this->urltoroot = $urltoroot;
    }

    public function calc_quality($content, $format)
    {
        return $format === 'html' ? 1.0 : 0.9;
    }

    public function get_field(&$qa_content, $content, $format, $fieldname, $rows, $autofocus = false)
    {
        $script = $this->urltoroot . 'assets/quill.js?v=2.0.3';
        $quillCss = $this->urltoroot . 'assets/quill.snow.css?v=2.0.3';
        $editorCss = $this->urltoroot . 'assets/modern-editor.css?v=2';

        // Load Quill immediately with the editor markup. The editor bootstrap
        // runs from Q2A's window-load hook, so this avoids script queue/defer
        // ordering differences between themes and cached pages.
        if (!isset($qa_content['css_src']) || !in_array($quillCss, $qa_content['css_src'])) {
            $qa_content['css_src'][] = $quillCss;
            $qa_content['css_src'][] = $editorCss;
        }

        if ($format === 'html') {
            $html = $content;
        } elseif (strlen((string)$content)) {
            require_once QA_INCLUDE_DIR . 'app/format.php';
            $html = qa_viewer_html($content, $format, array());
        } else {
            $html = '';
        }

        $id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldname);

        return array(
            'type' => 'custom',
            'html' =>
                '<script src="' . qa_html($script) . '"></script>' .
                '<div class="qa-modern-editor-wrap">' .
                '<textarea class="qa-modern-editor-value" name="' . qa_html($fieldname) . '" id="' . qa_html($id) . '">' . qa_html($html) . '</textarea>' .
                '<div class="qa-modern-editor" id="' . qa_html($id) . '_modern"></div>' .
                '<input class="qa-modern-editor-file" id="' . qa_html($id) . '_modern_file" type="file" accept="image/jpeg,image/png,image/gif" aria-label="Editöre JPG, PNG veya GIF yükle">' .
                '<div class="qa-modern-editor-status" id="' . qa_html($id) . '_modern_status" aria-live="polite"></div>' .
                '</div>',
        );
    }

    public function load_script($fieldname)
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldname);
        $uploadUrl = qa_js(qa_path('modern-editor-upload'));
        $uploadCode = qa_js(qa_get_form_security_code('modern-editor-upload'));

        return "(function () {
            window.qaModernEditors = window.qaModernEditors || {};
            var textarea = document.getElementById(" . qa_js($id) . ");
            var container = document.getElementById(" . qa_js($id . '_modern') . ");
            var fileInput = document.getElementById(" . qa_js($id . '_modern_file') . ");
            var status = document.getElementById(" . qa_js($id . '_modern_status') . ");
            if (!textarea || !container || typeof Quill === 'undefined') return;

            var editor = new Quill(container, {
                theme: 'snow',
                placeholder: 'İçeriğinizi buraya yazın…',
                modules: { toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ color: [] }, { background: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ align: [] }],
                    ['blockquote', 'code-block'],
                    ['link', 'image', 'video'],
                    ['clean']
                ] }
            });
            editor.clipboard.dangerouslyPasteHTML(textarea.value || '');
            window.qaModernEditors[" . qa_js($id) . "] = editor;

            var toolbar = editor.getModule('toolbar');
            editor.root.setAttribute('role', 'textbox');
            editor.root.setAttribute('aria-label', 'İçerik düzenleyici');
            editor.root.setAttribute('aria-multiline', 'true');
            var controlNames = { header: 'Başlık düzeyi', bold: 'Kalın', italic: 'İtalik', underline: 'Altı çizili', strike: 'Üstü çizili', color: 'Metin rengi', background: 'Arka plan rengi', list: 'Liste', align: 'Hizalama', blockquote: 'Alıntı', 'code-block': 'Kod bloğu', link: 'Bağlantı', image: 'Resim yükle (JPG, PNG, GIF)', video: 'YouTube videosu', clean: 'Biçimlendirmeyi temizle' };
            toolbar.container.querySelectorAll('button,select').forEach(function (control) {
                Object.keys(controlNames).some(function (key) {
                    if (!control.classList.contains('ql-' + key)) return false;
                    var label = controlNames[key];
                    if (key === 'list') label = control.value === 'ordered' ? 'Numaralı liste' : 'Madde işaretli liste';
                    control.setAttribute('aria-label', label);
                    control.setAttribute('title', label);
                    return true;
                });
            });
            toolbar.addHandler('video', function () {
                var value = window.prompt('YouTube video bağlantısını yapıştırın:');
                if (!value) return;
                try {
                    var url = new URL(value, window.location.href);
                    var videoId = '';
                    if (url.hostname === 'youtu.be') videoId = url.pathname.slice(1).split('/')[0];
                    if (url.hostname === 'youtube.com' || url.hostname === 'www.youtube.com' || url.hostname === 'm.youtube.com') {
                        videoId = url.searchParams.get('v') || (url.pathname.match(/\/(?:shorts|embed)\/([^/?]+)/) || [])[1] || '';
                    }
                    if (!/^[a-zA-Z0-9_-]{11}$/.test(videoId)) throw new Error('invalid');
                    value = 'https://www.youtube.com/embed/' + videoId;
                } catch (error) { status.textContent = 'Geçerli bir YouTube video bağlantısı yazın.'; return; }
                var range = editor.getSelection(true);
                editor.insertEmbed(range.index, 'video', value, 'user');
                editor.setSelection(range.index + 1, 0, 'silent');
            });
            toolbar.addHandler('image', function () { fileInput.click(); });

            function uploadImage(file) {
                if (!file || !/^image\/(?:jpeg|png|gif)$/.test(file.type)) {
                    status.textContent = 'JPG, PNG veya GIF yükleyin. WebP ve SVG dosyasını önce bu biçimlerden birine dönüştürün.';
                    return;
                }
                var data = new FormData();
                data.append('file', file);
                data.append('code', " . $uploadCode . ");
                status.textContent = 'Resim yükleniyor…';
                fetch(" . $uploadUrl . ", { method: 'POST', body: data, credentials: 'same-origin' })
                    .then(function (response) { return response.json(); })
                    .then(function (result) {
                        if (!result.url) throw new Error(result.error || 'Resim yüklenemedi.');
                        var range = editor.getSelection(true);
                        editor.insertEmbed(range.index, 'image', result.url, 'user');
                        editor.setSelection(range.index + 1, 0, 'silent');
                        status.textContent = 'Resim yüklendi.';
                    })
                    .catch(function (error) { status.textContent = error.message; });
            }

            fileInput.addEventListener('change', function () {
                if (!fileInput.files.length) return;
                uploadImage(fileInput.files[0]);
                fileInput.value = '';
            });

            function uploadDataImage(dataUrl) {
                var match = /^data:(image\/(?:jpeg|png|gif|webp));base64,/i.exec(dataUrl || '');
                if (!match) {
                    status.textContent = 'Bu görsel panodan yüklenemedi. Görsel düğmesini kullanarak dosyayı seçin.';
                    return;
                }

                try {
                    var binary = atob(dataUrl.slice(match[0].length));
                    var bytes = new Uint8Array(binary.length);
                    for (var i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
                    uploadImage(new File([bytes], 'panodan-yapistirilan-gorsel.' + match[1].split('/')[1], { type: match[1] }));
                } catch (error) {
                    status.textContent = 'Pano görseli okunamadı. Görsel düğmesini kullanarak dosyayı seçin.';
                }
            }

            // Capture phase is intentional: Quill's own paste handler otherwise
            // converts a copied image into a very large data: URL before this
            // handler gets a chance to upload it.
            editor.root.addEventListener('paste', function (event) {
                var clipboard = event.clipboardData;
                if (!clipboard || !clipboard.items) return;

                for (var i = 0; i < clipboard.items.length; i++) {
                    var item = clipboard.items[i];
                    if (item.kind === 'file' && /^image\//.test(item.type)) {
                        event.preventDefault();
                        uploadImage(item.getAsFile());
                        return;
                    }
                }

                var pastedHtml = clipboard.getData('text/html') || '';
                var imageMatch = /<img\b[^>]*\bsrc\s*=\s*[\"']([^\"']+)[\"']/i.exec(pastedHtml);
                if (imageMatch) {
                    event.preventDefault();
                    uploadDataImage(imageMatch[1]);
                }
            }, true);
        })();";
    }

    public function focus_script($fieldname)
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldname);
        return "if (window.qaModernEditors && window.qaModernEditors[" . qa_js($id) . "]) window.qaModernEditors[" . qa_js($id) . "].focus();";
    }

    public function update_script($fieldname)
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldname);
        return "if (window.qaModernEditors && window.qaModernEditors[" . qa_js($id) . "]) { var editor = window.qaModernEditors[" . qa_js($id) . "]; var draft = document.createElement('div'); draft.innerHTML = editor.root.innerHTML; var images = draft.querySelectorAll('img'); for (var i = 0; i < images.length; i++) { var source = images[i].getAttribute('src') || ''; if (/^(?:data:|blob:)/i.test(source)) images[i].parentNode.removeChild(images[i]); } document.getElementById(" . qa_js($id) . ").value = draft.innerHTML; }";
    }

    public function read_post($fieldname)
    {
        $html = qa_post_text($fieldname);

        // A pasted image can occasionally arrive as the literal, incomplete
        // text "<img" rather than an image element. It is not publishable
        // content, so discard that trailing fragment before sanitizing.
        $html = preg_replace('~<p>\s*&lt;img\s*</p>\s*$~iu', '', $html);
        $html = preg_replace('~<img\b[^>]*$~iu', '', $html);
        $plain = trim(html_entity_decode(strip_tags(str_replace(array('<br>', '<br/>', '<br />'), '', $html))));

        if ($plain === '' && !preg_match('/<(img|iframe)\b/i', $html)) {
            return array('format' => '', 'content' => '');
        }

        return array(
            'format' => 'html',
            'content' => qa_sanitize_html($html, false, true),
        );
    }
}
