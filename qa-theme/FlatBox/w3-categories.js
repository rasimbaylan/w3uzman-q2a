(function () {
    'use strict';
    var grid = document.getElementById('w3-cat-grid');
    if (!grid) return;
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.w3-cat-card'));
    var input = document.getElementById('w3-cat-search');
    var sort = document.getElementById('w3-cat-sort');
    var clear = document.getElementById('w3-cat-clear');
    var count = document.getElementById('w3-cat-result-count');
    var empty = document.getElementById('w3-cat-empty');
    var collator = new Intl.Collator('tr', {sensitivity:'base',numeric:true});
    function normalize(value) {
        return value.toLocaleLowerCase('tr').replace(/ı/g, 'i').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    function filter() {
        var query = normalize(input.value.trim());
        var visible = 0;
        cards.forEach(function (card) {
            var match = normalize(card.getAttribute('data-search')).indexOf(query) !== -1;
            card.hidden = !match;
            card.classList.toggle('is-filtered', !!query && match);
            if (match) visible++;
            var details = card.querySelector('.w3-cat-more');
            if (details) details.open = !!query && match;
        });
        count.textContent = visible + ' kategori';
        empty.hidden = visible !== 0;
        clear.hidden = !query;
    }
    input.disabled = sort.disabled = false;
    input.addEventListener('input', filter);
    clear.addEventListener('click', function () { input.value = ''; filter(); input.focus(); });
    input.form.addEventListener('submit', function (event) { event.preventDefault(); filter(); });
    sort.addEventListener('change', function () {
        cards.sort(function (a,b) {
            if (sort.value === 'popular') return Number(b.dataset.count)-Number(a.dataset.count) || Number(a.dataset.order)-Number(b.dataset.order);
            if (sort.value === 'name') return collator.compare(a.dataset.name,b.dataset.name);
            return Number(a.dataset.order)-Number(b.dataset.order);
        }).forEach(function (card) { grid.appendChild(card); });
    });
}());
