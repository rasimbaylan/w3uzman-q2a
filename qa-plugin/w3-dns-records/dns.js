(function () {
    'use strict';
    var root = document.querySelector('.w3-dns-results');
    if (!root) return;
    var groups = Array.prototype.slice.call(root.querySelectorAll('.w3-dns-group'));
    var results = {};
    var queue = [];
    var active = 0;
    var generation = 0;
    var stopped = false;
    var controllers = [];
    var progress = root.querySelector('#w3-dns-progress');
    var copyAll = root.querySelector('#w3-dns-copy-all');
    var labels = { ready: 'kayıt', empty: 'Kayıt yok', not_found: 'Ad bulunamadı', error: 'Sorgu hatası', checking: 'Sorgulanıyor', pending: 'Bekliyor' };
    var filters = root.querySelector('.w3-dns-filters');
    filters.hidden = groups.length < 2;

    function announce(message) { root.querySelector('#w3-dns-copy-status').textContent = message; }
    function copy(text, button) {
        function complete(ok) {
            announce(ok ? 'DNS kayıt değeri kopyalandı.' : 'Kopyalanamadı. Metni seçip kopyalayabilirsiniz.');
            var label = button.textContent;
            button.textContent = ok ? 'Kopyalandı' : 'Kopyalanamadı';
            window.setTimeout(function () { button.textContent = label; }, 1600);
        }
        function fallback() {
            var area = document.createElement('textarea'); area.value = text;
            area.setAttribute('readonly', ''); area.style.position = 'fixed'; area.style.opacity = '0';
            document.body.appendChild(area); area.select();
            var ok = false; try { ok = document.execCommand('copy'); } catch (_) {}
            area.remove(); button.focus(); complete(ok);
        }
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(function () { complete(true); }, fallback);
        else fallback();
    }
    function update() {
        var done = Object.keys(results).length;
        var errors = Object.keys(results).filter(function (key) { return results[key].state === 'error'; }).length;
        progress.textContent = done + ' / ' + groups.length + ' sorgu tamamlandı' + (errors ? ' · ' + errors + ' sorgu hatası' : '');
        copyAll.hidden = !Object.keys(results).some(function (key) { return results[key].records.length; });
    }
    function render(group, data) {
        var type = group.getAttribute('data-type');
        results[type] = data;
        group.setAttribute('data-state', data.state);
        group.querySelector('.w3-dns-group-count').textContent = data.state === 'ready' ? data.records.filter(function (record) { return record.type === type; }).length + ' kayıt' : labels[data.state];
        group.querySelector('.w3-dns-group-message').textContent = data.message;
        var tbody = group.querySelector('tbody'); tbody.textContent = '';
        data.records.forEach(function (record) {
            var row = document.createElement('tr');
            var name = document.createElement('td'); name.className = 'w3-dns-record-name'; name.textContent = record.name;
            var recordType = document.createElement('small'); recordType.textContent = record.type; name.appendChild(recordType);
            var value = document.createElement('td'); value.className = 'w3-dns-record-value';
            var code = document.createElement('code'); code.textContent = record.data; value.appendChild(code);
            var ttl = document.createElement('td'); ttl.className = 'w3-dns-record-ttl'; ttl.textContent = record.ttl;
            var seconds = document.createElement('small'); seconds.textContent = 'sn'; ttl.appendChild(seconds);
            var action = document.createElement('td'); action.className = 'w3-dns-record-action';
            var button = document.createElement('button'); button.type = 'button'; button.className = 'w3-dns-copy-value'; button.textContent = 'Kopyala'; button.setAttribute('aria-label', record.type + ' kayıt değerini kopyala');
            button.addEventListener('click', function () { copy(record.data, button); }); action.appendChild(button);
            [name,value,ttl,action].forEach(function (cell) { row.appendChild(cell); }); tbody.appendChild(row);
        });
        group.querySelector('.w3-dns-table-wrap').hidden = !data.records.length;
        var meta = 'Google Public DNS';
        if (data.checked) meta += ' · ' + new Date(data.checked * 1000).toISOString().slice(11,19) + ' UTC';
        if (data.cached) meta += ' · Önbellekten';
        if (data.dnssec === true) meta += ' · DNSSEC doğrulandı';
        group.querySelector('.w3-dns-group-meta').textContent = meta;
        group.querySelector('.w3-dns-retry').hidden = data.state !== 'error';
        group.open = data.records.length > 0 || data.state === 'error';
        var count = filters.querySelector('[data-count="' + type + '"]');
        if (count) count.textContent = data.state === 'error' ? '!' : data.records.filter(function (record) { return record.type === type; }).length;
        update();
    }
    function check(group, requestGeneration) {
        var type = group.getAttribute('data-type');
        group.setAttribute('data-state', 'checking'); group.querySelector('.w3-dns-group-count').textContent = 'Sorgulanıyor'; group.querySelector('.w3-dns-retry').hidden = true;
        var url = new URL(root.getAttribute('data-endpoint'), location.href);
        url.searchParams.set('domain', root.getAttribute('data-domain')); url.searchParams.set('type', type); url.searchParams.set('format', 'json');
        var controller = new AbortController(); controllers.push(controller);
        var timer = window.setTimeout(function () { controller.abort(); }, 12000);
        return fetch(url.href, { signal: controller.signal, cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (requestGeneration !== generation) return;
                if (!data || data.domain !== root.getAttribute('data-domain') || data.type !== type || ['ready','empty','not_found','error'].indexOf(data.state) === -1 || !Array.isArray(data.records)) throw new Error('invalid DNS response');
                render(group, data);
            })
            .catch(function () { if (!stopped && requestGeneration === generation) render(group, { domain:root.getAttribute('data-domain'), type:type, state:'error', records:[], message:'Bağlantı kurulamadı veya sorgu zaman aşımına uğradı. Tekrar deneyin.' }); })
            .then(function () { window.clearTimeout(timer); controllers = controllers.filter(function (item) { return item !== controller; }); });
    }
    function pump() {
        if (stopped) return;
        while (active < 3 && queue.length) {
            var group = queue.shift(); active++;
            (function (currentGeneration) { check(group, currentGeneration).then(function () { if (currentGeneration === generation) { active--; pump(); } }); }(generation));
        }
    }
    filters.querySelectorAll('button').forEach(function (button) {
        button.addEventListener('click', function () {
            var selection = button.getAttribute('data-filter');
            groups.forEach(function (group) { group.hidden = selection !== 'ALL' && group.getAttribute('data-type') !== selection; });
            filters.querySelectorAll('button').forEach(function (item) { item.setAttribute('aria-pressed', item === button ? 'true' : 'false'); });
        });
    });
    groups.forEach(function (group) {
        group.querySelector('.w3-dns-retry').addEventListener('click', function () {
            if (group.getAttribute('data-state') !== 'error') return;
            delete results[group.getAttribute('data-type')]; group.setAttribute('data-state','pending');
            group.querySelector('.w3-dns-retry').hidden = true; queue.push(group); update(); pump();
        });
        var initial = group.nextElementSibling;
        if (initial && initial.classList.contains('w3-dns-initial')) {
            try { render(group, JSON.parse(initial.textContent)); } catch (_) { queue.push(group); }
        } else queue.push(group);
    });
    copyAll.addEventListener('click', function () {
        var lines = []; var seen = {};
        groups.forEach(function (group) {
            var data = results[group.getAttribute('data-type')];
            if (!data) return;
            data.records.forEach(function (record) {
                var line = record.name + '\t' + record.ttl + '\tIN\t' + record.type + '\t' + record.data;
                if (!seen[line]) { seen[line] = true; lines.push(line); }
            });
        });
        if (lines.length) copy(lines.join('\n'), copyAll);
    });
    window.addEventListener('pagehide', function () { stopped = true; generation++; controllers.forEach(function (controller) { controller.abort(); }); });
    window.addEventListener('pageshow', function (event) { if (!event.persisted) return; stopped = false; active = 0; queue = groups.filter(function (group) { return ['pending','checking'].indexOf(group.getAttribute('data-state')) !== -1; }); pump(); });
    update(); pump();
}());
