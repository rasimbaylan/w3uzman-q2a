(function () {
    'use strict';

    var root = document.getElementById('w3-responsive');
    if (!root) { return; }

    var urlForm = document.getElementById('w3-responsive-url-form');
    var urlInput = document.getElementById('w3-responsive-url');
    var urlError = document.getElementById('w3-responsive-url-error');
    var sizeForm = document.getElementById('w3-responsive-size-form');
    var widthInput = document.getElementById('w3-responsive-width');
    var heightInput = document.getElementById('w3-responsive-height');
    var sizeError = document.getElementById('w3-responsive-size-error');
    var presets = Array.prototype.slice.call(root.querySelectorAll('[data-preset]'));
    var stage = document.getElementById('w3-responsive-stage');
    var viewport = document.getElementById('w3-responsive-viewport');
    var empty = document.getElementById('w3-responsive-empty');
    var dimensions = document.getElementById('w3-responsive-dimensions');
    var scaleLabel = document.getElementById('w3-responsive-scale');
    var rotate = document.getElementById('w3-responsive-rotate');
    var refresh = document.getElementById('w3-responsive-refresh');
    var openLink = document.getElementById('w3-responsive-open');
    var status = document.getElementById('w3-responsive-status');
    var frame = null;
    var currentUrl = '';
    var width = 375;
    var height = 812;
    var selectedPreset = 'mobile';
    var resizePending = false;
    var toolUrl = new URL(root.getAttribute('data-tool-url'), window.location.href);

    function showError(element, message, fields) {
        element.textContent = message;
        element.hidden = false;
        fields.forEach(function (field) { field.setAttribute('aria-invalid', 'true'); });
    }

    function clearError(element, fields) {
        element.hidden = true;
        element.textContent = '';
        fields.forEach(function (field) { field.removeAttribute('aria-invalid'); });
    }

    function normalPath(value) {
        try { value = decodeURIComponent(value); } catch (error) { /* Keep malformed escapes literal. */ }
        return value.replace(/\/{2,}/g, '/').replace(/^\/+|\/+$/g, '').toLowerCase();
    }

    function isRecursive(url) {
        if (url.host !== window.location.host && url.host !== toolUrl.host) { return false; }
        var path = normalPath(url.pathname);
        if (path === normalPath(toolUrl.pathname) || /(^|\/)(?:responsive-kontrol|mobil-uyumluluk-kontrolu)$/.test(path)) { return true; }
        return url.searchParams.getAll('qa').some(function (request) {
            return /^(?:responsive-kontrol|mobil-uyumluluk-kontrolu)$/.test(normalPath(request));
        });
    }

    function normalizeUrl(raw) {
        var value = raw.trim();
        if (!value) { throw new Error('Önizlemek istediğiniz sitenin adresini girin.'); }
        if (value.length > 4096) { throw new Error('Site adresi en fazla 4096 karakter olabilir.'); }
        if (/[\u0000-\u001f\u007f\\]/.test(value)) {
            throw new Error('Geçerli bir site adresi girin; kontrol karakterleri ve ters eğik çizgi kullanmayın.');
        }
        if (/^\/\//.test(value)) {
            value = 'https:' + value;
        } else if (!/^https?:\/\//i.test(value)) {
            // A hostname with a port is an address, while javascript:, data:, ftp:, etc. are schemes.
            if (/^(?:javascript|data|vbscript|file|blob|ftp|mailto|about|tel|ws|wss):/i.test(value) ||
                (/^[a-z][a-z\d+.-]*:/i.test(value) && !/^[^:/?#\s]+:\d+(?:[/?#]|$)/.test(value))) {
                throw new Error('Yalnızca http:// veya https:// ile başlayan web adresleri desteklenir.');
            }
            value = 'https://' + value;
        }

        var url;
        try { url = new URL(value); } catch (error) {
            throw new Error('Geçerli bir site adresi girin. Örnek: https://ornek.com');
        }
        if ((url.protocol !== 'http:' && url.protocol !== 'https:') || !url.hostname) {
            throw new Error('Yalnızca HTTP veya HTTPS web adresleri desteklenir.');
        }
        if (url.username || url.password) {
            throw new Error('Kullanıcı adı veya parola içeren adresler desteklenmez.');
        }
        if (window.location.protocol === 'https:' && url.protocol === 'http:') {
            throw new Error('Bu sayfa HTTPS kullanıyor; tarayıcı HTTP önizlemelerini engeller. Sitenin HTTPS adresini girin.');
        }
        if (isRecursive(url)) {
            throw new Error('İç içe önizlemeyi önlemek için Responsive Kontrol sayfası burada açılamaz. Başka bir sayfa adresi girin.');
        }
        return url.href;
    }

    function fitViewport() {
        var style = window.getComputedStyle(stage);
        var available = stage.clientWidth - parseFloat(style.paddingLeft || 0) - parseFloat(style.paddingRight || 0);
        var scale = Math.min(1, Math.max(1, available) / width);
        dimensions.textContent = width + ' × ' + height + ' px';
        scaleLabel.textContent = 'Ekrana sığdırma: %' + Math.round(scale * 100);
        viewport.style.width = (width * scale) + 'px';
        viewport.style.height = (height * scale) + 'px';
        if (frame) {
            // Layout viewport stays at the requested CSS-pixel size; only its painted output is scaled.
            frame.width = String(width);
            frame.height = String(height);
            frame.style.width = width + 'px';
            frame.style.height = height + 'px';
            frame.style.transform = 'scale(' + scale + ')';
            frame.title = 'Site önizlemesi — ' + width + ' × ' + height + ' piksel';
        }
    }

    function scheduleFit() {
        if (resizePending) { return; }
        resizePending = true;
        window.requestAnimationFrame(function () {
            resizePending = false;
            fitViewport();
        });
    }

    function applySize(nextWidth, nextHeight, preset) {
        width = nextWidth;
        height = nextHeight;
        selectedPreset = preset;
        widthInput.value = String(width);
        heightInput.value = String(height);
        clearError(sizeError, [widthInput, heightInput]);
        presets.forEach(function (button) {
            button.setAttribute('aria-pressed', String(button.getAttribute('data-preset') === selectedPreset));
        });
        fitViewport();
        status.textContent = width + ' × ' + height + ' px görüntü alanı seçildi.' + (currentUrl ? ' Çerçevedeki görünümü inceleyin.' : ' Önizlemek için bir site adresi girin.');
    }

    function sendToFrame() {
        if (!frame) {
            frame = document.createElement('iframe');
            frame.setAttribute('sandbox', 'allow-scripts allow-forms allow-same-origin');
            frame.setAttribute('referrerpolicy', 'no-referrer');
            viewport.appendChild(frame);
        }
        empty.hidden = true;
        viewport.hidden = false;
        fitViewport();
        frame.src = currentUrl;
        refresh.disabled = false;
        openLink.href = currentUrl;
        openLink.hidden = false;
        status.textContent = 'Adres önizleme çerçevesine gönderildi. İçerik görünmüyorsa site çerçevede gösterilmeyi engelliyor olabilir.';
    }

    urlForm.addEventListener('submit', function (event) {
        event.preventDefault();
        clearError(urlError, [urlInput]);
        try {
            var nextUrl = normalizeUrl(urlInput.value);
            currentUrl = nextUrl;
            urlInput.value = nextUrl;
            sendToFrame();
        } catch (error) {
            showError(urlError, error.message, [urlInput]);
            urlInput.focus();
        }
    });

    urlInput.addEventListener('input', function () { clearError(urlError, [urlInput]); });

    presets.forEach(function (button) {
        button.addEventListener('click', function () {
            applySize(Number(button.getAttribute('data-width')), Number(button.getAttribute('data-height')), button.getAttribute('data-preset'));
        });
    });

    sizeForm.addEventListener('submit', function (event) {
        event.preventDefault();
        clearError(sizeError, [widthInput, heightInput]);
        var nextWidth = Number(widthInput.value);
        var nextHeight = Number(heightInput.value);
        var invalidFields = [widthInput, heightInput].filter(function (field) {
            var value = Number(field.value);
            return !/^\d+$/.test(field.value) || !Number.isInteger(value) || value < 240 || value > 3840;
        });
        if (invalidFields.length) {
            showError(sizeError, 'Genişlik ve yüksekliği 240–3840 arasında tam sayı olarak girin.', invalidFields);
            invalidFields[0].focus();
            return;
        }
        applySize(nextWidth, nextHeight, null);
    });

    [widthInput, heightInput].forEach(function (field) {
        field.addEventListener('input', function () { clearError(sizeError, [widthInput, heightInput]); });
    });

    rotate.addEventListener('click', function () { applySize(height, width, selectedPreset); });
    refresh.addEventListener('click', function () { if (currentUrl) { sendToFrame(); } });

    if (typeof window.ResizeObserver === 'function') {
        var observer = new window.ResizeObserver(scheduleFit);
        observer.observe(stage);
    }
    window.addEventListener('resize', scheduleFit);
    fitViewport();
}());
