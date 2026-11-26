const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../sanro-design-maker');
const markup = ['<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1><link rel="stylesheet" href="/assets/editor.css">',
'<main class="sanro-dm" data-sanro-dm><header class="sanro-dm__header"><a class="sanro-dm__brand">SANRO <span>DESIGN MAKER</span></a><div class="sanro-dm__header-actions"><button data-action="undo">↶</button><button data-action="redo">↷</button><span data-save-status></span><a class="sanro-dm__print" href="/custom/">CETAK DESAIN INI</a></div></header>',
'<section class="sanro-dm__template-panel" data-template-panel hidden><div data-template-list></div></section><div class="sanro-dm__workspace"><aside class="sanro-dm__rail"><button data-action="add-text">Text</button><button data-action="upload">Image</button><button data-action="add-shape">Shapes</button><button data-action="duplicate">Duplicate</button><button data-action="delete">Delete</button><button data-action="layers">Layers</button><button data-action="templates">Templates</button><input data-image-file type="file"></aside>',
'<section class="sanro-dm__stage-wrap"><div class="sanro-dm__stage" data-stage><canvas data-design-canvas></canvas></div><p class="sanro-dm__hint">Canvas transparan</p></section>',
'<aside class="sanro-dm__properties" data-properties><h2>Properti objek</h2><label>Warna<input data-prop="fill" type="color"></label><label>Garis<input data-prop="stroke" type="color"></label><label>Garis<input data-prop="strokeWidth" type="range"></label><label>Opacity<input data-prop="opacity" type="range"></label><div class="sanro-dm__property-actions"><button data-action="align-left">Left</button><button data-action="align-center">Center</button><button data-action="align-right">Right</button><button data-action="shadow">Shadow</button></div><h2>Layers</h2><ol data-layer-list></ol><button data-action="save">Simpan desain</button></aside></div>',
'<footer class="sanro-dm__mobile-tools"><button data-action="add-text">Text</button><button data-action="upload">Image</button><button data-action="add-shape">Shapes</button><button data-action="templates">Templates</button><button data-action="layers">Layers</button></footer><a class="sanro-dm__mobile-print" href="/custom/">CETAK DESAIN INI</a><div data-notice></div></main>',
].join('');
(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: process.env.SANRO_TEST_CHROMIUM || '/home/sanro/.cache/ms-playwright/chromium-1255/chrome-linux64/chrome', args: ['--no-sandbox'] });
  try {
    const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
    page.on('dialog', dialog => dialog.accept());
    await page.setContent(markup);
    await page.addStyleTag({ content: fs.readFileSync(path.join(root, 'assets/editor.css'), 'utf8') });
    await page.evaluate(() => {
      const records = new Map();
      window.indexedDB = { open() {
        const request = {};
        setTimeout(() => {
          request.result = { objectStoreNames: { contains: () => true }, transaction() { return { objectStore() { return {
            get(key) { const r={}; setTimeout(() => { r.result=records.get(key); r.onsuccess && r.onsuccess(); },0); return r; },
            put(value,key) { const r={}; setTimeout(() => { records.set(key,value); r.result=key; r.onsuccess && r.onsuccess(); },0); return r; }
          }; } }; } };
          request.onsuccess && request.onsuccess();
        },0);
        return request;
      } };
    });
    await page.addScriptTag({ path: path.join(root, 'assets/vendor/fabric/index.min.js') });
    await page.addScriptTag({ path: path.join(root, 'assets/editor.js') });
    await page.waitForSelector('[data-design-canvas]');
    await page.click('.sanro-dm__rail [data-action="add-text"]');
    await page.waitForSelector('[data-layer-list] button');
    assert.equal(await page.locator('[data-layer-list] button').count(), 1, 'text object appears in layers');
    await page.click('.sanro-dm__rail [data-action="add-shape"]');
    assert.equal(await page.locator('[data-layer-list] button').count(), 2, 'shape object appears in layers');
    await page.click('.sanro-dm__header [data-action="undo"]'); await page.waitForTimeout(160);
    assert.equal(await page.locator('[data-layer-list] button').count(), 1, 'undo removes latest shape');
    await page.click('.sanro-dm__header [data-action="redo"]'); await page.waitForTimeout(160);
    assert.equal(await page.locator('[data-layer-list] button').count(), 2, 'redo restores shape');
    await page.click('.sanro-dm__rail [data-action="duplicate"]'); await page.waitForTimeout(160);
    assert.equal(await page.locator('[data-layer-list] button').count(), 3, 'duplicate creates a layer');
    await page.click('.sanro-dm__rail [data-action="delete"]');
    assert.equal(await page.locator('[data-layer-list] button').count(), 2, 'delete removes selected layer');
    await page.click('.sanro-dm__mobile-tools [data-action="layers"]');
    assert.equal(await page.locator('.sanro-dm').evaluate(el => el.classList.contains('is-properties-open')), true, 'mobile properties panel opens');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, 'mobile has no horizontal overflow');
    await page.click('.sanro-dm__mobile-tools [data-action="templates"]');
    assert.equal(await page.locator('[data-template-panel]').isVisible(), true, 'template panel opens');
    await page.click('[data-template-id="typography"]'); await page.waitForTimeout(160);
    assert.equal(await page.locator('[data-layer-list] button').count(), 6, 'template is added as editable layers');
    await page.waitForFunction(() => document.querySelector('[data-save-status]').textContent.includes('tersimpan'));
    const saved = await page.evaluate(() => new Promise(resolve => { const r=indexedDB.open('sanro-design-maker-guest',1); r.onsuccess=()=>{const q=r.result.transaction('drafts','readonly').objectStore('drafts').get('guest'); q.onsuccess=()=>resolve(!!(q.result&&q.result.json));}; r.onerror=()=>resolve(false); }));
    assert.equal(saved, true, 'guest draft is stored in IndexedDB');
    await page.setViewportSize({ width: 1365, height: 900 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, 'desktop has no horizontal overflow');
    console.log('Browser smoke PASS: mobile/desktop layout, text, shape, undo/redo, duplicate/delete, panel and IndexedDB guest draft');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
