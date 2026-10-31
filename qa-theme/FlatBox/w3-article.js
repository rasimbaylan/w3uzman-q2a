(function () {
    'use strict';
    if (!document.body.classList.contains('w3-article-page')) return;
    var status = document.querySelector('#w3-article-status');
    var save = document.querySelector('.w3-article-save'), messageTimer;
    function notify(message) {
        if (!status) return;
        window.clearTimeout(messageTimer);
        status.textContent = message;
        messageTimer = window.setTimeout(function () { status.textContent = ''; }, 6000);
    }
    var key = 'w3uzman.savedArticles.v1', saved = [];
    try { var existing = JSON.parse(localStorage.getItem(key) || '[]'); if (Array.isArray(existing)) saved = existing.filter(function (p) { return typeof p === 'string'; }).slice(-200); } catch (_) {}
    function updateSave() { if (save) { var active = saved.indexOf(save.getAttribute('data-article-path')) !== -1; save.setAttribute('aria-pressed', String(active)); save.title = active ? 'Bu tarayıcıdaki kaydı kaldır' : 'Bu tarayıcıya kaydet'; save.setAttribute('aria-label', save.title); } }
    if (save) {
        save.disabled = false; updateSave();
        save.addEventListener('click', function () {
            var path = save.getAttribute('data-article-path'), index = saved.indexOf(path);
            if (index === -1) saved.push(path); else saved.splice(index, 1);
            saved = saved.slice(-200); updateSave();
            try { localStorage.setItem(key, JSON.stringify(saved)); notify(index === -1 ? 'Haber bu tarayıcıya kaydedildi.' : 'Tarayıcıdaki kayıt kaldırıldı.'); }
            catch (_) { notify('Kalıcı kayıt kullanılamıyor; seçim yalnızca bu sayfada tutuluyor.'); }
        });
    }
    // Keep native authorised actions; only improve the disclosure's dismissal/focus.
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var menu = document.querySelector('.w3-article-more-actions[open]');
        if (!menu) return;
        event.preventDefault(); menu.open = false;
        menu.querySelector('summary').focus({preventScroll:true});
    });
    document.addEventListener('click', function (event) {
        var menu = document.querySelector('.w3-article-more-actions[open]');
        if (menu && !menu.contains(event.target)) menu.open = false;
    });
    document.querySelectorAll('[data-article-fallback]').forEach(function (img) {
        function fallback() { var replacement = img.getAttribute('data-article-fallback'); img.removeAttribute('data-article-fallback'); if (replacement) img.src = replacement; }
        img.addEventListener('error', fallback, {once:true});
        if (img.complete && !img.naturalWidth) fallback();
    });
    var hero = document.querySelector('[data-article-hero]');
    if (hero) {
        function missing() {
            var figure = hero.closest('.w3-article-hero');
            if (figure) { figure.hidden = true; figure.classList.add('is-unavailable'); }
        }
        hero.addEventListener('error', missing, {once:true});
        if (hero.complete && !hero.naturalWidth) missing();
    }
    // Progressive disclosure only for a blank new form. Editing, errors, drafts, linked forms
    // and no-JS visits keep the original editor visible. Native handlers still own the form.
    function compactCommentForm() {
        var form = document.querySelector('.qa-part-a-form .qa-a-form#anew');
        var trigger = document.querySelector('#q_doanswer');
        if (!form || !trigger || typeof trigger.onclick !== 'function' || location.search || location.hash) return;
        if (document.querySelector('.qa-error,.qa-form-tall-error,.qa-form-wide-error')) return;
        var fields = form.querySelectorAll('textarea,[contenteditable=true]');
        if (!fields.length) return;
        for (var i = 0; i < fields.length; i++) {
            if ((fields[i].value || fields[i].textContent || '').replace(/<[^>]*>|&nbsp;|\s/g, '')) return;
        }
        // Reuse the real native button instead of adding a third comment action.
        var toggle = trigger;
        toggle.classList.add('w3-article-comment-toggle');
        toggle.setAttribute('aria-controls', 'anew');
        function sync() {
            var open = getComputedStyle(form).display !== 'none';
            toggle.setAttribute('aria-expanded', String(open));
            toggle.value = open ? 'Yorum alanını kapat' : 'Yorum yaz';
        }
        form.style.display = 'none';
        if (window.qa_element_revealed === form) window.qa_element_revealed = null;
        sync();
        new MutationObserver(sync).observe(form, {attributes:true,attributeFilter:['style']});
    }
    if (document.readyState === 'complete') compactCommentForm();
    else window.addEventListener('load', compactCommentForm, {once:true});
}());
