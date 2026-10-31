(function () {
    'use strict';
    var menus = document.querySelectorAll('.w3-header-mobile-nav, .w3-header-account, .w3-header-tools');
    function closeMenu(menu) {
        menu.removeAttribute('open');
        menu.querySelectorAll('details[open]').forEach(function (child) { child.removeAttribute('open'); });
    }
    menus.forEach(function (menu) {
        menu.addEventListener('toggle', function () {
            if (!menu.open) closeMenu(menu);
        });
    });
    // Mouse hover opens desktop tools; native details clicks/keyboard remain available.
    var hoverPointer = window.matchMedia('(hover: hover) and (pointer: fine)');
    document.querySelectorAll('.w3-home-nav > .w3-header-tools').forEach(function (menu) {
        var closeTimer;
        function cancelClose() { window.clearTimeout(closeTimer); }
        function scheduleClose() {
            cancelClose();
            closeTimer = window.setTimeout(function () {
                if (!menu.contains(document.activeElement) && !menu.matches(':hover')) closeMenu(menu);
            }, 200);
        }
        menu.addEventListener('pointerenter', function (event) {
            if (event.pointerType !== 'mouse' || !hoverPointer.matches) return;
            cancelClose();
            menu.open = true;
        });
        menu.addEventListener('pointerleave', function (event) {
            if (event.pointerType === 'mouse' && hoverPointer.matches) scheduleClose();
        });
        menu.addEventListener('focusout', scheduleClose);
    });
    document.addEventListener('click', function (event) {
        menus.forEach(function (menu) { if (menu.open && !menu.contains(event.target)) closeMenu(menu); });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var menu = event.target.closest ? event.target.closest('.w3-header-tools[open], .w3-header-account[open], .w3-header-mobile-nav[open]') : null;
        if (!menu) {
            menus.forEach(function (candidate) {
                if (candidate.open && candidate.getClientRects().length) menu = candidate;
            });
        }
        if (!menu) return;
        event.preventDefault();
        closeMenu(menu);
        menu.querySelector('summary').focus();
    });
    // Browser-only bookmarks: no server requests and no claim of account synchronisation.
    var storageKey = 'w3uzman.savedArticles.v1';
    var saved = [];
    try { var parsed = JSON.parse(localStorage.getItem(storageKey) || '[]'); if (Array.isArray(parsed)) saved = parsed.filter(function (v) { return typeof v === 'string'; }).slice(0, 200); } catch (_) {}
    document.querySelectorAll('.w3-news-save').forEach(function (button) {
        var article = button.closest('article');
        var link = article && article.querySelector('.qa-q-item-title a');
        if (!link) { button.hidden = true; return; }
        var articlePath = new URL(link.href, location.href).pathname;
        function update() {
            var active = saved.indexOf(articlePath) !== -1;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.title = active ? 'Bu tarayıcıdaki kaydı kaldır' : 'Bu tarayıcıda kaydet';
            button.innerHTML = '<span aria-hidden="true">' + (active ? '♥' : '♡') + '</span> ' + (active ? 'Kaydedildi' : 'Kaydet');
        }
        update();
        button.addEventListener('click', function () {
            var index = saved.indexOf(articlePath);
            if (index === -1) saved.push(articlePath); else saved.splice(index, 1);
            saved = saved.slice(-200);
            update();
            try { localStorage.setItem(storageKey, JSON.stringify(saved)); } catch (_) {
                button.title = 'Tarayıcınız kalıcı kaydetmeye izin vermiyor; seçim yalnızca bu sayfada tutulur.';
            }
        });
    });
})();
