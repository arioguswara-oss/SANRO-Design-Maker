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
      return canvas.loadFromJSON(JSON.parse(json)).then(() => {
        canvas.requestRenderAll(); renderLayers();
      }).finally(() => { restoring = false; });
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
    function addShape() {
      const object = new fabric.Rect({ left: 340, top: 340, width: 260, height: 190, rx: 18, ry: 18, fill: '#e65a33', strokeWidth: 0, name: 'Bentuk' });
      canvas.add(object); canvas.setActiveObject(object); canvas.requestRenderAll(); renderLayers(); snapshot();
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
      const button = event.target.closest('[data-action]'); if (!button) return;
      const action = button.dataset.action;
      if (action === 'undo') undo();
      else if (action === 'redo') redo();
      else if (action === 'add-text') addText();
      else if (action === 'add-shape') addShape();
      else if (action === 'upload') fileInput.click();
      else if (action === 'duplicate') duplicate();
      else if (action === 'delete') remove();
      else if (action === 'layers') showProperties();
      else if (action === 'align-left') align('left');
      else if (action === 'align-center') align('center');
      else if (action === 'align-right') align('right');
      else if (action === 'shadow') updateProperty('shadow', !((selected() || {}).shadow));
      else if (action === 'save') inform('Draft sementara tersimpan di perangkat. Login untuk simpan permanen.');
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
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
}());
