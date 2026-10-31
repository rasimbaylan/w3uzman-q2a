(function (root, factory) {
    'use strict';
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.W3Text = factory();
}(typeof window !== 'undefined' ? window : this, function () {
    'use strict';
    const LIMIT = 200000;
    const wordSegmenter = typeof Intl.Segmenter === 'function' ? new Intl.Segmenter('tr', { granularity: 'word' }) : null;
    const characterSegmenter = typeof Intl.Segmenter === 'function' ? new Intl.Segmenter('tr', { granularity: 'grapheme' }) : null;
    const lines = text => text.replace(/\r\n?/g, '\n');
    function check(text) {
        if (text.length > LIMIT) throw new Error('En fazla 200.000 karakterlik metin kullanın.');
        return text;
    }
    function ascii(text) {
        return text.replace(/ı/g, 'i').replace(/İ/g, 'I').normalize('NFD').replace(/\p{M}/gu, '');
    }
    function analyze(input) {
        const text = lines(check(input));
        let words = 0, characters = 0, nonSpace = 0;
        if (wordSegmenter) {
            for (const part of wordSegmenter.segment(text)) if (part.isWordLike) words++;
        } else words = (text.match(/[\p{L}\p{N}]+(?:['’][\p{L}\p{N}]+)*/gu) || []).length;
        const parts = characterSegmenter ? characterSegmenter.segment(text) : text;
        for (const part of parts) {
            const value = typeof part === 'string' ? part : part.segment;
            characters++;
            if (!/^\s+$/u.test(value)) nonSpace++;
        }
        return { words, characters, nonSpace, lines: text ? text.split('\n').length : 0,
            paragraphs: text.trim() ? text.trim().split(/\n[\t ]*\n+/).filter(block => block.trim()).length : 0,
            seconds: words ? Math.ceil(words * 60 / 200) : 0 };
    }
    function transform(input, operation) {
        const text = check(input);
        let result;
        switch (operation) {
            case 'upper': result = text.toLocaleUpperCase('tr-TR'); break;
            case 'lower': result = text.toLocaleLowerCase('tr-TR'); break;
            case 'title': result = text.toLocaleLowerCase('tr-TR').replace(/[\p{L}\p{N}]+(?:['’][\p{L}\p{N}]+)*/gu, word => word.charAt(0).toLocaleUpperCase('tr-TR') + word.slice(1)); break;
            case 'ascii': result = ascii(text); break;
            case 'slug': result = ascii(text.toLocaleLowerCase('tr-TR')).replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); break;
            case 'clean': result = lines(text).replace(/\uFEFF/g, '').split('\n').map(line => line.replace(/[^\S\n]+/g, ' ').trim()).join('\n').replace(/\n{3,}/g, '\n\n').trim(); break;
            case 'spaces': result = lines(text).replace(/[^\S\n]+/g, ' '); break;
            case 'trim-lines': result = lines(text).split('\n').map(line => line.trim()).join('\n'); break;
            case 'blank-lines': result = lines(text).split('\n').filter(line => line.trim()).join('\n'); break;
            case 'dedupe': result = Array.from(new Set(lines(text).split('\n'))).join('\n'); break;
            default: throw new Error('Geçersiz metin işlemi.');
        }
        return check(result);
    }
    return Object.freeze({ LIMIT, analyze, transform });
}));
