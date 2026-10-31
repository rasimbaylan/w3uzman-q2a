(function () {
    'use strict';
    var root = document.querySelector('.w3-generator');
    if (!root || !window.W3DomainGenerator) return;
    var model = window.W3DomainGenerator, form = root.querySelector('#generator-form');
    var keyword = root.querySelector('#generator-keyword'), sector = root.querySelector('#generator-sector'), extension = root.querySelector('#generator-extension');
    var error = root.querySelector('#generator-error'), status = root.querySelector('#generator-status');
    var result = root.querySelector('#generator-results'), grid = root.querySelector('#generator-grid');
    var copyPanel = root.querySelector('#generator-copy-panel'), copyText = root.querySelector('#generator-copy-text');
    var report = null, batch = 0, previous = '', copyOrigin = null;
    function announce(text) { status.textContent = text; }
    function element(tag, className, text) { var node = document.createElement(tag); if (className) node.className = className; if (text) node.textContent = text; return node; }
    function domain(item) { return item.name + '.' + extension.value; }
    function hideCopy() { copyPanel.hidden = true; if (copyOrigin && copyOrigin.isConnected) copyOrigin.focus(); }
    async function copy(text, button) {
        var copied = false; copyOrigin = button;
        try { if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) { await navigator.clipboard.writeText(text); copied = true; } } catch (_) {}
        if (!copied) {
            copyText.value = text; copyPanel.hidden = false; copyText.focus(); copyText.select();
            try { copied = document.execCommand('copy'); } catch (_) {}
            if (copied) hideCopy();
        }
        if (copied) {
            announce(text.indexOf('\n') === -1 ? text + ' kopyalandı.' : 'Öneri listesi kopyalandı.');
            if (button.classList.contains('generator-copy')) {
                button.setAttribute('aria-label', text + ' kopyalandı'); button.title = 'Kopyalandı'; button.classList.add('is-copied');
                window.setTimeout(function () { button.classList.remove('is-copied'); button.setAttribute('aria-label', text + ' kopyala'); button.title = 'Alan adını kopyala'; }, 1800);
            }
        } else announce('Otomatik kopyalama kullanılamıyor. Seçili metni kopyalayın.');
    }
    function render() {
        if (!report || model.extensions.indexOf(extension.value) === -1) return;
        grid.replaceChildren(); copyPanel.hidden = true;
        var rows = Math.ceil(report.items.length / 2);
        grid.style.setProperty('--generator-rows', rows);
        report.items.forEach(function (item, index) {
            var name = domain(item), card = element('li', 'generator-card'), heading = element('div', 'generator-card-heading');
            var number = element('span', 'generator-index', String(index + 1).padStart(2, '0')); number.setAttribute('aria-hidden', 'true');
            if (index < rows) card.classList.add('generator-first-column');
            if (index === rows - 1 || index === report.items.length - 1) card.classList.add('generator-row-end');
            var title = element('strong', 'generator-domain', item.name), suffix = element('span', '', '.' + extension.value); title.append(suffix);
            var button = element('button', 'generator-copy'); button.type = 'button'; button.title = 'Alan adını kopyala'; button.setAttribute('aria-label', name + ' kopyala');
            var icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg'); icon.setAttribute('viewBox', '0 0 24 24'); icon.setAttribute('aria-hidden', 'true');
            var shape = document.createElementNS('http://www.w3.org/2000/svg', 'path'); shape.setAttribute('d', 'M9 9h11v12H9zM15 5V3H3v13h2'); icon.append(shape); button.append(icon);
            button.addEventListener('click', function () { copy(name, button); });
            var foot = element('div', 'generator-card-footer'), kind = element('span', 'generator-kind', item.kind);
            var link = element('a', 'generator-check', 'Müsaitlik '); var arrow = element('span', '', '↗'); arrow.setAttribute('aria-hidden', 'true'); link.append(arrow);
            var url = new URL(root.getAttribute('data-lookup'), window.location.href); url.searchParams.set('name', name); link.href = url.href;
            link.setAttribute('aria-label', name + ' müsaitliğini Domain Sorgulama aracında kontrol et');
            heading.append(title, kind); foot.append(button, link); card.append(number, heading, foot); grid.append(card);
        });
        root.querySelector('#generator-result-summary').textContent = report.items.length + ' öneri · ' + report.words.join(' + ') + ' · .' + extension.value;
        result.hidden = false;
        announce(report.items.length + ' isim önerisi hazır. Müsaitlik kontrol edilmedi.');
    }
    function generate(next) {
        error.hidden = true; keyword.removeAttribute('aria-invalid');
        try {
            var signature = model.normalize(keyword.value).join('|') + '|' + sector.value;
            batch = next && signature === previous ? batch + 1 : 0;
            report = model.generate(keyword.value, sector.value, batch); previous = signature;
            render();
        } catch (exception) {
            error.textContent = exception.message; error.hidden = false; keyword.setAttribute('aria-invalid', 'true'); keyword.focus();
            result.hidden = true; report = null;
        }
    }
    form.addEventListener('submit', function (event) { event.preventDefault(); generate(false); });
    root.querySelector('#generator-more').addEventListener('click', function () { generate(true); });
    root.querySelector('#generator-copy-all').addEventListener('click', function (event) { if (report) copy(report.items.map(domain).join('\n'), event.currentTarget); });
    root.querySelector('#generator-copy-close').addEventListener('click', hideCopy);
    extension.addEventListener('change', render);
    root.querySelectorAll('[data-example]').forEach(function (button) { button.disabled = false; button.addEventListener('click', function () { keyword.value = button.getAttribute('data-example'); generate(false); }); });
    root.querySelector('#generator-submit').disabled = false;
}());
