# SANRO Design Maker V1 checkpoint

- Integration branch `develop`. Source for v0.2.5-dev TESTING is `873dc1a1d1a443107dc737b00eae3ebe97b42e74`.
- WordPress confirms v0.2.5-dev is active on [Hostinger staging](https://saddlebrown-peafowl-616656.hostingersite.com/design-maker/). Guest browser loaded v0.2.5-dev assets.
- Private [TESTING installer](https://drive.google.com/file/d/1TpqqKxGKSYTlHvOoTFL5_oNpnhY-RtiY/view?usp=drivesdk) is 217,381 bytes. ZIP root, CRC and SHA256 read back from Drive passed. SHA256 `f3065f945e74301231eb541cfc291557f4d974ae8486903457f141d0c9ee7997`.
- Overall verified progress is 0.5% → 0.5% (+0.0 percentage points). Responsive acceptance is verified within Core Editor. The ledger assigns the whole Core Editor 25% and has no separate responsive-layout subweight.

## v0.2.5-dev staging acceptance

- v0.2.4-dev runtime reproduced page overflow. At 390 CSS px the theme wrapper scrolled to 420 px, the editor measured 421 px, and Preview extended to x=413. At desktop 1366 CSS px the document scrolled to 1416 px. Fixed and translated panels also shared overlapping vertical bounds with the mobile actions.
- The editor is mounted at the viewport root on the dedicated page. One grid owns the header, canvas area, print CTA and mobile toolbar. Context panels are closeable and scroll within the workspace. Desktop uses tools, square Fabric canvas, and properties/layers columns. Home waits for the IndexedDB transaction to commit. The Fabric rotation handle keeps a 36 CSS px offset across zoom levels and the 9 CSS px handles are preserved.
- **PASS** on installed staging as guest in fresh Chromium contexts. Mobile layout at 360, 390, 412 CSS px and desktop three-column layout at 1280, 1366, 1440, 1920 CSS px. All target menus reachable. Document width equals viewport width. The mobile properties panel opens, scrolls when contents exceed its bounds, and closes while the print CTA and toolbar remain reachable. At 1363 CSS px Cloud Browser separately measured document width 1363 px and three desktop columns.
- **PASS** on staging with synthetic PNG at 390 CSS px using Chromium touch emulation and 1440 CSS px using mouse. Upload, drag, resize, rotation, Home and draft recovery. Two-touch pinch zoom changed the rendered area on Chromium at 390 CSS px.
- **PASS** PHP lint, PHP smoke/storage/provider/render contracts, JavaScript syntax, mobile interaction contract and background-removal tests. Screenshot evidence from staging at mobile 390 × 844 and desktop 1440 × 900 CSS px.
- **NOT TESTED** on physical Android. **GO** for staging QA. **NO-GO** for Final Release, merge, or production deployment.

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
- Minimalist object controls implemented for V0.2.2: Fabric 7.4 visual corner diameter 9 CSS px with white fill/thin outline; four corner handles only; thin selection border; small rotation control icon. Per-control touch hit area is capped at 48 CSS px and shrinks with the rendered object bounds to avoid corner overlap. Mobile toolbar buttons flex within the viewport and CTA respects safe-area insets and max width.
- Owner acceptance `0.2.2-dev`: minimalist controls PASS/APPROVED; drag, resize, rotation and pinch PASS. Mobile layout FAIL: toolbar shifted out of view, canvas over-width, Preview clipped and vertical scrolling unavailable.
- `0.2.3-dev` mobile shell implemented: mobile-only flex app shell with `100vh` fallback and `100dvh`, safe-area padding, in-flow print CTA and toolbar, constrained stage/Fabric wrapper, horizontally scrollable toolbar and vertically scrollable property/asset/shape/template panels. Transformation code is unchanged.
- Owner reports `0.2.3-dev` checksum independently verified and candidate active on staging. Owner found desktop editor constrained to part of the screen with the WordPress page title visible behind it, and requested Home navigation.
- `0.2.4-dev` desktop shell uses viewport-fixed flex + full-width three-column workspace (tools, flexible canvas, properties/layers), removes page-title/theme-container constraints only on shortcode pages, and scales the center canvas against viewport dimensions. Added adjacent 44 px Home icon linking via `home_url('/')`; changed drafts prompt and are persisted to IndexedDB before leaving. Failed persistence keeps the editor open. Mobile shell and Fabric transformations remain unchanged.

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
- Owner acceptance evidence for `0.2.1-dev`: upload, drag, resize up/down, pinch and hidden theme header/footer PASS. Owner rejected the large blue selection control appearance. This is prior-version evidence and does not verify the new minimalist visual treatment.
- `node tests/mobile-interaction-contract.cjs`: PASS against the bundled Fabric 7.4 objects for 9 px circular corner visuals, up-to-48 px separate touch areas, four corners, rotation action, control locks, and responsive coordinate math. Real control rendering and no-overflow browser check NOT TESTED.
- `0.2.3-dev` focused PHP lint/smoke/storage/provider/render, JavaScript syntax and background-removal tests: PASS. Mobile layout/interaction source contract and size-budget math for 360/390/412 CSS px: PASS. Browser smoke is NOT TESTED: `tests/editor-smoke.cjs` could not load missing Playwright module. No post-fix browser/owner/staging acceptance is claimed.
- `0.2.4-dev` PHP lint, JS syntax, smoke/storage/provider/render, background-removal and responsive layout/navigation source contract: PASS. Static width budget checked at mobile 360/390/412 and desktop 1280/1366/1440/1920 CSS px. Browser-rendered fullscreen, actual theme-title suppression, Home click/confirmation/save, mobile gestures and desktop canvas interactions: NOT TESTED (no browser runtime evidence in this cycle).
- `0.2.4-dev` TESTING installer source `45e2c4b264d598eb89ec1e33911a96b7cff1c2fa`: [ZIP](https://drive.google.com/file/d/1pidEW1SDTn_nPsOk119GeFYhemJBUhDn/view?usp=drivesdk) (216,407 bytes, SHA256 `614793bf9cdf66f531f23cab14b7f0e1cc9b0b88a3ec6e7a8bd6fc74b6b46cb2`); [SHA256 manifest](https://drive.google.com/file/d/1OjYHp4d9QhjP0ZiMCV9S7PE2hcoNiZYY/view?usp=drivesdk); [release notes](https://docs.google.com/document/d/1LUSrMtIqvM_4cIwbwvv540Mv7v4S49ZN/edit?usp=drivesdk&ouid=102665342940633303649&rtpof=true&sd=true); [source commit](https://drive.google.com/file/d/1J-fbw-8AMa9l3CvcdxFAEXiEeX5cxKMZ/view?usp=drivesdk). ZIP root/CRC and local SHA manifest PASS. Drive confirms name, size, Releases parent and `shared=false` owner-only permission; remote SHA256 NOT VERIFIED. Staging untouched.
- `0.2.3-dev` TESTING installer, source `4568a6ff9fbfa16753070545837c4a6f40784530`: [ZIP](https://drive.google.com/file/d/1sSgr4DuuBpqGU1t2P-UWdycQYJiiny8o/view?usp=drivesdk) (215,418 bytes, SHA256 `7734fab4e419c11105052a65342f59057f821073a3b65f851f7ecdb94ba5b272`); [SHA256 manifest](https://drive.google.com/file/d/1h0eveLEpskDgs8vF7nwN8uKXrYdoSVDS/view?usp=drivesdk); [release notes](https://docs.google.com/document/d/1SzfAPhfwZOw_Vj2A1mym0-cuQcxk7tT6/edit?usp=drivesdk&ouid=102665342940633303649&rtpof=true&sd=true); [source commit](https://drive.google.com/file/d/1I1QklPuPRMa5bs5gvwYnBnW1kWI6OOee/view?usp=drivesdk). ZIP root/CRC and local SHA manifest PASS. Drive confirms byte size, Releases parent and private owner-only permission; remote SHA256 NOT VERIFIED.
- `0.2.2-dev` TESTING ZIP: [installer](https://drive.google.com/file/d/1EOYW88B3liPtoxZ2hcmog7m7knJlDLnN/view?usp=drivesdk) (214,536 bytes, SHA256 `ba70e1dc23086ee4570061937ec330676398d4c7794fe3c0c640730e790122dc`); [SHA256 manifest](https://drive.google.com/file/d/1RXlB3tdlTR-eezTLuNUMG5mFexTUH0fE/view?usp=drivesdk); [release notes](https://docs.google.com/document/d/181ZAhH0NEj4K5sjY2mu13sTROwtjgz0v/edit?usp=drivesdk&ouid=102665342940633303649&rtpof=true&sd=true).
- ZIP install root/CRC PASS. Drive metadata confirms name, exact size, Releases parent and private owner-only sharing; remote SHA256 NOT VERIFIED.
- `0.2.1-dev` package: [TESTING ZIP](https://drive.google.com/file/d/1MzGMTIt_9NHF30p4SpHoogGXsMCWacwC/view?usp=drivesdk) (212,943 bytes, SHA256 `0b273327a7f5c76b13c0c6aeff7e620c88b3a5d6858c1a2956f51c1fa2070692`); [SHA256 manifest](https://drive.google.com/file/d/1CCMFMWdIIrEjH3g7zoX2aEKphLo0GjJO/view?usp=drivesdk); [release notes](https://docs.google.com/document/d/1s8hOgOPE3xKh0okzCsbd3VwWBvmVxUYq/edit?usp=drivesdk&ouid=102665342940633303649&rtpof=true&sd=true).
- ZIP root/CRC and filename checks PASS. Drive metadata confirms filename, 212,943-byte size, expected Releases parent and private owner-only sharing; remote SHA256 metadata unavailable (NOT VERIFIED).
- Browser, WordPress REST/admin/storage, Openverse live search and raster output: NOT TESTED. Previously unavailable runners were not retried.

## Known issues and next tasks

- Implement/test PNG raster production output when a supported server raster engine is available; current server master is SVG only.
- Studio source requires product ID, `front`/`back` geometry and a Studio-authorized private master reference. The Maker's single canvas/SVG master has no proven mapping; do not send guessed requests or change SANRO Studio.
- Only Anton is bundled. More Google Font binaries can be added from their official repository with licenses.
- Superseded `0.2.0-dev` installer: 211,528 bytes, SHA256 `1f9cee487fbcadb69c09f667bdcafea3e3e9cd04d9114688c522467362506963`; replaced due independent QA P0 failures.
- No installer or feature is FINAL RELEASE.

## Authorization

The Owner authorized commits to `develop`, private Drive delivery, and Design Maker installation, activation and synthetic guest QA on the named Hostinger staging site for this cycle. Merge, production, Studio changes, worker delegation and global VPS changes remain outside this mandate.
