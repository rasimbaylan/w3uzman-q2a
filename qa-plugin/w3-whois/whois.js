(function () {
    'use strict';
    var form = document.getElementById('w3-whois-form');
    if (form) {
        var submit = form.querySelector('button[type="submit"]');
        var submitMarkup = submit.innerHTML;
        var input = document.getElementById('w3-whois-domain');
        var clear = document.getElementById('w3-whois-clear');
        if (clear) {
            input.addEventListener('input', function () { clear.hidden = !input.value; });
            clear.addEventListener('click', function () { input.value = ''; clear.hidden = true; input.focus(); });
        }
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) return;
            var button = submit;
            button.textContent = 'Sorgulanıyor…';
            button.disabled = true;
            form.setAttribute('aria-busy', 'true');
        });
        window.addEventListener('pageshow', function () {
            var button = submit;
            button.innerHTML = submitMarkup;
            button.disabled = false;
            form.removeAttribute('aria-busy');
        });
    }
    // Limit concurrency to two: the main result is already usable while these load.
    var pending = Array.prototype.slice.call(document.querySelectorAll('.w3-whois-alternative[data-pending="1"]'));
    function updateStatus(row, state, label, message) {
        var badge = row.querySelector('.w3-whois-status');
        badge.className = 'w3-whois-status w3-whois-status--' + state;
        var dot = document.createElement('span');
        dot.textContent = '●'; dot.setAttribute('aria-hidden', 'true');
        badge.textContent = ''; badge.appendChild(dot);
        var screenReaderLabel = document.createElement('span');
        screenReaderLabel.className = 'qa-reader-only';
        screenReaderLabel.textContent = label;
        badge.appendChild(screenReaderLabel);
        var link = row.querySelector('a');
        var tooltip = state === 'available' ? 'Boş (ön kontrol)' : label;
        if (state === 'unknown' && message) tooltip += '. ' + message;
        link.title = tooltip;
        link.setAttribute('aria-label', row.getAttribute('data-domain') + ': ' + tooltip);
        row.removeAttribute('data-pending');
        updateRelated();
    }
    function updateRelated() {
        var panel = document.querySelector('.w3-whois-related');
        if (!panel) return;
        var rows = Array.prototype.slice.call(document.querySelectorAll('.w3-whois-alternative')).filter(function (row) { return row.getAttribute('data-domain') !== panel.getAttribute('data-current-domain'); });
        var registered = rows.filter(function (row) { return row.querySelector('.w3-whois-status--registered'); });
        var loading = rows.some(function (row) { return row.hasAttribute('data-pending'); });
        var unknown = rows.some(function (row) { return row.querySelector('.w3-whois-status--unknown'); });
        var list = document.getElementById('w3-whois-related-list');
        list.textContent = '';
        registered.forEach(function (row) {
            var li = document.createElement('li');
            var link = document.createElement('a');
            link.href = row.querySelector('a').href;
            link.textContent = row.getAttribute('data-domain');
            li.appendChild(link); list.appendChild(li);
        });
        document.getElementById('w3-whois-related-count').textContent = 'Toplam: ' + registered.length;
        var message = document.getElementById('w3-whois-related-message');
        message.textContent = !rows.length ? 'Bu ad için ek uzantı kontrolü yapılmadı.' : loading ? 'Diğer uzantılar kontrol ediliyor…' : unknown ? 'Bazı uzantıların durumu doğrulanamadı.' : !registered.length ? 'Taranan popüler uzantılarda kayıt bulunamadı.' : '';
        message.hidden = !message.textContent;
    }
    function checkNext() {
        if (!pending.length) return;
        var row = pending.shift();
        if (!window.fetch || !window.AbortController) {
            updateStatus(row, 'unknown', 'Kontrol için tıklayın');
            checkNext(); return;
        }
        var controller = new AbortController();
        var timer = window.setTimeout(function () { controller.abort(); }, 25000);
        fetch(row.getAttribute('data-check-url'), {
            credentials: 'same-origin', signal: controller.signal, headers: {Accept: 'application/json'}
        }).then(function (response) {
            if (!response.ok) throw new Error('Lookup failed');
            return response.json();
        }).then(function (data) {
            if (data.domain !== row.getAttribute('data-domain')) throw new Error('Domain mismatch');
            var labels = {available:'Boş', registered:'Dolu', unknown:'Durum doğrulanamadı'};
            if (!labels[data.state]) throw new Error('Invalid status');
            updateStatus(row, data.state, labels[data.state], data.message);
        }).catch(function () {
            updateStatus(row, 'unknown', 'Durum doğrulanamadı', 'Sorgu tamamlanamadı. Alan adına tıklayarak tekrar deneyebilirsiniz.');
        }).then(function () { window.clearTimeout(timer); checkNext(); });
    }
    updateRelated(); checkNext(); checkNext();
    var copy = document.getElementById('w3-whois-copy');
    if (copy) copy.addEventListener('click', function () {
        var raw = document.getElementById('w3-whois-raw');
        var status = document.getElementById('w3-whois-copy-status');
        if (navigator.clipboard && window.isSecureContext) {
            var lines = raw.querySelectorAll('.w3-whois-code-line');
            var text = lines.length ? Array.prototype.map.call(lines, function (line) { return line.textContent; }).join('\n') : raw.textContent;
            navigator.clipboard.writeText(text).then(function () { status.textContent = 'Kopyalandı.'; }, selectText);
        } else selectText();
        function selectText() {
            var selection = window.getSelection();
            var range = document.createRange();
            range.selectNodeContents(raw);
            selection.removeAllRanges(); selection.addRange(range);
            status.textContent = 'Metin seçildi; Ctrl+C ile kopyalayabilirsiniz.';
        }
    });
}());
