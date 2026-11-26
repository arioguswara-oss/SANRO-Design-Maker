# SANRO Design Maker V1 checkpoint

- Integration branch: `develop`.
- Last source SHA: `d38b3cdf876adbbb393a8f87c9e72393ee78f2bf` (P0 implementation commit on `develop`).
- Staging candidate under independent QA: `88491d4f2a4d90e88c3f14bc8e1ff12335a0c386` (0.2.0-dev). Owner supplied QA: ZIP checksum/integrity, WP install/activation and shortcode HTTP 200 PASS; Android image upload PASS; image drag/resize and responsive header FAIL.
- Verified progress: 0.5% overall (unchanged). P0 regression tests cover coordinate math and source contracts, not real browser gestures.
- Workspace: isolated source copy at `/home/sanro/projects/sanro-design-maker-exec`, not a Git checkout. Changes are published to `develop` through GitHub MCP with an expected-head check.

## Implemented

- Existing guest-first Fabric editor, six editable starter templates and IndexedDB recovery.
- WordPress-native Admin sections: Dashboard, Assets, Templates, Fonts, Provider API, Watermark, Storage & Quota, Studio Bridge and Diagnostics. Data-changing admin actions check capability and nonce.
- Openverse official API search filters commercial use and whitelists CC0/PDM/CC BY/CC BY-SA. Results require safe HTTPS links; image proxy accepts only a cached official result ID, screens DNS/IP, blocks redirects, validates image MIME/dimensions, limits response size, and retains attribution/source/license.
- Bundled local Anton TrueType font with OFL 1.1. Admin can upload/delete additional approved Google Fonts WOFF2 files. Local faces use `font-display: swap` and load when selected.
- Free on-device flat-color edge background removal (8 MP cap). No paid/AI service.
- 480 × 480 JPEG preview with configurable watermark; no transparent HD download.
- Owner-only share creation/list/revocation. Tokens are stored hashed, links expire in 7 days, shared designs are read-only, and share asset routes require an active token and an asset reference in that design.
- Owner-only server generation of 4000 × 4000 resolution-independent SVG masters. Masters and source assets are stored outside webroot with restricted permissions; only metadata/hash is returned and no public master download route exists.
- P0 mobile fix implemented: responsive Fabric backing dimensions track the stage with a centered 1000-unit viewport transform; pinch coordinates derive from the upper canvas rectangle; transforms remain unlocked and touch hit targets scale to 52 CSS px. Fullscreen shell styles apply only to pages containing the shortcode; mobile keeps one bottom print CTA.

## Evidence

- PHP lint: PASS for all plugin and test PHP files.
- `php tests/php-smoke.php`: PASS.
- `php tests/storage-contract.php`: PASS.
- `php tests/provider-contract.php`: PASS.
- `php tests/render-contract.php`: PASS.
- `node --check` for editor/background JS: PASS.
- `node tests/remove-background.test.cjs`: PASS (3 tests).
- `node tests/mobile-interaction-contract.cjs`: PASS for source wiring and coordinate round-trip/handle math at 360, 390, 412 and 1440 CSS px. It does not simulate browser gestures.
- Focused P0 suite (PHP lint/smoke/storage/provider/render; JS syntax/background pixel tests): PASS after the fix.
- Owner-supplied pre-fix staging QA: install/activation/front-end HTTP 200 and Android upload PASS; drag/resize/header FAIL. Post-fix browser/staging regression: NOT TESTED; staging is untouched.
- `0.2.1-dev` package: [TESTING ZIP](https://drive.google.com/file/d/1MzGMTIt_9NHF30p4SpHoogGXsMCWacwC/view?usp=drivesdk) (212,943 bytes, SHA256 `0b273327a7f5c76b13c0c6aeff7e620c88b3a5d6858c1a2956f51c1fa2070692`); [SHA256 manifest](https://drive.google.com/file/d/1CCMFMWdIIrEjH3g7zoX2aEKphLo0GjJO/view?usp=drivesdk); [release notes](https://docs.google.com/document/d/1s8hOgOPE3xKh0okzCsbd3VwWBvmVxUYq/edit?usp=drivesdk&ouid=102665342940633303649&rtpof=true&sd=true).
- ZIP root/CRC and filename checks PASS. Drive metadata confirms filename, 212,943-byte size, expected Releases parent and private owner-only sharing; remote SHA256 metadata unavailable (NOT VERIFIED).
- Browser, WordPress REST/admin/storage, Openverse live search and raster output: NOT TESTED. Previously unavailable runners were not retried.

## Known issues and next tasks

- Run independent WordPress/browser QA after Owner-authorized staging handoff.
- Implement/test PNG raster production output when a supported server raster engine is available; current server master is SVG only.
- Studio source requires product ID, `front`/`back` geometry and a Studio-authorized private master reference. The Maker's single canvas/SVG master has no proven mapping; do not send guessed requests or change SANRO Studio.
- Only Anton is bundled. More Google Font binaries can be added from their official repository with licenses.
- Superseded `0.2.0-dev` installer: 211,528 bytes, SHA256 `1f9cee487fbcadb69c09f667bdcafea3e3e9cd04d9114688c522467362506963`; replaced due independent QA P0 failures.
- Build and privately upload the `0.2.1-dev` P0 installer after source commit; request ChatGPT independent checksum and staging QA. Do not deploy from this workspace.
- No installer or feature is FINAL RELEASE.

## Authorization

Authorized: isolated development, focused local tests, commits to `develop`, private Drive delivery, read-only Studio/staging inspection.
Not authorized: merge to `main`, staging install/change, deployment, production writes, Studio edits, credential access, paid services, Rclone, Hermes or global VPS changes.
