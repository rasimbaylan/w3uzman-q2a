(function () {
    'use strict';
    function legacyCopy(url) {
        var input = document.createElement('textarea');
        input.value = url; input.setAttribute('readonly', '');
        input.style.cssText = 'position:fixed;left:-9999px;top:0;opacity:0';
        document.body.appendChild(input); input.select();
        var copied = false;
        try { copied = document.execCommand('copy'); } catch (error) { copied = false; }
        input.remove(); return copied;
    }
    document.addEventListener('click', async function (event) {
        var button = event.target.closest('.w3-share-copy');
        if (!button) { return; }
        var panel = button.closest('.w3-share');
        var label = button.querySelector('span');
        var status = panel.querySelector('.w3-share-status');
        var url = button.getAttribute('data-share-url');
        var copied = false; button.disabled = true;
        try {
            if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
                try { await navigator.clipboard.writeText(url); copied = true; } catch (error) { copied = legacyCopy(url); }
            } else { copied = legacyCopy(url); }
            if (copied) {
                var manual = panel.querySelector('.w3-share-manual');
                if (manual) { manual.remove(); }
                label.textContent = 'Kopyalandı'; status.textContent = 'Bağlantı kopyalandı.';
                window.setTimeout(function () { label.textContent = 'Bağlantıyı kopyala'; }, 2000);
                button.focus({preventScroll:true});
            } else {
                var field = panel.querySelector('.w3-share-manual');
                if (!field) {
                    field = document.createElement('input'); field.type = 'text'; field.readOnly = true; field.className = 'w3-share-manual';
                    field.setAttribute('aria-label', 'Kopyalanacak bağlantı'); panel.appendChild(field);
                }
                field.value = url; field.focus(); field.select();
                status.textContent = 'Otomatik kopyalanamadı. Seçili bağlantıyı elle kopyalayabilirsiniz.';
            }
        } finally { button.disabled = false; }
    });
})();
