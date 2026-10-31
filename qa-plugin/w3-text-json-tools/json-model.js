/* Strict JSON syntax tree. Numeric lexemes and duplicate properties are preserved. No eval. */
(function (root, factory) {
    'use strict';
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.W3Json = factory();
}(typeof window !== 'undefined' ? window : this, function () {
    'use strict';
    const MAX_BYTES = 1048576, MAX_DEPTH = 128, MAX_OUTPUT = 8388608;
    function parse(source) {
        if (new TextEncoder().encode(source).length > MAX_BYTES) throw new Error('JSON dosyası en fazla 1 MB olabilir.');
        let i = 0, nodes = 0, properties = 0, arrayItems = 0, maxDepth = 0, duplicates = 0;
        const warnings = [], hasBom = source.charCodeAt(0) === 0xfeff;
        if (hasBom) { i++; warnings.push('Başlangıçtaki UTF-8 BOM işareti çıktıya alınmadı.'); }
        function fail(message, at) {
            const position = at === undefined ? i : at;
            const before = source.slice(0, position).split(/\r\n?|\n/), line = before.length;
            const column = before[before.length - 1].length + 1;
            const error = new SyntaxError(message);
            error.position = position; error.line = line; error.column = column;
            throw error;
        }
        function white() { while (i < source.length && /[\x20\t\n\r]/.test(source[i])) i++; }
        function string() {
            const start = i++;
            while (i < source.length) {
                const char = source[i++];
                if (char === '"') {
                    const raw = source.slice(start, i);
                    return { raw, value: JSON.parse(raw) };
                }
                if (char.charCodeAt(0) < 32) fail('Metin içinde kaçışsız kontrol karakteri var.', i - 1);
                if (char === '\\') {
                    if (i >= source.length) fail('Kaçış karakterinden sonra bir karakter bekleniyor.');
                    const escape = source[i++];
                    if (escape === 'u') {
                        if (!/^[\da-f]{4}$/i.test(source.slice(i, i + 4))) fail('Unicode kaçışı dört onaltılık hane içermeli.');
                        i += 4;
                    } else if (!'"\\/bfnrt'.includes(escape)) fail('Geçersiz kaçış karakteri.', i - 1);
                }
            }
            fail('Metnin kapanış çift tırnağı eksik.');
        }
        function value(depth) {
            white();
            if (depth > MAX_DEPTH) fail('En fazla 128 iç içe nesne/dizi destekleniyor.');
            maxDepth = Math.max(maxDepth, depth); nodes++;
            const char = source[i];
            if (char === '{') {
                i++; white(); const entries = [], keys = new Set();
                if (source[i] !== '}') while (true) {
                    white(); const start = i;
                    if (source[i] !== '"') fail('Nesne anahtarı çift tırnak içinde olmalı.');
                    const key = string(); white();
                    if (source[i++] !== ':') fail('Anahtardan sonra iki nokta (:) bekleniyor.', i - 1);
                    if (keys.has(key.value)) {
                        duplicates++;
                        if (duplicates <= 3) warnings.push('Tekrarlanan anahtar: ' + JSON.stringify(key.value).slice(0, 100) + '.');
                    }
                    keys.add(key.value); properties++;
                    entries.push({ key: key.raw, node: value(depth + 1) }); white();
                    if (source[i] === '}') break;
                    if (source[i++] !== ',') fail('Nesnede virgül veya kapanış süslü parantezi bekleniyor.', i - 1);
                }
                if (source[i++] !== '}') fail('Nesnenin kapanış süslü parantezi eksik.');
                return { type: 'object', entries };
            }
            if (char === '[') {
                i++; white(); const items = [];
                if (source[i] !== ']') while (true) {
                    items.push(value(depth + 1)); arrayItems++; white();
                    if (source[i] === ']') break;
                    if (source[i++] !== ',') fail('Dizide virgül veya kapanış köşeli parantezi bekleniyor.', i - 1);
                }
                if (source[i++] !== ']') fail('Dizinin kapanış köşeli parantezi eksik.');
                return { type: 'array', items };
            }
            if (char === '"') return { type: 'string', raw: string().raw };
            for (const literal of ['true', 'false', 'null']) if (source.startsWith(literal, i)) {
                i += literal.length;
                return { type: literal === 'null' ? 'null' : 'boolean', raw: literal };
            }
            const match = /^-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?/.exec(source.slice(i));
            if (match) { i += match[0].length; return { type: 'number', raw: match[0] }; }
            fail(i >= source.length ? 'Bir JSON değeri bekleniyor; içerik erken bitti.' : 'Geçersiz JSON değeri. Metinleri çift tırnak içine alın.');
        }
        white();
        if (i >= source.length) fail('JSON verisini yapıştırın veya bir dosya seçin.');
        const tree = value(0); white();
        if (i !== source.length) fail('JSON değerinden sonra beklenmeyen karakter var.');
        if (duplicates) warnings.push('Toplam ' + duplicates + ' yinelenen anahtar korundu. Bazı uygulamalar yalnızca son değeri kullanır.');
        return { tree, warnings, stats: { nodes, properties, arrayItems, maxDepth, type: tree.type }, bytes: new TextEncoder().encode(source).length };
    }
    function format(tree, indent) {
        if (!['', '  ', '    ', '\t'].includes(indent)) throw new Error('Geçersiz girinti seçimi.');
        const chunks = []; let length = 0;
        function push(text) {
            length += text.length;
            if (length > MAX_OUTPUT) throw new Error('Biçimlendirilmiş çıktı çok büyük. Sıkıştırmayı veya daha küçük bir dosyayı deneyin.');
            chunks.push(text);
        }
        function walk(node, depth) {
            if (node.raw !== undefined) { push(node.raw); return; }
            const object = node.type === 'object', entries = object ? node.entries : node.items;
            push(object ? '{' : '[');
            entries.forEach((entry, index) => {
                if (index) push(',');
                if (indent) push('\n' + indent.repeat(depth + 1));
                if (object) { push(entry.key); push(indent ? ': ' : ':'); }
                walk(object ? entry.node : entry, depth + 1);
            });
            if (entries.length && indent) push('\n' + indent.repeat(depth));
            push(object ? '}' : ']');
        }
        walk(tree, 0);
        return chunks.join('');
    }
    return Object.freeze({ MAX_BYTES, parse, format });
}));
