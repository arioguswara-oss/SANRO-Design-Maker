const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const target = process.env.SANRO_TEST_URL;
assert.ok(target, 'Set SANRO_TEST_URL to an authorized Design Maker test page');
const home = new URL('/', target).href;
const viewportCases = [
  [360, 640, true], [390, 844, true], [412, 760, true],
  [1280, 800, false], [1366, 768, false], [1440, 900, false], [1920, 1080, false]
];
const capturePath = process.env.SANRO_TEST_CAPTURE;
const markupPath = process.env.SANRO_TEST_MARKUP;
const evidencePath = process.env.SANRO_TEST_EVIDENCE;
const version = process.env.SANRO_TEST_VERSION || '0.2.5-dev';
const mobileTools = '.sanro-dm__mobile-tools';
const uploadName = 'SANRO-QA.png';

async function installFixture(page) {
  if (!capturePath && !markupPath) return;
  const [capture, markup] = [capturePath, markupPath].map(file => fs.readFileSync(file, 'utf8'));
  const content = capture
    .replace(/<main class="sanro-dm"[\s\S]*?<\/main>/, markup)
    .replace(/<style id="sanro-dm-shapes-inline-css">[\s\S]*?<\/style>/, '')
    .replaceAll('ver=0.2.4-dev', 'ver=' + version);
  await page.route('**/*', route => {
    const request = route.request();
    if (new URL(request.url()).origin !== new URL(target).origin) return route.abort();
    if (['image', 'media', 'font'].includes(request.resourceType())) return route.abort();
    return route.continue();
  });
  await page.route(url => url.href === target, route => route.fulfill({ contentType: 'text/html', body: content }));
  await page.route('**/wp-content/plugins/sanro-design-maker/assets/**', route => {
    const asset = new URL(route.request().url()).pathname.split('/assets/')[1];
    return route.fulfill({ path: path.resolve(__dirname, '../sanro-design-maker/assets', asset) });
  });
}

function assertControlsFit(page, selectors) {
  return page.evaluate(list => {
    const viewport = window.visualViewport;
    return list.flatMap(selector => [...document.querySelectorAll(selector)].map(element => {
      const box = element.getBoundingClientRect();
      const hit = document.elementFromPoint(box.x + box.width / 2, box.y + box.height / 2);
      return [selector, box.width > 0 && box.height > 0 && box.left >= 0 && box.top >= 0 &&
        box.right <= viewport.width + 1 && box.bottom <= viewport.height + 1 && element.contains(hit)];
    }));
  }, selectors).then(entries => {
    assert.ok(entries.length >= selectors.length);
    for (const [selector, fits] of entries) assert.ok(fits, selector + ' is fully visible and reachable');
  });
}

