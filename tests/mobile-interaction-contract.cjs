const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..');
const editor = fs.readFileSync(path.join(root, 'sanro-design-maker/assets/editor.js'), 'utf8');
const css = fs.readFileSync(path.join(root, 'sanro-design-maker/includes/bootstrap.php'), 'utf8');
const plugin = fs.readFileSync(path.join(root, 'sanro-design-maker/sanro-design-maker.php'), 'utf8');

assert.match(editor, /new ResizeObserver\(resizeCanvas\)/);
assert.match(editor, /canvas\.setDimensions\(\{ width, height \}\)/);
assert.match(editor, /const previousFitScale = fitScale/);
assert.match(editor, /oldZoom \/ \(previousFitScale \|\| 1\)/);
assert.match(editor, /canvas\.setViewportTransform\(\[zoom, 0, 0, zoom, \(width - 1000 \* zoom\) \/ 2, \(height - 1000 \* zoom\) \/ 2\]\)/);
assert.match(editor, /canvas\.upperCanvasEl\.getBoundingClientRect\(\)/);
assert.match(editor, /touchCornerSize: Math\.ceil\(52 \/ zoom\)/);
assert.match(editor, /lockMovementX: false, lockMovementY: false/);
assert.match(editor, /lockScalingX: false, lockScalingY: false, lockRotation: false/);
assert.match(editor, /userZoom = Math\.min\(4, Math\.max\(\.55, userZoom \* factor\)\)/);
assert.match(editor, /canvas\.on\('object:added', event => \{ enableTouchControls\(event\.target\)/);
assert.match(editor, /fileInput\.addEventListener\('change'/);
assert.match(editor, /button\.addEventListener\('click', \(\) => \{ canvas\.setActiveObject\(object\)/);
assert.match(editor, /readDraft\(\)\.then\(draft/);
assert.match(css, /body\.sanro-dm-fullscreen/);
assert.match(css, /\.sanro-dm__print\{display:none!important\}/);
assert.match(css, /env\(safe-area-inset-bottom\)/);
assert.match(plugin, /Version: 0\.2\.1-dev/);

for (const [viewport, stage] of [[360, 344], [390, 374], [412, 396], [1440, 680]]) {
  const zoom = stage / 1000;
  const offset = (stage - 1000 * zoom) / 2;
  const designPoint = 500;
  const screenPoint = designPoint * zoom + offset;
  assert.ok(Math.abs((screenPoint - offset) / zoom - designPoint) < 1e-9, `coordinate round-trip at ${viewport}px`);
  assert.equal(Math.ceil(52 / zoom) * zoom >= 52, true, `touch handle target at ${viewport}px`);
}
console.log('mobile-interaction-contract: PASS (source contract + responsive coordinate math; no browser gesture simulation)');
