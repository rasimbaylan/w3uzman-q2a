(function () {
    'use strict';
    var address = document.getElementById('w3-ip-address');
    var button = document.getElementById('w3-ip-copy');
    var status = document.getElementById('w3-ip-status');
    var map = document.getElementById('w3-ip-map');
    var mapButton = document.getElementById('w3-ip-show-map');
    if (map && mapButton) {
        mapButton.hidden = false;
        mapButton.addEventListener('click', function () {
            var latitude = Number(map.getAttribute('data-lat'));
            var longitude = Number(map.getAttribute('data-lon'));
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || Math.abs(latitude) > 90 || Math.abs(longitude) > 180) { return; }
            var centre = Math.max(-85, Math.min(85, latitude));
            var bounds = [Math.max(-180, longitude - 0.18), Math.max(-85, centre - 0.12), Math.min(180, longitude + 0.18), Math.min(85, centre + 0.12)].map(function (value) { return value.toFixed(5); }).join(',');
            var frame = document.createElement('iframe');
            frame.title = 'IP adresinin yaklaşık konumu — OpenStreetMap';
            frame.setAttribute('referrerpolicy', 'no-referrer');
            frame.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups');
            frame.src = 'https://www.openstreetmap.org/export/embed.html?bbox=' + encodeURIComponent(bounds) + '&layer=mapnik&marker=' + encodeURIComponent(centre + ',' + longitude);
            map.textContent = '';
            map.appendChild(frame);
            frame.focus();
        });
    }
    if (!address || !button || !status) { return; }
    button.hidden = false;

    function legacyCopy() {
        var selection = window.getSelection();
        var saved = [];
        if (!selection) { return false; }
        for (var i = 0; i < selection.rangeCount; i++) { saved.push(selection.getRangeAt(i).cloneRange()); }
        var range = document.createRange();
        range.selectNodeContents(address);
        selection.removeAllRanges();
        selection.addRange(range);
        var copied = false;
        try { copied = document.execCommand('copy'); } catch (error) { copied = false; }
        if (copied) {
            selection.removeAllRanges();
            saved.forEach(function (savedRange) { selection.addRange(savedRange); });
        }
        return copied;
    }

    button.addEventListener('click', function () {
        var text = address.textContent.trim();
        status.textContent = '';
        function finish(copied) {
            status.textContent = copied ? 'IP adresi kopyalandı.' : 'Adresi seçip Ctrl+C veya cihazınızın kopyalama seçeneğini kullanabilirsiniz.';
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () { finish(true); }, function () { finish(legacyCopy()); });
        } else {
            finish(legacyCopy());
        }
    });
})();