async function checkLayout(page, width, height, mobile) {
  const layout = await page.evaluate(() => {
    const box = selector => {
      const rect = document.querySelector(selector).getBoundingClientRect();
      return [rect.x, rect.y, rect.width, rect.height];
    };
    return {
      page: [innerWidth, innerHeight, document.documentElement.scrollWidth, document.documentElement.scrollHeight],
      shell: box('[data-sanro-dm]'), workspace: box('.sanro-dm__workspace'),
      stage: box('[data-stage]'), canvas: box('.upper-canvas'),
      left: box('.sanro-dm__rail'), right: box('[data-properties]'),
      columns: getComputedStyle(document.querySelector('.sanro-dm__workspace')).gridTemplateColumns.split(' ').length,
      themeHidden: [...document.querySelectorAll('.wp-site-blocks, .wp-block-post-title')].every(node => node.getClientRects().length === 0),
      guest: !document.body.classList.contains('logged-in') && !window.SANRO_DM_API.loggedIn,
      version: new URL(document.querySelector('#sanro-dm-editor-js').src).searchParams.get('ver')
    };
  });
  assert.deepEqual(layout.page, [width, height, width, height]);
  assert.deepEqual(layout.shell, [0, 0, width, height]);
  assert.equal(layout.version, version);
  assert.ok(layout.guest && layout.themeHidden);
  assert.ok(Math.abs(layout.stage[2] - layout.stage[3]) <= 1, 'square stage');
  assert.ok(Math.abs(layout.canvas[2] - layout.canvas[3]) <= 1, 'square Fabric canvas');
  assert.ok(layout.canvas[0] >= 0 && layout.canvas[0] + layout.canvas[2] <= width, 'Fabric canvas fits viewport');
  assert.equal(await page.getByRole('link', { name: 'CETAK DESAIN INI', exact: true }).count(), 1);
  await assertControlsFit(page, ['[data-home-link]', '[data-action=undo]', '[data-action=redo]', '[data-action=download-preview]', '.sanro-dm__print', ...(mobile ? [mobileTools + ' button'] : [])]);
  if (mobile) {
    assert.equal(await page.locator(mobileTools + ' button').count(), 6);
    assert.equal(await page.locator('[data-properties]').isVisible(), false);
    const layers = page.locator(mobileTools + ' [data-action=layers]');
    await layers.tap();
    assert.equal(await layers.getAttribute('aria-expanded'), 'true');
    await assertControlsFit(page, ['[aria-label="Tutup properti"]', '.sanro-dm__print', mobileTools + ' button']);
    const panel = page.locator('[data-properties]');
    const panelScroll = await panel.evaluate(node => {
      const available = node.scrollHeight - node.clientHeight;
      node.scrollTop = available;
      return [available, node.scrollTop, window.scrollY];
    });
    assert.deepEqual(panelScroll, [panelScroll[0], panelScroll[0], 0], 'properties scroll independently');
    await page.getByRole('button', { name: 'Tutup properti' }).tap();
    assert.equal(await panel.isVisible(), false);
    for (const [action, selector] of [['add-shape', '[data-shape-panel]'], ['templates', '[data-template-panel]'], ['assets', '[data-asset-panel]']]) {
      await page.locator(mobileTools + ' [data-action=' + action + ']').tap();
      assert.ok(await page.locator(selector).isVisible());
      await assertControlsFit(page, ['.sanro-dm__print', mobileTools + ' button']);
      await page.locator(selector + ' [data-action=panel-close]').tap();
    }
  } else {
    assert.equal(layout.columns, 3);
    assert.equal(layout.left[0], 0);
    assert.equal(layout.right[0] + layout.right[2], width);
    assert.ok(layout.stage[0] >= layout.left[2] && layout.stage[0] + layout.stage[2] <= layout.right[0]);
  }
  return layout;
}

async function draftObject(page) {
  return page.evaluate(() => new Promise((resolve, reject) => {
    const open = indexedDB.open('sanro-design-maker-guest', 1);
    open.onerror = () => reject(open.error);
    open.onsuccess = () => {
      const db = open.result;
      const request = db.transaction('drafts').objectStore('drafts').get('guest');
      request.onerror = () => reject(request.error);
      request.onsuccess = () => {
        db.close();
        resolve(request.result ? JSON.parse(request.result.json).objects[0] : null);
      };
    };
  }));
}

async function waitForDraft(page, predicate, message) {
  for (let retry = 0; retry < 40; retry++) {
    const object = await draftObject(page);
    if (object && predicate(object)) return object;
    await new Promise(resolve => setTimeout(resolve, 75));
  }
  assert.fail(message);
}

