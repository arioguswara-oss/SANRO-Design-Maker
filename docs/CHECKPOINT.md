# SANRO Design Maker V1 checkpoint

- Integration branch: `develop`.
- Last verified code/test source SHA: `fd12bbb8b87105d76dd416af1f2d4bfdc3ed4150` (template panel/runtime additions and smoke test update; later commits are README/docs only).
- Verified progress: 0.5% overall; Core Editor 2% of its 25% stage. Cycle 2 added templates but does not increase verified progress because browser execution failed.

## Implemented

- WordPress shortcode with responsive mobile/desktop shell and print CTA to `/custom/`.
- Fabric.js 7.4.0 MIT bundle loaded locally; transparent 1000 × 1000 canvas.
- Text, rectangle, PNG/JPEG/WebP upload capped at 1.5 MiB, Fabric transforms, editable layers, duplicate/delete, undo/redo, fill/stroke/opacity, alignment, shadow and pinch zoom.
- Six original editable starter templates: vintage, streetwear, adventure, cartoon, floral and typography.
- Guest-only IndexedDB draft recovery with schema version and 3 MiB serialized-state cap.
- Read-only Studio contract notes in `docs/STUDIO-CONTRACT-READONLY.md`.

## Evidence

- PHP lint for plugin entrypoint, bootstrap and smoke script: PASS.
- `php tests/php-smoke.php`: PASS for shortcode, local assets, template-picker mount, transparent canvas and print CTA in a WordPress stub harness.
- `node --check` for editor JS, Fabric bundle and browser test: PASS.
- Browser interactions: NOT TESTED. Chromium process fails before page execution with sandbox crashpad `EPERM`; a retry with crash reporter flags also failed.
- WordPress MCP read-only plugin inventory: NOT VERIFIED (`Transport closed`). Prior page read returned HTTP 200, but installed Studio plugin and feature flags were not confirmed.

## Known gaps

- No WordPress runtime or successful browser run.
- No permanent My Designs/account workflow, private server-side image storage, production rendering, background removal, asset provider API, local Google Fonts or admin catalog.
- Studio source contract requires product ID, product-specific front/back schema, nonce and owner token; staging enablement is not verified and no integration call was made.
- Terminal GitHub clone failed DNS resolution. Local tests use `/home/sanro/projects/sanro-design-maker-exec`; GitHub MCP wrote and read back commits on `develop`.
- No installer ZIP is ready or uploaded; no staging or production change.

## Next tasks (max 5)

1. Run browser smoke with an allowed Playwright/Chromium runner; self-fix runtime failures.
2. Verify transforms, template application, image upload and IndexedDB restore on mobile and desktop.
3. Inspect staging's installed SANRO Studio version and flags read-only when MCP responds; map actual product/front/back design schema.
4. Implement and test private server-side image storage, upload validation, ownership and quota before permanent image saves.
5. Add guest-to-account save/claim and My Designs CRUD after storage/auth contracts are ready.

## Authorization boundaries

Authorized: isolated development, local tests, commits on `develop`, read-only Studio/staging inspection and private Drive delivery when a candidate is ready.
Not authorized: merge to `main`, staging install/change, deployment, production writes, Studio edits, credential access, paid services, Rclone, Hermes or global VPS changes.