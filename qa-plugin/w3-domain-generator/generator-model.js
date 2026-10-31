(function (root, factory) {
    'use strict';
    var model = factory();
    if (typeof module === 'object' && module.exports) module.exports = model;
    else root.W3DomainGenerator = model;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';
    var sectors = {
        general: { prefixes: ['merhaba', 'yeni', 'net', 'bir', 'hey', 'go', 'get', 'pro'], suffixes: ['hub', 'lab', 'ist', 'plus', 'box', 'nova', 'zone', 'club', 'base', 'dunya', 'rota', 'merkez'] },
        ecommerce: { prefixes: ['al', 'sepet', 'pazar', 'shop', 'buy', 'mini', 'hizli', 'go'], suffixes: ['sepet', 'pazar', 'dukkan', 'magaza', 'shop', 'store', 'market', 'cart', 'ticaret', 'butik', 'al', 'box'] },
        saas: { prefixes: ['get', 'use', 'try', 'go', 'hey', 'my', 'pro', 'neo'], suffixes: ['app', 'hub', 'flow', 'cloud', 'labs', 'sync', 'base', 'kit', 'desk', 'pilot', 'suite', 'loop'] },
        blog: { prefixes: ['oku', 'bir', 'yeni', 'acik', 'net', 'merak', 'mini', 'hey'], suffixes: ['blog', 'gunluk', 'not', 'defter', 'rehber', 'okur', 'bulten', 'pusula', 'medya', 'hikaye', 'soz', 'rota'] },
        software: { prefixes: ['dev', 'code', 'get', 'open', 'net', 'neo', 'pro', 'go'], suffixes: ['dev', 'code', 'soft', 'yazilim', 'tech', 'stack', 'kit', 'api', 'bit', 'works', 'lab', 'studio'] },
        agency: { prefixes: ['neo', 'yeni', 'pro', 'hey', 'net', 'bir', 'go', 'mini'], suffixes: ['ajans', 'studio', 'tasarim', 'medya', 'dijital', 'creative', 'works', 'design', 'fikir', 'marka', 'atolye', 'ekip'] }
    };
    var prefixes = ['get', 'go', 'hey', 'pro', 'my', 'neo', 'bir', 'yeni', 'mini', 'net', 'merhaba', 'use'];
    var suffixes = ['hub', 'app', 'ist', 'lab', 'plus', 'box', 'nova', 'base', 'club', 'zone', 'go', 'one'];
    var extensions = ['com', 'com.tr', 'net', 'org', 'io', 'ai'];
    function normalize(value) {
        if (typeof value !== 'string' || value.length > 60) throw new Error('En fazla 60 karakterlik bir veya iki anahtar kelime yazın.');
        var text = value.trim().replace(/[İı]/g, 'i').replace(/[Şş]/g, 's').replace(/[Ğğ]/g, 'g').replace(/[Çç]/g, 'c').replace(/[Üü]/g, 'u').replace(/[Öö]/g, 'o').replace(/ß/g, 'ss').replace(/[Ææ]/g, 'ae').replace(/[Œœ]/g, 'oe').normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        if (!text) throw new Error('Öneri üretmek için bir anahtar kelime yazın.');
        if (/[^a-z0-9\s-]/.test(text) || /[\u0000-\u001f\u007f]/.test(text)) throw new Error('Adres veya özel işaret yerine kelime yazın. Örnek: bulut veya bulut yazılım.');
        var words = text.split(/[\s-]+/).filter(Boolean);
        if (!words.length || words.length > 2) throw new Error('Bir veya iki anahtar kelime kullanın. Örnek: bulut yazılım.');
        if (words.some(function (word) { return !/[a-z]/.test(word) || word.length < 2 || word.length > 24; }) || words.join('').length > 32) throw new Error('Her kelime 2–24 karakter olmalı ve harf içermeli; toplam uzunluk en fazla 32 karakter olabilir.');
        return words;
    }
    function generate(value, sector, batch) {
        var words = normalize(value);
        if (!Object.prototype.hasOwnProperty.call(sectors, sector)) throw new Error('Geçerli bir sektör seçin.');
        batch = Number.isSafeInteger(batch) && batch >= 0 ? batch : 0;
        var pools = [[], [], []], seen = new Set();
        function add(name, kind, pool) {
            if (!/^[a-z0-9]{2,32}$/.test(name) || seen.has(name)) return;
            seen.add(name); pools[pool].push({ name: name, kind: kind });
        }
        words.forEach(function (word) {
            add(word, 'Anahtar kelime', 0);
            prefixes.forEach(function (prefix) { if (word.indexOf(prefix) !== 0) add(prefix + word, 'Ön ek', 0); });
            suffixes.forEach(function (suffix) { if (!word.endsWith(suffix)) add(word + suffix, 'Son ek', 0); });
        });
        words.forEach(function (word) {
            sectors[sector].prefixes.forEach(function (prefix) { if (word.indexOf(prefix) !== 0) add(prefix + word, 'Sektörel', 1); });
            sectors[sector].suffixes.forEach(function (suffix) { if (!word.endsWith(suffix)) add(word + suffix, 'Sektörel', 1); });
        });
        if (words.length === 2 && words[0] !== words[1]) {
            var joined = words[0] + words[1], reverse = words[1] + words[0];
            add(joined, 'İki kelime', 2); add(reverse, 'İki kelime', 2);
            ['go', 'hub', 'lab', 'plus', 'app'].forEach(function (suffix) { add(joined + suffix, 'İki kelime', 2); add(reverse + suffix, 'İki kelime', 2); });
            ['get', 'pro', 'neo', 'bir'].forEach(function (prefix) { add(prefix + joined, 'İki kelime', 2); add(prefix + reverse, 'İki kelime', 2); });
        }
        // Stable, reproducible batches; no remote service or random availability claim.
        var seed = 2166136261;
        (words.join('|') + '|' + sector).split('').forEach(function (char) { seed = Math.imul(seed ^ char.charCodeAt(0), 16777619) >>> 0; });
        function random() { seed ^= seed << 13; seed ^= seed >>> 17; seed ^= seed << 5; return (seed >>> 0) / 4294967296; }
        pools.forEach(function (pool) {
            for (var i = pool.length - 1; i > 0; i--) { var j = Math.floor(random() * (i + 1)); var item = pool[i]; pool[i] = pool[j]; pool[j] = item; }
            if (pool.length) { var offset = ((batch % pool.length) * 7) % pool.length; pools[pools.indexOf(pool)] = pool.slice(offset).concat(pool.slice(0, offset)); }
        });
        var result = [], cursor = 0;
        while (result.length < 24 && pools.some(function (pool) { return pool.length; })) {
            var current = pools[cursor++ % pools.length];
            if (current.length) result.push(current.shift());
        }
        return { words: words, sector: sector, items: result };
    }
    return { normalize: normalize, generate: generate, extensions: extensions.slice() };
}));
