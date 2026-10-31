(function () {
    'use strict';
    var root = document.querySelector('.w3-domain-results');
    if (!root) return;
    var rows = Array.prototype.slice.call(root.querySelectorAll('.w3-domain-row'));
    var filters = root.querySelector('.w3-domain-filters');
    var progress = root.querySelector('#w3-domain-progress');
    var selected = 'all';
    var queue = rows.slice();
    var active = 0;
    var stopping = false;
    var generation = 0;
    var controllers = [];
    var states = { available: ['Boş', '✓'], registered: ['Dolu', '×'], unknown: ['Doğrulanamadı', '?'], pending: ['Bekliyor', '…'], checking: ['Sorgulanıyor', '…'] };
    filters.hidden = false;

    function update() {
        var counts = { all: rows.length, available: 0, registered: 0, unknown: 0 };
        var done = 0;
        var visible = 0;
        rows.forEach(function (row) {
            var state = row.getAttribute('data-state');
            if (Object.prototype.hasOwnProperty.call(counts, state)) { counts[state]++; done++; }
            row.hidden = selected !== 'all' && state !== selected;
            if (!row.hidden) visible++;
        });
        Object.keys(counts).forEach(function (key) { root.querySelector('[data-count="' + key + '"]').textContent = counts[key]; });
        progress.textContent = done + ' / ' + rows.length + ' tamamlandı' + (done === rows.length && counts.unknown ? ' · ' + counts.unknown + ' sonuç doğrulanamadı' : '');
        root.querySelector('.w3-domain-filter-empty').hidden = visible !== 0;
    }

    function render(row, state, message, source) {
        row.setAttribute('data-state', state);
        row.querySelector('.w3-domain-status').textContent = states[state][0];
        row.querySelector('.w3-domain-indicator').textContent = states[state][1];
        row.querySelector('.w3-domain-detail').textContent = state === 'available' ? 'Kayıt bulunamadı · Ön kontrol' : (state === 'registered' ? 'Kayıtlı alan adı' : (message || 'Kayıt servisine ulaşılamadı'));
        row.querySelector('.w3-domain-detail').title = (source ? source + ' · ' : '') + (message || '');
        row.querySelector('.w3-domain-whois').hidden = state !== 'registered';
        row.querySelector('.w3-domain-retry').hidden = state !== 'unknown';
        update();
    }

    function check(row, requestGeneration) {
        render(row, 'checking', 'Kayıt servisi sorgulanıyor');
        var url = new URL(root.getAttribute('data-endpoint'), window.location.href);
        url.searchParams.set('format', 'status');
        url.searchParams.set('name', root.getAttribute('data-name'));
        url.searchParams.set('extension', row.getAttribute('data-extension'));
        var controller = new AbortController();
        controllers.push(controller);
        var timer = window.setTimeout(function () { controller.abort(); }, 16000);
        return fetch(url.href, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (requestGeneration !== generation) return;
                var domain = root.getAttribute('data-name') + '.' + row.getAttribute('data-extension');
                if (data.domain !== domain || ['available', 'registered', 'unknown'].indexOf(data.state) === -1) throw new Error('invalid');
                render(row, data.state, data.message, data.source);
            })
            .catch(function () { if (!stopping && requestGeneration === generation) render(row, 'unknown', 'Bağlantı kurulamadı veya sorgu zaman aşımına uğradı.'); })
            .then(function () { window.clearTimeout(timer); controllers = controllers.filter(function (item) { return item !== controller; }); });
    }

    function pump() {
        if (stopping) return;
        while (active < 2 && queue.length) {
            var row = queue.shift();
            active++;
            (function (currentGeneration) {
                check(row, currentGeneration).then(function () { if (currentGeneration === generation) { active--; pump(); } });
            }(generation));
        }
    }
    filters.querySelectorAll('button').forEach(function (button) {
        button.addEventListener('click', function () {
            selected = button.getAttribute('data-filter');
            filters.querySelectorAll('button').forEach(function (item) { item.setAttribute('aria-pressed', item === button ? 'true' : 'false'); });
            update();
        });
    });
    rows.forEach(function (row) {
        row.querySelector('.w3-domain-retry').addEventListener('click', function () {
            if (row.getAttribute('data-state') !== 'unknown') return;
            render(row, 'pending', 'Tekrar kontrol edilecek');
            queue.push(row); pump();
        });
    });
    window.addEventListener('pagehide', function () { stopping = true; generation++; controllers.forEach(function (controller) { controller.abort(); }); });
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        stopping = false; active = 0;
        queue = rows.filter(function (row) { return ['pending', 'checking'].indexOf(row.getAttribute('data-state')) !== -1; });
        pump();
    });
    update(); pump();
}());
