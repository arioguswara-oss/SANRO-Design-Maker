# SANRO Design Maker V1 checkpoint

- Integration branch: `develop`.
- Last source SHA: `88491d4f2a4d90e88c3f14bc8e1ff12335a0c386` (implementation source commit on `develop`).
- Verified progress: 0.5% overall (unchanged). New feature code has focused unit evidence but no real WordPress/browser runtime evidence.
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

## Evidence

- PHP lint: PASS for all plugin and test PHP files.
- `php tests/php-smoke.php`: PASS.
- `php tests/storage-contract.php`: PASS.
- `php tests/provider-contract.php`: PASS.
- `php tests/render-contract.php`: PASS.
- `node --check` for editor/background JS: PASS.
- `node tests/remove-background.test.cjs`: PASS (3 tests).
- Browser, WordPress REST/admin/storage, Openverse live search and raster output: NOT TESTED. Previously unavailable runners were not retried.

## Known issues and next tasks

- Run independent WordPress/browser QA after Owner-authorized staging handoff.
- Implement/test PNG raster production output when a supported server raster engine is available; current server master is SVG only.
- Studio source requires product ID, `front`/`back` geometry and a Studio-authorized private master reference. The Maker's single canvas/SVG master has no proven mapping; do not send guessed requests or change SANRO Studio.
- Only Anton is bundled. More Google Font binaries can be added from their official repository with licenses.
- TESTING installer built from `88491d4f2a4d90e88c3f14bc8e1ff12335a0c386`: 211,528 bytes, SHA256 `1f9cee487fbcadb69c09f667bdcafea3e3e9cd04d9114688c522467362506963`. ZIP CRC/root structure/license and symlink checks PASS. Uploaded to Drive `SANRO Design Maker/Releases`; remote metadata confirms exact size/parent and private owner-only sharing. Drive did not return a `sha256Checksum`, so remote checksum is NOT VERIFIED.
- Installer: [TESTING ZIP](https://drive.google.com/file/d/1ZkTPT8X0mP3bnpQMgPVpeQQBVB3edsuc/view?usp=drivesdk) · [SHA256SUMS](https://drive.google.com/file/d/1sVk55SN_heWEpS0WN5pgHM0cpDM4Iyog/view?usp=drivesdk) · [release notes](https://docs.google.com/document/d/11sK0sHBkJHMhlaaYHyo8TynouGYTWwCN/edit?usp=drivesdk&ouid=102665342940633303649&rtpof=true&sd=true) · [source commit metadata](https://drive.google.com/file/d/1eKW3aruJJrMghiZ_mx3bzgzvuCSziOHZ/view?usp=drivesdk).
- Continue remaining independent V1 work; no installer or feature is FINAL RELEASE.
- Continue remaining independent V1 work; no installer or feature is FINAL RELEASE.

## Authorization

Authorized: isolated development, focused local tests, commits to `develop`, private Drive delivery, read-only Studio/staging inspection.
Not authorized: merge to `main`, staging install/change, deployment, production writes, Studio edits, credential access, paid services, Rclone, Hermes or global VPS changes.
