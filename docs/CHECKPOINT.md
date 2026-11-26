# SANRO Design Maker V1 checkpoint

- Integration branch: `develop`.
- Last verified code/test source SHA: `9d87ac4e60e7f955cf6e3434d9bed28a7afe6c34` (code and smoke-test change; subsequent commit updates the plugin README).
- Verified progress: 0.5% overall; Core Editor 2% of its 25% stage. Cycles 2–3 added templates/shapes, but no progress credit until browser verification.

## Implemented

- Responsive WordPress shortcode shell with `/custom/` print CTA.
- Local Fabric.js 7.4.0 MIT bundle, transparent 1000 × 1000 canvas.
- Text, rectangle/circle/triangle/line shapes, PNG/JPEG/WebP upload capped at 1.5 MiB, transforms, editable layers, duplicate/delete, undo/redo, fill/stroke/opacity, alignment, shadow and pinch zoom.
- Six original editable starter templates: vintage, streetwear, adventure, cartoon, floral and typography.
- Guest-only IndexedDB draft recovery with schema version and 3 MiB serialized-state cap.
- Read-only Studio source contract in `docs/STUDIO-CONTRACT-READONLY.md`.

## Evidence

- PHP lint for plugin, bootstrap and smoke harness: PASS.
- `php tests/php-smoke.php`: PASS for shortcode, local assets, canvas/template/shape mount and print CTA under WordPress stubs.
- `node --check` for editor JS, vendor Fabric bundle and browser smoke: PASS.
- Browser interactions: NOT TESTED. Chromium crashes before page execution with sandbox crashpad `EPERM`; retry with crash-reporter flags also failed.
- WordPress MCP staging plugin inventory: NOT VERIFIED (`Transport closed`). No Studio endpoint was called.

## Known issues

- Browser and real WordPress runtime tests are unavailable in current sandbox.
- No permanent account save/claim, secure private server-side image storage, production rendering, asset provider API, local Google Fonts, background removal or admin asset catalog.
- Studio routes are feature-flagged and expect product-specific front/back geometry; staging install/flags were not verified.
- GitHub terminal clone could not resolve `github.com`; isolated source copy is `/home/sanro/projects/sanro-design-maker-exec`, and GitHub MCP commits were read back on `develop`.
- No installer ZIP uploaded; staging and production are unchanged.

## Next tasks

1. Run browser smoke with an allowed Playwright/Chromium runner and fix runtime errors.
2. Verify shape/template selection, object transforms, image upload and IndexedDB recovery on mobile/desktop.
3. Check staging Studio version and feature flags read-only when MCP responds; map the product/front/back schema.
4. Implement and test private image storage, upload validation, owner checks and quotas before permanent image saves.
5. Implement guest-to-account save/claim and My Designs once storage/auth contracts exist.

## Authorization

Authorized: isolated development, local tests, `develop` commits, read-only Studio/staging checks, private Drive delivery when ready.
Not authorized: `main` merge, staging install/change, deployment, production writes, Studio edits, credential access, paid services, Rclone, Hermes or global VPS changes.