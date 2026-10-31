/* W3Uzman QR payloads. Pure functions; no network or persistent storage. */
(function (root, factory) {
    'use strict';
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.W3QrModel = factory();
}(typeof window !== 'undefined' ? window : this, function () {
    'use strict';
    const bytes = text => new TextEncoder().encode(text);
    function fail(message) { throw new Error(message); }
    function single(value, label, max) {
        const text = String(value || '').trim();
        if (/[\u0000-\u001f\u007f]/.test(text)) fail(label + ' alanında satır sonu veya kontrol karakteri kullanmayın.');
        if (text.length > max) fail(label + ' çok uzun.');
        return text;
    }
    function webUrl(value) {
        let text = single(value, 'Bağlantı', 1500);
        if (!text) fail('Bir web sitesi veya sayfa adresi girin.');
        if (/\s/.test(text) || text.includes('\\')) fail('Geçerli bir web adresi girin; boşluk kullanmayın.');
        if (!/^https?:\/\//i.test(text)) {
            if (/^[a-z][a-z\d+.-]*:/i.test(text) && !/^[^/:]+:\d+(?:\/|$)/.test(text)) fail('Yalnızca HTTP veya HTTPS bağlantıları kullanılabilir.');
            text = 'https://' + text;
        }
        let url;
        try { url = new URL(text); } catch (_) { fail('Geçerli bir web adresi girin.'); }
        if (!['https:', 'http:'].includes(url.protocol) || !url.hostname || url.username || url.password) fail('Kullanıcı adı veya şifre içermeyen bir HTTP/HTTPS adresi girin.');
        if (!url.hostname.includes('.') && url.hostname !== 'localhost') fail('Alan adını uzantısıyla birlikte girin (örnek: w3uzman.com).');
        return url.href;
    }
    function phone(value, required) {
        const text = single(value, 'Telefon', 40);
        if (!text && !required) return '';
        if (!/^\+?[\d ()-]+$/.test(text)) fail('Telefonu ülke koduyla girin (örnek: +90 555 123 45 67).');
        const digits = text.replace(/\D/g, '');
        if (!/^[1-9]\d{6,14}$/.test(digits)) fail('Ülke koduyla birlikte 7–15 haneli bir telefon numarası girin; başına 0 eklemeyin.');
        return digits;
    }
    const wifiEscape = text => text.replace(/[\\;,:\"]/g, '\\$&');
    const cardEscape = text => text.replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,');
    function fold(line) {
        let result = '', length = 0;
        for (const ch of line) {
            const count = bytes(ch).length;
            if (length + count > 75) { result += '\r\n '; length = 1; }
            result += ch;
            length += count;
        }
        return result;
    }
    function build(type, fields) {
        let payload, summary;
        if (type === 'url') {
            payload = webUrl(fields.url);
            summary = new URL(payload).hostname;
        } else if (type === 'wifi') {
            const ssid = String(fields.ssid || '');
            const password = String(fields.password || '');
            const security = fields.security || 'WPA';
            if (!ssid || bytes(ssid).length > 32 || /[\u0000-\u001f\u007f]/.test(ssid)) fail('Ağ adı 1–32 UTF-8 bayt olmalı ve kontrol karakteri içermemeli.');
            if (!['WPA', 'WEP', 'nopass'].includes(security)) fail('Geçerli bir Wi-Fi güvenlik türü seçin.');
            if (security !== 'nopass' && /[\u0000-\u001f\u007f]/.test(password)) fail('Wi-Fi şifresinde kontrol karakteri kullanmayın.');
            if (security === 'WPA' && !((bytes(password).length >= 8 && bytes(password).length <= 63) || /^[\da-f]{64}$/i.test(password))) fail('WPA/WPA2 şifresi 8–63 bayt veya 64 haneli onaltılık anahtar olmalı.');
            if (security === 'WEP' && !((/^[\x20-\x7e]+$/.test(password) && [5, 13].includes(password.length)) || /^(?:[\da-f]{10}|[\da-f]{26})$/i.test(password))) fail('WEP için 5/13 ASCII karakter veya 10/26 onaltılık hane girin.');
            payload = 'WIFI:T:' + security + ';S:' + wifiEscape(ssid) + ';' + (security === 'nopass' ? '' : 'P:' + wifiEscape(password) + ';') + 'H:' + (fields.hidden ? 'true' : 'false') + ';;';
            summary = ssid;
        } else if (type === 'whatsapp') {
            const number = phone(fields.phone, true);
            const message = String(fields.message || '').trim();
            if (message.length > 500 || /[\u0000-\u0008\u000b\u000c\u000e-\u001f\u007f]/.test(message)) fail('Mesaj en fazla 500 karakter olmalı ve kontrol karakteri içermemeli.');
            payload = 'https://wa.me/' + number + (message ? '?text=' + encodeURIComponent(message) : '');
            summary = '+' + number;
        } else if (type === 'vcard') {
            const first = single(fields.first, 'Ad', 80), last = single(fields.last, 'Soyad', 80);
            if (!first && !last) fail('Kişi kartı için ad veya soyad girin.');
            const name = [first, last].filter(Boolean).join(' ');
            const company = single(fields.company, 'Kurum', 120);
            const number = phone(fields.phone, false);
            const email = single(fields.email, 'E-posta', 200);
            if (email && !/^[^\s@<>;,]+@[^\s@<>;,]+\.[^\s@<>;,]+$/.test(email)) fail('Geçerli bir e-posta adresi girin.');
            const url = fields.website ? webUrl(fields.website) : '';
            const lines = ['BEGIN:VCARD', 'VERSION:3.0', 'N:' + cardEscape(last) + ';' + cardEscape(first) + ';;;', 'FN:' + cardEscape(name)];
            if (company) lines.push('ORG:' + cardEscape(company));
            if (number) lines.push('TEL;TYPE=CELL:+' + number);
            if (email) lines.push('EMAIL;TYPE=INTERNET:' + cardEscape(email));
            if (url) lines.push('URL:' + cardEscape(url));
            lines.push('END:VCARD');
            payload = lines.map(fold).join('\r\n') + '\r\n';
            summary = name;
        } else fail('Bir QR kod türü seçin.');
        if (bytes(payload).length > 1800) fail('İçerik QR kod için çok uzun. Bağlantıyı veya metin alanlarını kısaltın.');
        return { payload, summary };
    }
    return Object.freeze({ build, webUrl });
}));
