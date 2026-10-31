(function () {
    'use strict';
    window.W3LocalTools = Object.freeze({
        async copy(text) {
            if (navigator.clipboard && window.isSecureContext) {
                try { await navigator.clipboard.writeText(text); return; } catch (_) { /* Local HTTP fallback. */ }
            }
            const previous = document.activeElement, field = document.createElement('textarea');
            field.value = text; field.readOnly = true;
            field.style.cssText = 'position:fixed;left:-10000px;top:0;opacity:0';
            document.body.appendChild(field); field.select();
            let ok = false;
            try { ok = document.execCommand('copy'); } finally {
                field.remove(); if (previous && previous.focus) previous.focus({ preventScroll: true });
            }
            if (!ok) throw new Error('Otomatik kopyalama desteklenmiyor. Metni seçip Ctrl+C kullanabilirsiniz.');
        },
        download(text, filename, type) {
            const url = URL.createObjectURL(new Blob([text], { type }));
            const link = document.createElement('a');
            link.href = url; link.download = filename; document.body.appendChild(link); link.click(); link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 30000);
        },
        async readFile(file, maxBytes) {
            if (!file) return null;
            if (file.size > maxBytes) throw new Error('Dosya boyutu sınırı aşıldı (' + Math.round(maxBytes / 1024) + ' KB).');
            let value;
            try { value = new TextDecoder('utf-8', { fatal: true }).decode(await file.arrayBuffer()); }
            catch (_) { throw new Error('Dosya UTF-8 metin olmalı. UTF-8 olarak kaydedip tekrar seçin.'); }
            return value;
        },
        bytes(value) { return new TextEncoder().encode(value).length; },
        size(value) { return value < 1024 ? value + ' B' : (value / 1024).toLocaleString('tr-TR', { maximumFractionDigits: 1 }) + ' KB'; }
    });
}());
