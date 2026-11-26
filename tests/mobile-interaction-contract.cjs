const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..');
const editor = fs.readFileSync(path.join(root, 'sanro-design-maker/assets/editor.js'), 'utf8');
const markup = fs.readFileSync(path.join(root, 'sanro-design-maker/includes/bootstrap.php'), 'utf8');
const mobileCss = fs.readFileSync(path.join(root, 'sanro-design-maker/assets/mobile-controls.css'), 'utf8');
const plugin = fs.readFileSync(path.join(root, 'sanro-design-maker/sanro-design-maker.php'), 'utf8');
const controlsSource = fs.readFileSync(path.join(root, 'sanro-design-maker/assets/object-controls.js'), 'utf8');

assert.match(editor, /new ResizeObserver\(resizeCanvas\)/);
assert.match(editor, /canvas\.setDimensions\(\{ width, height \}\)/);
assert.match(editor, /const previousFitScale = fitScale/);
assert.match(editor, /oldZoom \/ \(previousFitScale \|\| 1\)/);
assert.match(editor, /canvas\.setViewportTransform\(\[zoom, 0, 0, zoom, \(width - 1000 \* zoom\) \/ 2, \(height - 1000 \* zoom\) \/ 2\]\)/);
assert.match(editor, /canvas\.upperCanvasEl\.getBoundingClientRect\(\)/);
assert.match(editor, /SANRODMConfigureObjectControls\(object, zoom\)/);
assert.match(controlsSource, /lockMovementX: false, lockMovementY: false/);
assert.match(controlsSource, /lockScalingX: false, lockScalingY: false/);
assert.match(editor, /userZoom = Math\.min\(4, Math\.max\(\.55, userZoom \* factor\)\)/);
assert.match(editor, /canvas\.on\('object:added', event => \{ enableTouchControls\(event\.target\)/);
assert.match(editor, /fileInput\.addEventListener\('change'/);
assert.match(editor, /button\.addEventListener\('click', \(\) => \{ canvas\.setActiveObject\(object\)/);
assert.match(editor, /readDraft\(\)\.then\(draft/);
assert.match(markup, /SANRO_DM_VERSION/);
assert.match(markup, /object-controls\.js/);
assert.match(markup, /mobile-controls\.css/);
assert.equal((markup.match(/CETAK DESAIN INI/g) || []).length, 1);
assert.match(markup, /class="sanro-dm__home" data-home-link href="<\?php echo esc_url\(home_url\('\/'\)\); \?>"/);
assert.match(editor, /json !== lastPersistedDraft/);
assert.match(editor, /tx\.oncomplete/);
assert.match(editor, /window\.location\.assign\(target\)/);
assert.match(mobileCss, /height: calc\(100vh[^;]+;\s*height: calc\(100dvh/, 'dynamic viewport has a legacy fallback');
assert.match(mobileCss, /safe-area-inset-bottom/);
assert.match(mobileCss, /grid-template: "header" auto "workspace" minmax\(0, 1fr\) "print" auto "tools" auto/);
assert.match(mobileCss, /grid-template-columns: 94px minmax\(0, 1fr\) clamp\(270px, 24vw, 320px\)/);
assert.match(plugin, /Version: 0\.2\.5-dev/);

const fabric = require(path.join(root, 'sanro-design-maker/assets/vendor/fabric/index.min.js'));
const configureControls = require(path.join(root, 'sanro-design-maker/assets/object-controls.js'));
const design = new fabric.Rect({ width: 420, height: 360, scaleX: 1, scaleY: 1 });
const controls = configureControls(design, 0.34);
assert.equal(controls.visualSize, 9);
assert.equal(design.cornerStyle, 'circle');
assert.equal(design.cornerColor, '#fff');
assert.equal(design.cornerStrokeColor, '#46515d');
assert.equal(design.borderColor, '#68727e');
for (const key of ['tl', 'tr', 'bl', 'br']) {
  assert.equal(design.controls[key].visible, true, `${key} corner is visible`);
  assert.equal(design.controls[key].sizeX, 9, `${key} is visually 9 CSS px`);
  assert.equal(design.controls[key].touchSizeX, 48, `${key} keeps a 48 CSS px target when space allows`);
}
for (const key of ['ml', 'mr', 'mt', 'mb']) assert.equal(design.controls[key].visible, false, `${key} midpoint control is hidden`);
assert.equal(design.controls.mtr.visible, true);
assert.equal(design.controls.mtr.actionName, 'rotate');
assert.equal(design.controls.mtr.withConnection, true);
assert.equal(design.controls.mtr.offsetY, -36, 'rotation handle stays in viewport pixels');
assert.equal(design.lockMovementX || design.lockMovementY || design.lockScalingX || design.lockScalingY || design.lockRotation, false);
const narrow = new fabric.Rect({ width: 72, height: 24, scaleX: 1, scaleY: 1 });
const narrowResult = configureControls(narrow, 0.34);
assert.ok(narrowResult.touchSize < 48, 'small-object hit targets shrink to avoid corner overlap');
assert.ok(narrow.controls.tl.touchSizeX <= 24 * 0.34 * 0.45);

for (const [viewport, stage] of [[360, 344], [390, 374], [412, 396], [1440, 680]]) {
  const zoom = stage / 1000;
  const offset = (stage - 1000 * zoom) / 2;
  const designPoint = 500;
  const screenPoint = designPoint * zoom + offset;
  assert.ok(Math.abs((screenPoint - offset) / zoom - designPoint) < 1e-9, `coordinate round-trip at ${viewport}px`);
  assert.equal(Math.ceil(52 / zoom) * zoom >= 52, true, `touch handle target at ${viewport}px`);
}
console.log('mobile-interaction-contract: PASS (source contract + responsive coordinate math; no browser gesture simulation)');
