/* Resimden PDF: no uploads, network calls, analytics payloads or browser storage. */
(function () {
  'use strict';
  var root = document.querySelector('.w3-pdf');
  if (!root) return;
  var el = function (id) { return document.getElementById('pdf-' + id); };
  var pages = [], nextId = 1, busy = false, cancelled = false, resultUrl = null, draggedId = null;
  var limits = { count: 20, file: 15 * 1024 * 1024, total: 50 * 1024 * 1024, pixels: 30000000, side: 12000 };
  var settings = [el('direction'), el('margin'), el('quality'), el('name')];
  function bytes(n) { return n < 1024 * 1024 ? Math.max(1, Math.round(n / 1024)) + ' KB' : (n / 1024 / 1024).toLocaleString('tr-TR', { maximumFractionDigits: 1 }) + ' MB'; }
  function say(text) { el('status').textContent = text; }
  function fail(text) { el('error').textContent = text || ''; el('error').hidden = !text; }
  function invalidate() {
    if (resultUrl) URL.revokeObjectURL(resultUrl);
    resultUrl = null;
    el('download').removeAttribute('href');
    el('result').hidden = true;
  }
  function setBusy(value) {
    busy = value;
    root.setAttribute('aria-busy', String(value));
    el('pick').disabled = value;
    el('files').disabled = value;
    el('clear').disabled = value;
    el('create').disabled = value || !pages.length;
    settings.forEach(function (input) { input.disabled = value; });
    el('progress-wrap').hidden = !value;
    el('pages').querySelectorAll('button').forEach(function (button) { button.disabled = value || button.dataset.edge === 'true'; });
    el('pages').querySelectorAll('.pdf-page').forEach(function (card) { card.draggable = !value; });
  }
  function button(action, text, title, disabled) {
    var b = document.createElement('button');
    b.type = 'button'; b.dataset.action = action; b.textContent = text;
    b.title = title; b.setAttribute('aria-label', title); b.disabled = !!disabled;
    b.dataset.edge = String(!!disabled);
    return b;
  }
  function render(focus) {
    var list = el('pages');
    list.textContent = '';
    pages.forEach(function (item, index) {
      var card = document.createElement('li');
      card.className = 'pdf-page'; card.dataset.id = String(item.id); card.draggable = !busy;
      var thumb = document.createElement('div'); thumb.className = 'pdf-thumb';
      var image = document.createElement('img'); image.src = item.thumb; image.alt = '';
      image.style.transform = 'rotate(' + item.rotation + 'deg)';
      var number = document.createElement('span'); number.className = 'pdf-page-number'; number.textContent = index + 1;
      thumb.append(image, number);
      var name = document.createElement('span'); name.className = 'pdf-page-name'; name.textContent = item.file.name; name.title = item.file.name;
      var actions = document.createElement('div'); actions.className = 'pdf-page-actions';
      actions.append(
        button('up', '↑', 'Sayfa ' + (index + 1) + ': önceye taşı', index === 0),
        button('down', '↓', 'Sayfa ' + (index + 1) + ': sonraya taşı', index === pages.length - 1),
        button('rotate', '↻', 'Sayfa ' + (index + 1) + ': 90 derece döndür'),
        button('remove', '×', 'Sayfa ' + (index + 1) + ': kaldır')
      );
      card.append(thumb, name, actions); list.appendChild(card);
    });
    el('count').textContent = pages.length;
    el('list-heading').hidden = !pages.length;
    el('create').disabled = busy || !pages.length;
    if (focus) {
      var target = list.querySelector('[data-id="' + focus.id + '"] [data-action="' + focus.action + '"]');
      if (target && !target.disabled) target.focus();
      else el('pick').focus();
    }
  }
  function read(blob) {
    return new Promise(function (resolve, reject) {
      var reader = new FileReader(); reader.onload = function () { resolve(reader.result); };
      reader.onerror = function () { reject(new Error('Dosya okunamadı.')); }; reader.readAsArrayBuffer(blob);
    });
  }
  // Inspect the real signature/dimensions before decoding, not just extension/MIME.
  async function inspect(file) {
    if (!file.size || file.size > limits.file) throw new Error('Dosya boş veya 15 MB sınırını aşıyor.');
    var data = new Uint8Array(await read(file.slice(0, 524288))), width = 0, height = 0;
    if (data.length >= 24 && [137,80,78,71,13,10,26,10].every(function (v, i) { return data[i] === v; }) && String.fromCharCode.apply(null, data.slice(12,16)) === 'IHDR') {
      var view = new DataView(data.buffer); width = view.getUint32(16); height = view.getUint32(20);
    } else if (data[0] === 255 && data[1] === 216) {
      var p = 2;
      while (p + 4 <= data.length) {
        if (data[p++] !== 255) break;
        while (data[p] === 255) p++;
        var marker = data[p++];
        if (marker === 217 || marker === 218) break;
        if (marker === 1 || (marker >= 208 && marker <= 215)) continue;
        if (p + 2 > data.length) break;
        var size = (data[p] << 8) | data[p + 1];
        if (size < 2 || p + size > data.length) break;
        if ([192,193,194,195,197,198,199,201,202,203,205,206,207].indexOf(marker) !== -1 && size >= 8) {
          height = (data[p + 3] << 8) | data[p + 4]; width = (data[p + 5] << 8) | data[p + 6]; break;
        }
        p += size;
      }
    }
    if (!width || !height) throw new Error('Geçerli bir JPG veya PNG görseli seçin.');
    if (width > limits.side || height > limits.side || width * height > limits.pixels) throw new Error('Görsel 30 megapiksel veya 12.000 piksel kenar sınırını aşıyor.');
  }
  function load(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file), image = new Image();
      image.onload = function () { URL.revokeObjectURL(url); resolve(image); };
      image.onerror = function () { URL.revokeObjectURL(url); reject(new Error('Görsel bozuk veya tarayıcınız tarafından açılamıyor.')); };
      image.src = url;
    });
  }
  function toBlob(canvas, quality) {
    return new Promise(function (resolve, reject) {
      canvas.toBlob(function (blob) { if (blob) resolve(blob); else reject(new Error('Görsel işlenemedi. Daha küçük bir görsel deneyin.')); }, 'image/jpeg', quality);
    });
  }
  async function thumbnail(file) {
    var image = await load(file), canvas = document.createElement('canvas');
    canvas.width = canvas.height = 200;
    var ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Tarayıcınız görsel işlemesini desteklemiyor.');
    ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, 200, 200);
    var scale = Math.min(184 / image.naturalWidth, 184 / image.naturalHeight);
    var w = image.naturalWidth * scale, h = image.naturalHeight * scale;
    ctx.drawImage(image, (200-w)/2, (200-h)/2, w, h);
    image.src = '';
    var blob = await toBlob(canvas, .75); canvas.width = canvas.height = 1;
    return URL.createObjectURL(blob);
  }
  async function add(files) {
    if (busy || !files.length) return;
    invalidate(); fail(''); setBusy(true); el('progress-wrap').hidden = true;
    var errors = [], added = 0, total = pages.reduce(function (n, item) { return n + item.file.size; }, 0);
    try {
      for (var i = 0; i < files.length; i++) {
        var file = files[i];
        if (pages.length >= limits.count) { errors.push('En fazla 20 görsel ekleyebilirsiniz.'); break; }
        if (pages.some(function (item) { return item.file.name === file.name && item.file.size === file.size && item.file.lastModified === file.lastModified; })) { errors.push(file.name + ': bu görsel zaten listede.'); continue; }
        if (total + file.size > limits.total) { errors.push(file.name + ': toplam 50 MB sınırını aşıyor.'); continue; }
        try {
          say('Görsel kontrol ediliyor: ' + (i + 1) + ' / ' + files.length);
          await inspect(file);
          var thumb = await thumbnail(file);
          pages.push({ id: nextId++, file: file, thumb: thumb, rotation: 0 });
          total += file.size; added++;
        } catch (error) { errors.push(file.name + ': ' + error.message); }
      }
      render();
      say(added + ' görsel eklendi. Toplam ' + pages.length + ' sayfa · ' + bytes(total) + '.');
      fail(errors.slice(0, 5).join('\n') + (errors.length > 5 ? '\nDiğer geçersiz dosyalar da eklenmedi.' : ''));
    } finally { setBusy(false); el('files').value = ''; }
  }
  function change(id, action) {
    if (busy) return;
    var i = pages.findIndex(function (item) { return item.id === id; });
    if (i < 0) return;
    invalidate();
    if (action === 'remove') { URL.revokeObjectURL(pages[i].thumb); pages.splice(i, 1); }
    else if (action === 'rotate') pages[i].rotation = (pages[i].rotation + 90) % 360;
    else {
      var j = action === 'up' ? i - 1 : i + 1;
      if (j < 0 || j >= pages.length) return;
      var item = pages.splice(i, 1)[0]; pages.splice(j, 0, item);
    }
    render({ id: id, action: action });
    say(action === 'rotate' ? 'Görsel döndürüldü. PDF oluşturduğunuzda bu yön kullanılacak.' : 'Sayfa düzeni güncellendi. Toplam ' + pages.length + ' sayfa.');
  }
  function checkCancel() { if (cancelled) throw new Error('PDF oluşturma iptal edildi. Görselleriniz listede duruyor.'); }
  function tick() { return new Promise(function (resolve) { setTimeout(resolve, 0); }); }
  async function convert() {
    if (busy || !pages.length) return;
    invalidate(); fail(''); cancelled = false; setBusy(true);
    el('cancel').disabled = false;
    el('progress').value = 0;
    try {
      var doc = await PDFLib.PDFDocument.create();
      doc.setTitle('Resimden PDF'); doc.setCreator('W3Uzman Resimden PDF');
      var small = el('quality').value === 'small', maxSide = small ? 1600 : 3200;
      var margin = Number(el('margin').value) * 72 / 25.4;
      for (var i = 0; i < pages.length; i++) {
        checkCancel();
        el('progress-label').textContent = 'Sayfa hazırlanıyor: ' + (i + 1) + ' / ' + pages.length;
        say(el('progress-label').textContent); await tick();
        var item = pages[i], image = await load(item.file), swap = item.rotation % 180 !== 0;
        var ow = image.naturalWidth, oh = image.naturalHeight;
        var scale = Math.min(1, maxSide / Math.max(ow, oh));
        var w = Math.max(1, Math.round(ow * scale)), h = Math.max(1, Math.round(oh * scale));
        var canvas = document.createElement('canvas'); canvas.width = swap ? h : w; canvas.height = swap ? w : h;
        var ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Görsel işlenemedi. Tarayıcınızı yenileyip tekrar deneyin.');
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.translate(canvas.width / 2, canvas.height / 2); ctx.rotate(item.rotation * Math.PI / 180);
        ctx.drawImage(image, -w/2, -h/2, w, h); image.src = '';
        var blob = await toBlob(canvas, small ? .78 : .94), cw = canvas.width, ch = canvas.height;
        canvas.width = canvas.height = 1; checkCancel();
        var embedded = await doc.embedJpg(await read(blob));
        var landscape = el('direction').value === 'landscape' || (el('direction').value === 'auto' && cw > ch);
        var pw = landscape ? 841.89 : 595.28, ph = landscape ? 595.28 : 841.89;
        var fit = Math.min((pw - margin * 2) / cw, (ph - margin * 2) / ch);
        var dw = cw * fit, dh = ch * fit;
        doc.addPage([pw, ph]).drawImage(embedded, { x: (pw-dw)/2, y: (ph-dh)/2, width: dw, height: dh });
        el('progress').value = Math.round((i+1) / pages.length * 90);
        await tick();
      }
      checkCancel(); el('progress-label').textContent = 'PDF dosyası tamamlanıyor…'; await tick();
      var output = await doc.save({ objectsPerTick: 20 }); checkCancel();
      resultUrl = URL.createObjectURL(new Blob([output], { type: 'application/pdf' }));
      var name = el('name').value.replace(/\.pdf$/i, '').replace(/[<>:"/\\|?*\u0000-\u001f\u007f]/g, '-').replace(/[. ]+$/g, '').trim().slice(0,80) || 'belgelerim';
      el('download').href = resultUrl; el('download').download = name + '.pdf';
      el('result-info').textContent = pages.length + ' sayfa · ' + bytes(output.length) + ' · A4';
      el('result').hidden = false; el('progress').value = 100;
      say('PDF hazır. İndirmek için PDF\'yi indir düğmesini kullanın.');
      el('download').focus();
    } catch (error) { fail(error.message || 'PDF oluşturulamadı. Daha az veya daha küçük görselle tekrar deneyin.'); say('PDF oluşturulmadı. Görselleriniz listede duruyor.'); }
    finally { setBusy(false); }
  }
  if (!window.PDFLib || !window.FileReader || !window.URL || !HTMLCanvasElement.prototype.toBlob) {
    fail('PDF aracı yüklenemedi veya tarayıcınız desteklemiyor. Sayfayı yenileyin ya da güncel bir tarayıcı deneyin.'); return;
  }
  el('pick').disabled = false;
  el('pick').addEventListener('click', function () { if (!busy) el('files').click(); });
  el('files').addEventListener('change', function () { add(Array.from(this.files)).catch(function () { fail('Görseller eklenemedi. Daha az dosyayla tekrar deneyin.'); }); });
  el('drop').addEventListener('dragover', function (e) { e.preventDefault(); if (!busy) this.classList.add('is-over'); });
  el('drop').addEventListener('dragleave', function () { this.classList.remove('is-over'); });
  el('drop').addEventListener('drop', function (e) { e.preventDefault(); this.classList.remove('is-over'); if (!busy && e.dataTransfer) add(Array.from(e.dataTransfer.files)).catch(function () { fail('Görseller eklenemedi.'); }); });
  // Never let an accidental file drop navigate away from this page.
  root.addEventListener('dragover', function (e) { e.preventDefault(); });
  root.addEventListener('drop', function (e) { e.preventDefault(); });
  el('pages').addEventListener('click', function (e) { var b = e.target.closest('button[data-action]'); if (b) change(Number(b.closest('.pdf-page').dataset.id), b.dataset.action); });
  el('pages').addEventListener('dragstart', function (e) { var card = e.target.closest('.pdf-page'); if (busy || !card) { e.preventDefault(); return; } draggedId = Number(card.dataset.id); e.dataTransfer.setData('text/plain', String(draggedId)); e.dataTransfer.effectAllowed = 'move'; card.classList.add('is-dragging'); });
  el('pages').addEventListener('dragover', function (e) { if (busy || draggedId === null) return; e.preventDefault(); var card = e.target.closest('.pdf-page'); if (card) card.classList.add('is-target'); });
  el('pages').addEventListener('dragleave', function (e) { var card = e.target.closest('.pdf-page'); if (card) card.classList.remove('is-target'); });
  el('pages').addEventListener('drop', function (e) {
    e.preventDefault(); var card = e.target.closest('.pdf-page');
    if (busy || !card || draggedId === null) return;
    var from = pages.findIndex(function (p) { return p.id === draggedId; }), to = pages.findIndex(function (p) { return p.id === Number(card.dataset.id); });
    if (from >= 0 && to >= 0 && from !== to) { invalidate(); pages.splice(to, 0, pages.splice(from,1)[0]); render(); say('Sayfa sırası güncellendi.'); }
    draggedId = null; el('pages').querySelectorAll('.is-target,.is-dragging').forEach(function (c) { c.classList.remove('is-target','is-dragging'); });
  });
  el('pages').addEventListener('dragend', function () { draggedId = null; this.querySelectorAll('.is-target,.is-dragging').forEach(function (c) { c.classList.remove('is-target','is-dragging'); }); });
  el('clear').addEventListener('click', function () { if (busy) return; pages.forEach(function (p) { URL.revokeObjectURL(p.thumb); }); pages = []; invalidate(); fail(''); render(); say('Tüm görseller kaldırıldı. Yeni görseller seçebilirsiniz.'); el('pick').focus(); });
  settings.forEach(function (input) { input.addEventListener('input', invalidate); input.addEventListener('change', invalidate); });
  el('cancel').addEventListener('click', function () { cancelled = true; this.disabled = true; el('progress-label').textContent = 'İptal ediliyor…'; });
  el('create').addEventListener('click', convert);
  window.addEventListener('pagehide', function (event) { if (!event.persisted) { pages.forEach(function (p) { URL.revokeObjectURL(p.thumb); }); if (resultUrl) URL.revokeObjectURL(resultUrl); } });
}());
