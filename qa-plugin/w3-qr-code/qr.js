(function () {
    'use strict';
    const root = document.querySelector('.w3-qr');
    if (!root) return;
    const el = id => root.querySelector('#' + id);
    const status = el('qr-status'), preview = el('qr-preview');
    const pngLink = el('qr-png'), svgLink = el('qr-svg');
    let type = 'url', timer, revision = 0, urls = [], currentQr = null;
    const labels = { url: 'Bağlantı', wifi: 'Wi-Fi', whatsapp: 'WhatsApp', vcard: 'Kişi kartı' };
    function clearExports() {
        revision++;
        urls.forEach(url => URL.revokeObjectURL(url));
        urls = [];
        [pngLink, svgLink].forEach(link => { link.removeAttribute('href'); link.setAttribute('aria-disabled', 'true'); });
        currentQr = null;
    }
    function message(text, error) {
        status.textContent = text;
        status.classList.toggle('is-error', Boolean(error));
        el('qr-ready').textContent = error ? 'Bilgi bekleniyor' : 'İndirilmeye hazır';
        el('qr-ready').classList.toggle('is-ready', !error);
    }
    function fields() {
        const value = id => el('qr-' + id).value;
        if (type === 'url') return { url: value('url') };
        if (type === 'wifi') return { ssid: value('ssid'), password: value('password'), security: value('security'), hidden: el('qr-hidden').checked };
        if (type === 'whatsapp') return { phone: value('wa-phone'), message: value('message') };
        return { first: value('first'), last: value('last'), company: value('company'), phone: value('card-phone'), email: value('email'), website: value('website') };
    }
    function svgFor(qr, color, size) {
        const ns = 'http://www.w3.org/2000/svg', border = 4, dimension = qr.size + border * 2;
        const svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('xmlns', ns);
        svg.setAttribute('viewBox', '0 0 ' + dimension + ' ' + dimension);
        svg.setAttribute('width', size);
        svg.setAttribute('height', size);
        svg.setAttribute('shape-rendering', 'crispEdges');
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', labels[type] + ' QR kodu');
        const background = document.createElementNS(ns, 'rect');
        background.setAttribute('width', '100%'); background.setAttribute('height', '100%'); background.setAttribute('fill', '#ffffff');
        svg.appendChild(background);
        const path = document.createElementNS(ns, 'path');
        let d = '';
        for (let y = 0; y < qr.size; y++) for (let x = 0; x < qr.size; x++) {
            if (qr.getModule(x, y)) d += 'M' + (x + border) + ',' + (y + border) + 'h1v1h-1z';
        }
        path.setAttribute('d', d); path.setAttribute('fill', color); svg.appendChild(path);
        return svg;
    }
    function attach(link, blob) {
        const url = URL.createObjectURL(blob);
        urls.push(url); link.href = url; link.setAttribute('aria-disabled', 'false');
    }
    function exportCode(qr, svg, size, color, token) {
        attach(svgLink, new Blob(['<?xml version="1.0" encoding="UTF-8"?>\n' + new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml;charset=utf-8' }));
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = size;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Tarayıcı PNG üretimini desteklemiyor. SVG olarak indirebilirsiniz.');
        const scale = Math.floor(size / (qr.size + 8));
        const offset = Math.floor((size - qr.size * scale) / 2);
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = color;
        for (let y = 0; y < qr.size; y++) for (let x = 0; x < qr.size; x++) if (qr.getModule(x, y)) ctx.fillRect(offset + x * scale, offset + y * scale, scale, scale);
        canvas.toBlob(blob => {
            if (token !== revision) return;
            if (!blob) { message('PNG üretilemedi. SVG olarak indirebilirsiniz.', true); return; }
            attach(pngLink, blob);
        }, 'image/png');
    }
    function update() {
        clearTimeout(timer);
        clearExports();
        preview.replaceChildren();
        try {
            const result = W3QrModel.build(type, fields());
            const segments = qrcodegen.QrSegment.makeSegments(result.payload);
            // UTF-8 ECI 26 lets readers decode Turkish names/SSIDs without guessing.
            if (/[^\x00-\x7f]/.test(result.payload)) segments.unshift(qrcodegen.QrSegment.makeEci(26));
            const qr = qrcodegen.QrCode.encodeSegments(segments, qrcodegen.QrCode.Ecc.MEDIUM);
            const size = Number(el('qr-size').value), color = el('qr-color').value;
            const svg = svgFor(qr, color, size);
            preview.appendChild(svg);
            currentQr = qr;
            el('qr-summary').textContent = labels[type] + ' · ' + result.summary;
            message('Kodunuz hazır. İndirmeden önce telefonunuzla tarayabilirsiniz.', false);
            exportCode(qr, svg, size, color, revision);
        } catch (error) {
            const hint = document.createElement('p');
            hint.textContent = 'Bilgileri tamamlayın, QR kodunuz burada görünsün.';
            preview.replaceChildren(hint);
            el('qr-summary').textContent = labels[type] + ' QR kodu';
            message(error instanceof Error ? error.message : 'QR kod oluşturulamadı. Bilgilerinizi kontrol edin.', true);
        }
    }
    if (typeof W3QrModel === 'undefined' || typeof qrcodegen === 'undefined' || typeof TextEncoder === 'undefined') {
        message('QR bileşeni yüklenemedi. Sayfayı yenileyin veya güncel bir tarayıcı deneyin.', true);
        return;
    }
    root.querySelectorAll('[data-qr-type]').forEach(button => button.addEventListener('click', () => {
        type = button.dataset.qrType;
        root.querySelectorAll('[data-qr-type]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        root.querySelectorAll('[data-qr-panel]').forEach(panel => { panel.hidden = panel.dataset.qrPanel !== type; });
        update();
    }));
    root.querySelectorAll('input, textarea').forEach(input => input.addEventListener('input', () => {
        clearExports();
        preview.replaceChildren();
        el('qr-ready').textContent = 'Güncelleniyor'; el('qr-ready').classList.remove('is-ready');
        status.textContent = 'QR kod güncelleniyor…'; status.classList.remove('is-error');
        clearTimeout(timer); timer = setTimeout(update, 200);
    }));
    root.querySelectorAll('select').forEach(select => select.addEventListener('change', () => {
        const noPassword = el('qr-security').value === 'nopass';
        el('qr-password').disabled = noPassword;
        el('qr-show-password').disabled = noPassword;
        update();
    }));
    el('qr-show-password').addEventListener('click', () => {
        const show = el('qr-password').type === 'password';
        el('qr-password').type = show ? 'text' : 'password';
        el('qr-show-password').textContent = show ? 'Gizle' : 'Göster';
        el('qr-show-password').setAttribute('aria-pressed', String(show));
        el('qr-show-password').setAttribute('aria-label', show ? 'Wi-Fi şifresini gizle' : 'Wi-Fi şifresini göster');
    });
    el('qr-reset').addEventListener('click', () => {
        const panel = root.querySelector('[data-qr-panel="' + type + '"]');
        panel.querySelectorAll('input,textarea').forEach(input => { if (input.type === 'checkbox') input.checked = false; else input.value = ''; });
        if (type === 'wifi') {
            el('qr-password').type = 'password'; el('qr-show-password').textContent = 'Göster';
            el('qr-show-password').setAttribute('aria-pressed', 'false'); el('qr-show-password').setAttribute('aria-label', 'Wi-Fi şifresini göster');
        }
        update(); panel.querySelector('input').focus();
    });
    [pngLink, svgLink].forEach(link => link.addEventListener('click', event => {
        if (!currentQr || link.getAttribute('aria-disabled') === 'true') event.preventDefault();
    }));
    window.addEventListener('pagehide', () => { clearTimeout(timer); clearExports(); });
    // A restored back/forward-cache page needs fresh object URLs.
    window.addEventListener('pageshow', event => { if (event.persisted) update(); });
    update();
}());
