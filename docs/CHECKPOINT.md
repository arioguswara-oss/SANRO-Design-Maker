# SANRO Design Maker V1 checkpoint

- Current integration branch: `develop`.
- Last verified code and test source SHA: `43a7c0a40895ce7812bf39ddd5d97827b2f87866` (subsequent commits record vendor metadata, portable test loading and execution notes).
- GitHub branch head before this checkpoint file: `98752a2496535ed113b3b87d140dae14489f0847`.
- Verified functional progress: 0.5% overall; Core Editor 2% of its 25% stage. No staging or final acceptance credit.

## Implemented

- WordPress shortcode shell with mobile/desktop layout and `/custom/` print CTA.
- Locally bundled Fabric.js 7.4.0 (MIT, tag `v740`) and transparent 1000 × 1000 editor canvas.
- Text and rectangle tools, PNG/JPEG/WebP file selection capped at 1.5 MiB, Fabric selection/transforms, layer selection, duplicate/delete, undo/redo, basic fill/stroke/opacity, alignment, shadow, pinch zoom and keyboard shortcuts.
- Guest IndexedDB draft recovery with schema version and a 3 MiB serialized-state limit.
- Test scripts, vendor license/version record, progress ledger and this resume checkpoint.

## Test evidence

- PHP lint: PASS for plugin entrypoint and bootstrap.
- `tests/php-smoke.php`: PASS for shortcode output, asset enqueue, canvas mount and print CTA in a WordPress stub harness.
- `node --check`: PASS for editor JS, upstream Fabric bundle and browser smoke script.
- Browser interactions: NOT TESTED. Chromium is installed but crashes before page execution with sandbox crashpad `EPERM`; retry with crash reporter flags failed the same way.
- Full WordPress runtime, mobile object transforms, file upload/reopen, IndexedDB in real browser, security and accessibility: NOT TESTED.

## Known issues

- Browser execution is blocked by the current sandbox runtime.
- PHP and WordPress behavior has only been verified with a local stub harness, not a WordPress installation.
- Permanent account saves, server-side upload validation/private asset storage, production rendering, asset/template/font providers, background removal and SANRO Studio handoff are not implemented.
- The local source workspace is isolated at `/home/sanro/projects/sanro-design-maker-exec`; a terminal clone could not resolve `github.com`, so local tests used the synchronized workspace copy and commits were written through the authenticated GitHub MCP to `develop`.

## Next tasks (max 5)

1. Run the existing browser smoke with an allowed Playwright/Chromium runner; resolve any runtime failures.
2. Verify image handling, transform gestures, mobile pinch zoom and IndexedDB restore in browser.
3. Inspect SANRO Studio integration contract read-only and document exact handoff requirements.
4. Design and implement secure server-side asset storage, guest/owner authorization and quota endpoints.
5. Continue asset/template catalog and account/guest claim flows after storage and identity contracts are established.

## Authorization boundaries

- Authorized: isolated development, local tests, commits on `develop`, read-only Studio/staging inspection, private Google Drive candidate delivery when ready.
- Not authorized: merge to `main`, staging install or changes, deployment, production writes, SANRO Studio modifications, credential access, paid services, Rclone, Hermes/global VPS changes.
- No installer candidate is ready; no artifact has been uploaded. Staging URL remains unmodified.