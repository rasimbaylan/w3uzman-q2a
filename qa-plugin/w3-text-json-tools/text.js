(function () {
    'use strict';
    const root = document.querySelector('.w3-text'); if (!root) return;
    const el = id => root.querySelector('#text-' + id), input = el('input'), status = el('status');
    let history = [], timer, importRevision = 0;
    function say(message, error) { status.textContent = message; status.classList.toggle('is-error', Boolean(error)); }
    function refresh() {
        clearTimeout(timer);
        try {
            const info = W3Text.analyze(input.value);
            for (const [key, value] of Object.entries({ words:info.words, chars:info.characters, nonspace:info.nonSpace, lines:info.lines, paragraphs:info.paragraphs })) el(key).textContent = value.toLocaleString('tr-TR');
            el('reading').textContent = info.seconds < 60 ? info.seconds + ' sn' : Math.floor(info.seconds / 60) + ' dk' + (info.seconds % 60 ? ' ' + info.seconds % 60 + ' sn' : '');
            ['copy','download','clear'].forEach(id => { el(id).disabled = !input.value; });
            root.querySelectorAll('[data-text-action]').forEach(button => { button.disabled = !input.value; });
            el('undo').disabled = !history.length;
            input.setAttribute('aria-invalid', 'false');
            return true;
        } catch (error) {
            say(error.message, true); input.setAttribute('aria-invalid', 'true');
            el('copy').disabled = el('download').disabled = true;
            return false;
        }
    }
    function replace(value, description) {
        if (value === input.value) { say('Metin zaten bu biçimde.'); return; }
        history.push(input.value); if (history.length > 10) history.shift();
        input.value = value;
        if (refresh()) say(description);
    }
    root.querySelectorAll('[data-text-action]').forEach(button => button.addEventListener('click', () => {
        importRevision++;
        try { replace(W3Text.transform(input.value, button.dataset.textAction), button.textContent.trim() + ' işlemi uygulandı.'); }
        catch (error) { say(error.message, true); }
    }));
    input.addEventListener('input', () => {
        importRevision++; history = []; el('undo').disabled = true;
        clearTimeout(timer); timer = setTimeout(() => { if (refresh()) say('Sayaçlar güncellendi.'); }, 160);
    });
    el('undo').addEventListener('click', () => { importRevision++; if (history.length) { input.value = history.pop(); refresh(); say('Son işlem geri alındı.'); } });
    el('clear').addEventListener('click', () => { importRevision++; replace('', 'Metin temizlendi. Geri alabilirsiniz.'); input.focus(); });
    el('example').addEventListener('click', () => {
        importRevision++; replace('İstanbul’dan Isparta’ya: bilgi paylaştıkça çoğalır.\n\n  Türkçe metinlerle   çalışmak artık daha kolay!  \nMerhaba dünya 👋\nMerhaba dünya 👋', 'Örnek metin yüklendi. Dönüşümleri deneyebilirsiniz.');
    });
    el('file').addEventListener('change', async () => {
        const revision = ++importRevision, file = el('file').files[0];
        try {
            const value = await W3LocalTools.readFile(file, 524288);
            if (revision !== importRevision || value === null) return;
            if (value.length > W3Text.LIMIT) throw new Error('Metin çok uzun. En fazla 200 bin karakterlik daha kısa bir dosya seçin.');
            replace(value, 'TXT dosyası cihazınızda açıldı.');
        } catch (error) { if (revision === importRevision) say(error.message, true); }
        finally { el('file').value = ''; }
    });
    el('copy').addEventListener('click', async () => {
        try { await W3LocalTools.copy(input.value); say('Metin panoya kopyalandı.'); } catch (error) { say(error.message, true); }
    });
    el('download').addEventListener('click', () => {
        if (refresh() && input.value) { W3LocalTools.download(input.value, 'w3uzman-metin.txt', 'text/plain;charset=utf-8'); say('TXT indirme isteği hazırlandı.'); }
    });
    refresh();
}());
