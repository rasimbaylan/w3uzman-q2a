(function () {
    'use strict';
    const root = document.querySelector('.w3-json'); if (!root) return;
    const el = id => root.querySelector('#json-' + id), input = el('input'), output = el('output');
    let timer, parsed = null, errorPosition = null, mode = 'pretty', importRevision = 0;
    const types = { object:'Nesne', array:'Dizi', string:'Metin', number:'Sayı', boolean:'Boolean', null:'Null' };
    function message(text, state) { el('message').textContent = text; el('status').className = 'json-status' + (state ? ' is-' + state : ''); }
    function resetOutput() {
        output.value = ''; parsed = null; errorPosition = null;
        el('copy').disabled = el('download').disabled = true;
        el('output-size').textContent = '0 B'; el('goto-error').hidden = true;
        el('warnings').hidden = true; el('warnings').textContent = '';
        el('type').textContent = '—'; ['properties','items','depth','nodes'].forEach(id => { el(id).textContent = '0'; });
    }
    function run(action) {
        clearTimeout(timer); resetOutput();
        el('clear').disabled = !input.value;
        el('input-size').textContent = W3LocalTools.size(W3LocalTools.bytes(input.value)) + ' · En fazla 1 MB';
        input.setAttribute('aria-invalid', 'false');
        if (!input.value.trim()) { message('JSON verisini yapıştırın; doğrulama ve önizleme otomatik çalışır.'); return; }
        try {
            const result = W3Json.parse(input.value);
            if (action === 'pretty' || action === 'minify') mode = action;
            const indent = mode === 'minify' ? '' : (el('indent').value === 'tab' ? '\t' : ' '.repeat(Number(el('indent').value)));
            output.value = W3Json.format(result.tree, indent); parsed = result;
            el('output-size').textContent = W3LocalTools.size(W3LocalTools.bytes(output.value));
            el('copy').disabled = el('download').disabled = false;
            el('type').textContent = types[result.stats.type];
            for (const [id, value] of Object.entries({ properties:result.stats.properties, items:result.stats.arrayItems, depth:result.stats.maxDepth, nodes:result.stats.nodes })) el(id).textContent = value.toLocaleString('tr-TR');
            if (result.warnings.length) { el('warnings').textContent = result.warnings.join(' '); el('warnings').hidden = false; }
            message(action === 'validate' ? 'Geçerli JSON. Sözdizimi doğrulandı.' : 'Geçerli JSON · ' + (mode === 'minify' ? 'Sıkıştırılmış çıktı hazır.' : 'Düzenlenmiş çıktı hazır.'), 'valid');
        } catch (error) {
            input.setAttribute('aria-invalid', 'true');
            if (Number.isInteger(error.position)) {
                errorPosition = Math.min(input.value.length, Math.max(0, error.position)); el('goto-error').hidden = false;
                message('Satır ' + error.line + ', sütun ' + error.column + ': ' + error.message, 'error');
            } else message(error.message || 'JSON işlenemedi.', 'error');
        }
    }
    input.addEventListener('input', () => {
        importRevision++; clearTimeout(timer); resetOutput(); el('clear').disabled = !input.value;
        message('JSON kontrol ediliyor…'); timer = setTimeout(() => run(), 250);
    });
    input.addEventListener('keydown', event => { if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') { event.preventDefault(); run('pretty'); } });
    el('format').addEventListener('click', () => run('pretty'));
    el('minify').addEventListener('click', () => run('minify'));
    el('validate').addEventListener('click', () => run('validate'));
    el('indent').addEventListener('change', () => run('pretty'));
    el('goto-error').addEventListener('click', () => {
        if (errorPosition === null) return;
        input.focus({ preventScroll:true }); input.setSelectionRange(errorPosition, Math.min(errorPosition + 1, input.value.length));
        input.scrollTop = Math.max(0, (input.value.slice(0, errorPosition).split('\n').length - 3) * parseFloat(getComputedStyle(input).lineHeight));
    });
    el('clear').addEventListener('click', () => { importRevision++; input.value = ''; run(); input.focus(); });
    el('example').addEventListener('click', () => {
        importRevision++;
        input.value = '{"site":"W3Uzman","konular":["Yapay zekâ","Yazılım","Web"],"aktif":true,"buyukKimlik":9007199254740993,"oran":1.2300,"not":null}';
        run('pretty');
    });
    el('file').addEventListener('change', async () => {
        const revision = ++importRevision, file = el('file').files[0];
        try {
            const value = await W3LocalTools.readFile(file, W3Json.MAX_BYTES);
            if (revision !== importRevision || value === null) return;
            input.value = value; run('pretty');
        } catch (error) { if (revision === importRevision) message('Dosya açılamadı: ' + error.message + ' Mevcut girdi korundu.', 'error'); }
        finally { el('file').value = ''; }
    });
    el('copy').addEventListener('click', async () => {
        if (!parsed) return;
        try { await W3LocalTools.copy(output.value); message('JSON panoya kopyalandı.', 'valid'); }
        catch (error) { message(error.message, 'error'); }
    });
    el('download').addEventListener('click', () => {
        if (parsed) { W3LocalTools.download(output.value, 'w3uzman-' + (mode === 'minify' ? 'sikistirilmis' : 'duzenlenmis') + '.json', 'application/json;charset=utf-8'); message('JSON indirme isteği hazırlandı.', 'valid'); }
    });
    run();
}());
