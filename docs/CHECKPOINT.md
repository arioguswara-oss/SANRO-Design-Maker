# SANRO Design Maker V1 checkpoint

- Integration branch: `develop`.
- Last source SHA: `9ff26d96a963d10e69bae28319f7db22f104af42` (implementation commit on `develop`; checkpoint documentation follows).
- Verified progress: 0.5% overall; weighted score unchanged because this cycle's new account/storage behavior has syntax and editor-shell evidence only, not WordPress/browser runtime verification.
- Workspace: isolated source copy at `/home/sanro/projects/sanro-design-maker-exec`; this directory is not a Git checkout. Changes were published to `develop` through GitHub MCP after local checks; branch head also includes checkpoint-only commits.

## Implemented

- Existing editor foundation and six editable starter templates; Fabric.js 7.4.0 MIT bundled locally.
- Logged-in REST-backed My Designs: save/update, list, reopen/edit, duplicate and delete. Login redirect leaves guest IndexedDB draft recoverable; saving after login associates it with the WordPress account.
- REST upload accepts only uploaded PNG/JPEG/WebP, checks file MIME and image dimensions, enforces 8 MiB upload, 32 megapixel limit, per-account 64 MiB/100-asset quota and per-hour upload rate limit.
- Asset and design records are scoped by current WordPress user. Stored assets are UUID-named with restrictive permissions in a directory that must resolve outside both WordPress root and document root; storage fails closed otherwise.
- Saved design JSON is size/object bounded and rejects inline data URLs or image references that are not private assets owned by the current account. Account-authenticated asset reads stream privately with no-store headers.
- Daily GC removes only unreferenced private assets older than 180 days. Existing user designs are not age-deleted.

## Evidence

- PHP syntax checks: PASS for plugin entrypoint, bootstrap and storage module.
- `php tests/php-smoke.php`: PASS for shortcode/editor shell, local assets, shape/template mount and print CTA.
- `php tests/storage-contract.php`: PASS for ownership/private-reference validation boundaries; this is a focused unit test, not WordPress REST integration evidence.
- `node --check` for editor JS and browser smoke script: PASS.
- Browser UI test: NOT TESTED. No alternate browser runner/desktop tool is exposed in this session and local Playwright package is absent. The prior Chromium crash method was not repeated.
- WordPress route/database/private filesystem/account lifecycle integration tests: NOT TESTED.
- Staging and SANRO Studio unchanged. No installation, deployment or production changes.

## Known issues and next tasks

- Exercise authenticated REST routes and storage permission behavior in a local WordPress runtime (focused serializer validation test exists; full integration remains NOT TESTED); specifically verify uploads, cross-account denial, CRUD, quotas and GC.
- Run browser tests on desktop/mobile with Fabric image upload and guest draft recovery after login.
- Verify response/error UX, My Designs UI and private image rehydration in browser.
- Continue independent Asset/Template and Admin functions, then complete Studio contract integration only after read-only endpoint evidence.
- Build installer, checksum and release metadata only after an installable candidate is ready.

## Authorization

Authorized: isolated development, local tests, commits to `develop`, private Drive delivery when candidate ready, read-only staging/Studio inspection.
Not authorized: merge to `main`, staging install/change, deployment, production writes, Studio edits, credential access, paid services, Rclone, Hermes or global VPS changes.
