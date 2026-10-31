(function () {
    'use strict';
    const root = document.querySelector('.w3-directory');
    if (!root) return;
    const search = root.querySelector('#directory-search');
    const filters = root.querySelectorAll('[data-directory-filter]');
    const normalize = text => text.toLocaleLowerCase('tr-TR').replace(/ı/g, 'i').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const cards = Array.from(root.querySelectorAll('[data-directory-group]')).map(card => ({ element:card, group:card.dataset.directoryGroup, text:normalize(card.dataset.directorySearch) }));
    let selected = 'all';
    function update() {
        const words = normalize(search.value.trim()).split(/\s+/).filter(Boolean);
        let visible = 0;
        cards.forEach(card => {
            const match = (selected === 'all' || card.group === selected) && words.every(word => card.text.includes(word));
            card.element.hidden = !match;
            if (match) visible++;
        });
        root.querySelector('#directory-count').textContent = visible + ' araç' + (visible !== cards.length ? ' / ' + cards.length : '');
        root.querySelector('#directory-empty').hidden = visible !== 0;
        filters.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.directoryFilter === selected)));
    }
    filters.forEach(button => {
        button.disabled = false;
        button.addEventListener('click', () => { selected = button.dataset.directoryFilter; update(); });
    });
    search.disabled = false;
    search.addEventListener('input', update);
    root.querySelector('#directory-reset').addEventListener('click', () => { selected = 'all'; search.value = ''; update(); search.focus(); });
    update();
}());
