(function () {
  'use strict';
  const ROOT = '[data-sanro-dm]';
  const LIMIT = 3 * 1024 * 1024;
  const DB_NAME = 'sanro-design-maker-guest';
  const DB_VERSION = 1;

  function ready() {
    document.querySelectorAll(ROOT).forEach(boot);
  }

  function boot(root) {
    if (!window.fabric || !window.fabric.Canvas) return;
    const element = root.querySelector('[data-design-canvas]');
    const stage = root.querySelector('[data-stage]');
    const status = root.querySelector('[data-save-status]');
    const notice = root.querySelector('[data-notice]');
    const fileInput = root.querySelector('[data-image-file]');
    const layers = root.querySelector('[data-layer-list]');
    const templatePanel = root.querySelector('[data-template-panel]');
    const templateList = root.querySelector('[data-template-list]');
    const shapePanel = root.querySelector('[data-shape-panel]');
    const myDesigns = root.querySelector('[data-my-designs]');
    let currentDesignId = '';
    const canvas = new fabric.Canvas(element, {
      width: 1000, height: 1000, backgroundColor: 'rgba(0,0,0,0)',
      preserveObjectStacking: true, selection: true, stopContextMenu: true,
      fireRightClick: false, enableRetinaScaling: true
    });
    let history = [], historyIndex = -1, historyTimer = 0, saveTimer = 0, noticeTimer = 0, restoring = false;
    let dbPromise;

    function inform(message) {
      notice.textContent = message;
      notice.classList.add('is-visible');
      window.clearTimeout(noticeTimer);
      noticeTimer = window.setTimeout(() => notice.classList.remove('is-visible'), 2400);
    }
    function db() {
      if (!('indexedDB' in window)) return Promise.reject(new Error('IndexedDB unavailable'));
      if (!dbPromise) dbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = () => {
          if (!request.result.objectStoreNames.contains('drafts')) request.result.createObjectStore('drafts');
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
      });
      return dbPromise;
    }
    function draftTransaction(mode, action) {
      return db().then(database => new Promise((resolve, reject) => {
        const tx = database.transaction('drafts', mode);
        const request = action(tx.objectStore('drafts'));
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
      }));
    }
    function readDraft() { return draftTransaction('readonly', store => store.get('guest')); }
    function writeDraft(value) { return draftTransaction('readwrite', store => store.put(value, 'guest')); }
    function snapshot() {
      if (restoring) return;
      const json = JSON.stringify(canvas.toJSON(['name', 'data']));
      if (json.length > LIMIT) {
        status.textContent = 'Draft terlalu besar untuk disimpan lokal';
        return;
      }
      const current = history[historyIndex];
      if (current === json) return;
      history = history.slice(0, historyIndex + 1);
      history.push(json);
      if (history.length > 40) history.shift();
      historyIndex = history.length - 1;
      status.textContent = 'Menyimpan draft…';
      window.clearTimeout(saveTimer);
      saveTimer = window.setTimeout(() => {
        writeDraft({ version: 1, updatedAt: Date.now(), json })
          .then(() => { status.textContent = 'Draft lokal tersimpan'; })
          .catch(() => { status.textContent = 'Draft hanya ada di sesi ini'; });
      }, 250);
    }
    function restoreJSON(json) {
      restoring = true;
      return hydrateImages(JSON.parse(json)).then(value => canvas.loadFromJSON(value)).then(() => {
        canvas.requestRenderAll(); renderLayers();
      }).finally(() => { restoring = false; });
    }
    function api(path, options) {
      if (!window.SANRO_DM_API || !window.SANRO_DM_API.loggedIn) return Promise.reject(new Error('Login diperlukan untuk menyimpan permanen.'));
      const opts = Object.assign({ credentials: 'same-origin', headers: {} }, options || {});
      opts.headers = Object.assign({ 'X-WP-Nonce': window.SANRO_DM_API.nonce }, opts.headers);
      return fetch(window.SANRO_DM_API.root + path, opts).then(response => response.ok ? response : response.json().then(error => { throw new Error(error.message || 'Permintaan gagal.'); }));
    }
    async function hydrateImages(json) {
      for (const object of (json.objects || [])) {
        if (!object.data || !object.data.assetId) continue;
        const response = await api('assets/' + encodeURIComponent(object.data.assetId));
        const blob = await response.blob(); object.src = URL.createObjectURL(blob);
      }
      return json;
    }
    async function prepareDesign() {
      const design = canvas.toJSON(['name', 'data']);
      for (let i = 0; i < canvas.getObjects().length; i++) {
        const object = canvas.getObjects()[i]; const saved = design.objects[i];
        if (object.type !== 'image') continue;
        let assetId = object.data && object.data.assetId;
        if (!assetId) {
          const blob = await fetch(object.getSrc()).then(response => response.blob());
          const form = new FormData(); form.append('file', blob, 'design-image');
          const uploaded = await api('assets', { method: 'POST', body: form }).then(response => response.json());
          assetId = uploaded.id; object.data = Object.assign({}, object.data, { assetId });
        }
        saved.data = Object.assign({}, saved.data, { assetId }); saved.src = window.SANRO_DM_API.root + 'assets/' + encodeURIComponent(assetId);
      }
      return JSON.stringify(design);
    }
    async function saveDesign() {
      if (!window.SANRO_DM_API || !window.SANRO_DM_API.loggedIn) { window.location.href = window.SANRO_DM_API ? window.SANRO_DM_API.loginUrl : '/wp-login.php'; return; }
      status.textContent = 'Menyimpan desain…';
      try {
        const design = await prepareDesign();
        const response = await api('designs', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: currentDesignId || undefined, title: 'Desain SANRO', design }) });
        const result = await response.json(); currentDesignId = result.id; status.textContent = 'Desain tersimpan'; inform('Desain tersimpan ke akun Anda.');
        snapshot();
      } catch (error) { status.textContent = 'Gagal menyimpan'; inform(error.message); }
    }
    async function listDesigns(refresh) {
      if (!window.SANRO_DM_API || !window.SANRO_DM_API.loggedIn) { window.location.href = window.SANRO_DM_API ? window.SANRO_DM_API.loginUrl : '/wp-login.php'; return; }
      if (!refresh) myDesigns.hidden = !myDesigns.hidden;
      if (myDesigns.hidden) return;
      myDesigns.textContent = 'Memuat desain…';
      try {
        const items = await api('designs').then(response => response.json()); myDesigns.replaceChildren();
        if (!items.length) { myDesigns.textContent = 'Belum ada desain tersimpan.'; return; }
        items.forEach(item => {
          const row = document.createElement('div'); row.className = 'sanro-dm__design-row';
          const title = document.createElement('span'); title.textContent = item.title;
          const open = document.createElement('button'); open.type = 'button'; open.textContent = 'Buka';
          open.addEventListener('click', async () => { const data = await api('designs/' + encodeURIComponent(item.id)).then(r => r.json()); currentDesignId = data.id; await loadJSON(JSON.stringify(data.design)); inform('Desain dibuka untuk diedit.'); });
          const duplicateButton = document.createElement('button'); duplicateButton.type = 'button'; duplicateButton.textContent = 'Duplikat';
          duplicateButton.addEventListener('click', async () => { const data = await api('designs/' + encodeURIComponent(item.id)).then(r => r.json()); await api('designs', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({title: item.title + ' salinan', design: JSON.stringify(data.design)}) }); inform('Desain diduplikasi.'); listDesigns(true); });
          const del = document.createElement('button'); del.type = 'button'; del.textContent = 'Hapus';
          del.addEventListener('click', async () => { await api('designs/' + encodeURIComponent(item.id), { method: 'DELETE' }); if (currentDesignId === item.id) currentDesignId = ''; inform('Desain dihapus.'); listDesigns(true); });
          row.append(title, open, duplicateButton, del); myDesigns.append(row);
        });
      } catch (error) { myDesigns.textContent = error.message; }
    }
    function loadJSON(json) {
      return restoreJSON(json).then(snapshot);
    }
    function undo() {
      if (historyIndex <= 0) return;
      historyIndex -= 1;
      restoreJSON(history[historyIndex]);
    }
    function redo() {
      if (historyIndex >= history.length - 1) return;
      historyIndex += 1;
      restoreJSON(history[historyIndex]);
    }
    function selected() { return canvas.getActiveObject(); }
    function renderLayers() {
      layers.replaceChildren();
      canvas.getObjects().slice().reverse().forEach((object, reverseIndex) => {
        const index = canvas.getObjects().length - 1 - reverseIndex;
        const item = document.createElement('li');
        const button = document.createElement('button');
        const label = object.name || (object.type === 'i-text' ? object.text : object.type) || 'Objek';
        button.type = 'button'; button.textContent = String(label).slice(0, 48);
        button.setAttribute('aria-current', selected() === object ? 'true' : 'false');
        button.addEventListener('click', () => { canvas.setActiveObject(object); canvas.requestRenderAll(); renderLayers(); });
        item.append(button);
        if (index < 0) return;
        layers.append(item);
      });
    }
    function updateProperty(name, value) {
      const object = selected(); if (!object) return;
      if (name === 'opacity') value = Number(value) / 100;
      if (name === 'strokeWidth') value = Number(value);
      object.set(name, value);
      if (name === 'shadow') object.set('shadow', value ? new fabric.Shadow({ color: 'rgba(0,0,0,.35)', blur: 18, offsetX: 5, offsetY: 6 }) : null);
      canvas.requestRenderAll(); snapshot();
    }
    function addText() {
      const object = new fabric.IText('Tulis teks', { left: 210, top: 220, fontSize: 72, fontFamily: 'Arial', fill: '#20242a', name: 'Teks baru' });
      canvas.add(object); canvas.setActiveObject(object); object.enterEditing(); object.selectAll(); canvas.requestRenderAll(); renderLayers(); snapshot();
    }
    function addShape(kind) {
      let object;
      if (kind === 'circle') object = new fabric.Circle({ left: 340, top: 340, radius: 145, fill: '#e65a33', strokeWidth: 0, name: 'Lingkaran' });
      else if (kind === 'triangle') object = new fabric.Triangle({ left: 340, top: 330, width: 300, height: 300, fill: '#e65a33', strokeWidth: 0, name: 'Segitiga' });
      else if (kind === 'line') object = new fabric.Line([220, 500, 780, 500], { stroke: '#20242a', strokeWidth: 16, fill: '#20242a', name: 'Garis' });
      else object = new fabric.Rect({ left: 340, top: 340, width: 260, height: 190, rx: 18, ry: 18, fill: '#e65a33', strokeWidth: 0, name: 'Persegi' });
      canvas.add(object); canvas.setActiveObject(object); canvas.requestRenderAll(); renderLayers(); snapshot();
    }
    function templateText(text, top, size, fill, options) {
      return new fabric.Textbox(text, Object.assign({ left: 100, top, width: 800, fontSize: size, fontFamily: 'Arial', fontWeight: 'bold', textAlign: 'center', fill, name: 'Teks template' }, options || {}));
    }
    function addTemplate(id) {
      if (canvas.getObjects().length && !window.confirm('Tambahkan template editable ke desain saat ini?')) return;
      const items = [];
      if (id === 'vintage') {
        items.push(new fabric.Circle({ left: 180, top: 130, radius: 320, fill: 'rgba(0,0,0,0)', stroke: '#a64d2f', strokeWidth: 16, name: 'Cincin vintage' }));
        items.push(new fabric.Circle({ left: 215, top: 165, radius: 285, fill: 'rgba(0,0,0,0)', stroke: '#d39a48', strokeWidth: 7, name: 'Garis vintage' }));
        items.push(templateText('YOUR BRAND', 380, 96, '#542f25', { fontFamily: 'Georgia', charSpacing: 60 }));
        items.push(templateText('ORIGINAL GOODS  •  EST. 2026', 510, 30, '#a64d2f', { fontFamily: 'Georgia', charSpacing: 90 }));
      } else if (id === 'streetwear') {
        items.push(new fabric.Rect({ left: 140, top: 200, width: 720, height: 590, fill: 'rgba(0,0,0,0)', stroke: '#1d2530', strokeWidth: 18, name: 'Bingkai streetwear' }));
        items.push(templateText('MOVE\nLOUD', 330, 145, '#f04f35', { stroke: '#1d2530', strokeWidth: 3, lineHeight: .8, charSpacing: 30 }));
        items.push(templateText('MAKE YOUR OWN WAY', 650, 32, '#1d2530', { charSpacing: 160 }));
      } else if (id === 'adventure') {
        items.push(new fabric.Circle({ left: 360, top: 145, radius: 140, fill: '#e7a542', name: 'Matahari adventure' }));
        items.push(new fabric.Triangle({ left: 155, top: 330, width: 360, height: 330, fill: '#31565b', name: 'Gunung kiri' }));
        items.push(new fabric.Triangle({ left: 420, top: 370, width: 390, height: 290, fill: '#527a68', name: 'Gunung kanan' }));
        items.push(templateText('WILD & FREE', 710, 64, '#253b3c', { charSpacing: 100 }));
        items.push(templateText('TAKE THE LONG WAY', 790, 27, '#527a68', { charSpacing: 140 }));
      } else if (id === 'cartoon') {
        items.push(new fabric.Circle({ left: 155, top: 190, radius: 145, fill: '#ffcb4b', stroke: '#273342', strokeWidth: 12, name: 'Lencana kartun' }));
        items.push(new fabric.Triangle({ left: 650, top: 180, width: 180, height: 180, fill: '#6bc6bb', angle: 18, name: 'Bintang kartun' }));
        items.push(new fabric.Circle({ left: 680, top: 600, radius: 90, fill: '#f28b76', name: 'Aksen kartun' }));
        items.push(templateText('HAPPY\nDAYS!', 380, 112, '#ef6046', { stroke: '#293545', strokeWidth: 4, lineHeight: .82 }));
      } else if (id === 'floral') {
        [[500,205],[650,335],[615,520],[385,520],[350,335]].forEach((point,index) => items.push(new fabric.Circle({ left: point[0]-82, top: point[1]-82, radius: 82, fill: index%2 ? '#e7a68c' : '#8cac80', name: 'Kelopak bunga '+(index+1) })));
        items.push(new fabric.Circle({ left: 435, top: 270, radius: 65, fill: '#efd28b', name: 'Pusat bunga' }));
        items.push(templateText('BLOOM DAILY', 690, 56, '#435e4b', { fontFamily: 'Georgia', charSpacing: 90 }));
      } else if (id === 'typography') {
        items.push(templateText('MAKE', 200, 120, '#20242a', { charSpacing: 220 }));
        items.push(templateText('IT YOURS', 370, 112, '#e65a33', { charSpacing: 120 }));
        items.push(new fabric.Rect({ left: 260, top: 570, width: 480, height: 12, fill: '#20242a', name: 'Garis tipografi' }));
        items.push(templateText('IDEAS LOOK GOOD ON YOU', 620, 34, '#20242a', { charSpacing: 100 }));
      }
      if (!items.length) return;
      canvas.add(...items); canvas.setActiveObject(items[items.length-1]); canvas.requestRenderAll(); renderLayers(); snapshot();
      templatePanel.hidden = true;
      inform('Template ditambahkan sebagai objek editable.');
    }
    function renderTemplates() {
      const entries = [
        ['vintage','Vintage','Lencana dan tipografi retro'], ['streetwear','Streetwear','Huruf tebal dengan bingkai'],
        ['adventure','Adventure','Gunung dan matahari'], ['cartoon','Kartun','Warna cerah dan lencana'],
        ['floral','Floral','Motif bunga geometris'], ['typography','Tipografi','Komposisi teks editable']
      ];
      entries.forEach(([id,title,description]) => {
        const button=document.createElement('button'); button.type='button'; button.className='sanro-dm__template-card'; button.dataset.templateId=id;
        const name=document.createElement('strong'); name.textContent=title;
        const detail=document.createElement('span'); detail.textContent=description;
        button.append(name,detail); templateList.append(button);
      });
    }
    function duplicate() {
      const object = selected(); if (!object) return inform('Pilih objek untuk diduplikasi.');
      object.clone().then(copy => { copy.set({ left: (object.left || 0) + 24, top: (object.top || 0) + 24, name: (object.name || 'Objek') + ' salinan' }); canvas.add(copy); canvas.setActiveObject(copy); canvas.requestRenderAll(); renderLayers(); snapshot(); });
    }
    function remove() {
      const object = selected(); if (!object) return inform('Pilih objek untuk dihapus.');
      canvas.remove(object); canvas.discardActiveObject(); canvas.requestRenderAll(); renderLayers(); snapshot();
    }
    function align(where) {
      const object = selected(); if (!object) return inform('Pilih objek untuk diratakan.');
      const width = object.getScaledWidth();
      const left = where === 'left' ? 0 : where === 'center' ? (1000 - width) / 2 : 1000 - width;
      object.set({ left }); object.setCoords(); canvas.requestRenderAll(); snapshot();
    }
    function upload(file) {
      if (!file) return;
      const allowed = ['image/png', 'image/jpeg', 'image/webp'];
      if (!allowed.includes(file.type) || file.size > 1536 * 1024) { inform('Pilih PNG, JPEG, atau WebP hingga 1,5 MB.'); return; }
      const reader = new FileReader();
      reader.onerror = () => inform('Gambar tidak dapat dibaca.');
      reader.onload = () => fabric.Image.fromURL(String(reader.result)).then(image => {
        const scale = Math.min(420 / image.width, 420 / image.height, 1);
        image.set({ left: 290, top: 290, scaleX: scale, scaleY: scale, name: file.name });
        canvas.add(image); canvas.setActiveObject(image); canvas.requestRenderAll(); renderLayers(); snapshot();
      }).catch(() => inform('Gambar tidak dapat dibuka.'));
      reader.readAsDataURL(file);
    }
    function showProperties() { root.classList.toggle('is-properties-open'); }
    root.addEventListener('click', event => {
      const shape = event.target.closest('[data-shape-type]');
      if (shape && shapePanel.contains(shape)) { addShape(shape.dataset.shapeType); shapePanel.hidden=true; return; }
      const template = event.target.closest('[data-template-id]');
      if (template && templateList.contains(template)) { addTemplate(template.dataset.templateId); return; }
      const button = event.target.closest('[data-action]'); if (!button) return;
      const action = button.dataset.action;
      if (action === 'undo') undo();
      else if (action === 'redo') redo();
      else if (action === 'add-text') addText();
      else if (action === 'add-shape') { shapePanel.hidden=!shapePanel.hidden; templatePanel.hidden=true; }
      else if (action === 'upload') fileInput.click();
      else if (action === 'duplicate') duplicate();
      else if (action === 'delete') remove();
      else if (action === 'layers') showProperties();
      else if (action === 'templates') { templatePanel.hidden = !templatePanel.hidden; shapePanel.hidden=true; }
      else if (action === 'templates-close') templatePanel.hidden = true;
      else if (action === 'align-left') align('left');
      else if (action === 'align-center') align('center');
      else if (action === 'align-right') align('right');
      else if (action === 'shadow') updateProperty('shadow', !((selected() || {}).shadow));
      else if (action === 'save') saveDesign();
      else if (action === 'my-designs') listDesigns();
    });
    root.querySelectorAll('[data-prop]').forEach(input => {
      input.addEventListener('input', () => updateProperty(input.dataset.prop, input.value));
    });
    fileInput.addEventListener('change', () => { upload(fileInput.files[0]); fileInput.value = ''; });
    canvas.on('object:added', () => { renderLayers(); window.clearTimeout(historyTimer); historyTimer = window.setTimeout(snapshot, 120); });
    canvas.on('object:modified', () => { renderLayers(); snapshot(); });
    canvas.on('object:removed', () => { renderLayers(); snapshot(); });
    canvas.on('selection:created', renderLayers); canvas.on('selection:updated', renderLayers); canvas.on('selection:cleared', renderLayers);
    root.addEventListener('keydown', event => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') { event.preventDefault(); event.shiftKey ? redo() : undo(); }
      if ((event.key === 'Delete' || event.key === 'Backspace') && !event.target.closest('input,textarea,[contenteditable=true]')) remove();
    });
    let touches = new Map(), lastDistance = 0;
    stage.addEventListener('touchstart', event => { for (const touch of event.changedTouches) touches.set(touch.identifier, touch); if (touches.size === 2) lastDistance = distance([...touches.values()]); }, { passive: true });
    stage.addEventListener('touchmove', event => {
      for (const touch of event.changedTouches) if (touches.has(touch.identifier)) touches.set(touch.identifier, touch);
      if (touches.size >= 2) {
        event.preventDefault(); const points = [...touches.values()]; const next = distance(points);
        if (lastDistance > 0 && next > 0) {
          const rect = stage.getBoundingClientRect();
          const center = new fabric.Point((((points[0].clientX + points[1].clientX) / 2) - rect.left) * canvas.getWidth() / rect.width, (((points[0].clientY + points[1].clientY) / 2) - rect.top) * canvas.getHeight() / rect.height);
          const factor = Math.min(1.12, Math.max(.88, next / lastDistance)); canvas.zoomToPoint(center, Math.min(4, Math.max(.3, canvas.getZoom() * factor)));
        }
        lastDistance = next;
      }
    }, { passive: false });
    stage.addEventListener('touchend', event => { for (const touch of event.changedTouches) touches.delete(touch.identifier); if (touches.size < 2) lastDistance = 0; }, { passive: true });
    function distance(points) { return Math.hypot(points[0].clientX - points[1].clientX, points[0].clientY - points[1].clientY); }
    canvas.setDimensions({ width: 1000, height: 1000 });
    readDraft().then(draft => {
      if (!draft || !draft.json || draft.version !== 1) { snapshot(); return; }
      if (window.confirm('Lanjutkan draft SANRO Design Maker yang tersimpan di perangkat ini?')) loadJSON(draft.json);
      else snapshot();
    }).catch(() => snapshot());
    renderLayers();
    renderTemplates();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
}());