async function checkInteractions(page, context, width, mobile) {
  const imageButton = page.locator((mobile ? mobileTools : '.sanro-dm__rail') + ' [data-action=upload]');
  const png = await page.evaluate(() => {
    const canvas = document.createElement('canvas');
    canvas.width = 420;
    canvas.height = 300;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#1f76bc';
    ctx.fillRect(0, 0, 420, 300);
    ctx.fillStyle = '#ffd35d';
    ctx.fillRect(0, 0, 125, 95);
    return canvas.toDataURL().split(',')[1];
  });
  const [chooser] = await Promise.all([page.waitForEvent('filechooser'), mobile ? imageButton.tap() : imageButton.click()]);
  await chooser.setFiles({ name: uploadName, mimeType: 'image/png', buffer: Buffer.from(png, 'base64') });
  let object = await waitForDraft(page, o => o.type.toLowerCase() === 'image', 'image upload is persisted');

  const canvas = page.locator('.upper-canvas');
  const bounds = await canvas.boundingBox();
  const scale = bounds.width / 1000;
  const point = (x, y) => ({ x: bounds.x + x * scale, y: bounds.y + y * scale });
  const cdp = mobile ? await context.newCDPSession(page) : null;
  const touch = (type, points) => cdp.send('Input.dispatchTouchEvent', { type, touchPoints: points.map((p, id) => ({ ...p, id })) });
  const drag = async (from, to) => {
    if (mobile) {
      await touch('touchStart', [from]);
      for (let step = 1; step <= 8; step++) {
        await touch('touchMove', [{x: from.x + (to.x - from.x) * step / 8, y: from.y + (to.y - from.y) * step / 8}]);
      }
      await touch('touchEnd', []);
    } else {
      await page.mouse.move(from.x, from.y);
      await page.mouse.down();
      await page.mouse.move(to.x, to.y, { steps: 8 });
      await page.mouse.up();
    }
  };

  const center = point(object.left, object.top);
  await drag(center, { x: center.x + 31, y: center.y + 23 });
  const priorPosition = object;
  object = await waitForDraft(page, o => o.left > priorPosition.left + 20 && o.top > priorPosition.top + 15, 'drag changes image position');

  const bottomRight = point(object.left + object.width * object.scaleX / 2, object.top + object.height * object.scaleY / 2);
  bottomRight.x += 2;
  bottomRight.y += 2;
  await drag(bottomRight, { x: bottomRight.x + 25, y: bottomRight.y + 21 });
  const priorScale = object.scaleX;
  object = await waitForDraft(page, o => o.scaleX > priorScale + 0.05, 'resize increases image scale');

  const rotation = point(object.left, object.top - object.height * object.scaleY / 2);
  rotation.y -= 38;
  await drag(rotation, { x: rotation.x + 40, y: rotation.y + 13 });
  object = await waitForDraft(page, o => Math.abs(o.angle) > 5, 'rotation changes image angle');

  if (mobile) {
    const pixels = () => page.locator('.lower-canvas').evaluate(node => {
      const data = node.getContext('2d').getImageData(0, 0, node.width, node.height).data;
      let count = 0;
      for (let offset = 3; offset < data.length; offset += 4) if (data[offset] > 200) count++;
      return count;
    });
    const before = await pixels();
    const midpoint = point(object.left, object.top);
    const pair = spread => [{ x: midpoint.x - spread, y: midpoint.y }, { x: midpoint.x + spread, y: midpoint.y }];
    await touch('touchStart', pair(25));
    for (const distance of [30, 35, 42, 50, 60]) await touch('touchMove', pair(distance));
    await touch('touchEnd', []);
    assert.ok(await pixels() > before * 1.2, 'two-pointer pinch zoom increases rendered image area');
  }

  if (evidencePath) await page.screenshot({ path: path.join(evidencePath, 'sanro-maker-' + (mobile ? 'mobile' : 'desktop') + '-' + width + '.png') });

  const homeLink = page.locator('[data-home-link]');
  assert.equal(await homeLink.getAttribute('href'), home);
  await Promise.all([page.waitForURL(home, { waitUntil: 'domcontentloaded' }), homeLink.click()]);
  assert.equal(await page.locator('[data-sanro-dm]').count(), 0, 'home stays outside editor shell');
  await page.goto(target, { waitUntil: 'domcontentloaded' });
  const recovered = await waitForDraft(page, o => o.angle === object.angle, 'draft survives Home and recovery');
  await page.locator('[data-layer-list] button').first().waitFor({ state: 'attached' });
  assert.deepEqual([recovered.left, recovered.top, recovered.scaleX], [object.left, object.top, object.scaleX]);
  console.log(width + ': upload, drag, resize, rotate, Home dan draft recovery PASS' + (mobile ? ', pinch touch CDP PASS' : ' (mouse)'));
}

(async () => {
  const launch = { executablePath: process.env.SANRO_TEST_CHROMIUM, args: ['--no-sandbox', '--disable-gpu'] };
  if (process.env.SANRO_TEST_PROXY) launch.proxy = { server: process.env.SANRO_TEST_PROXY };
  if (evidencePath) fs.mkdirSync(evidencePath, { recursive: true });
  const browser = await chromium.launch(launch);
  try {
    const results = await Promise.allSettled(viewportCases.map(async ([width, height, mobile]) => {
      const context = await browser.newContext({ viewport: { width, height }, isMobile: mobile, hasTouch: mobile, ignoreHTTPSErrors: Boolean(launch.proxy) });
      try {
        const page = await context.newPage();
        page.on('dialog', dialog => dialog.accept());
        await installFixture(page);
        await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.locator('.upper-canvas').waitFor({ state: 'attached' });
        const layout = await checkLayout(page, width, height, mobile);
        console.log(width + ': layout PASS, canvas ' + layout.canvas.slice(2).join('×'));
        if (width === 390 || width === 1440) await checkInteractions(page, context, width, mobile);
      } finally {
        await context.close();
      }
    }));
    const failures = results.flatMap((result, index) => {
      if (result.status === 'fulfilled') return [];
      console.error(viewportCases[index][0] + ' FAIL', result.reason);
      return [result.reason];
    });
    assert.equal(failures.length, 0);
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
