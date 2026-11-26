# SANRO Design Maker

Guest-first editor for kaoscetak.id. Add shortcode `[sanro_design_maker]` to the Design Maker page. Development build; browser and WordPress runtime acceptance are still pending.

## Editor and account features

- Fabric.js 7.4.0 is bundled locally from upstream release tag `v740`; the 1000 × 1000 canvas is transparent.
- Text, editable shapes, six original starter templates, PNG/JPEG/WebP upload (1.5 MiB client limit), transforms, layers, undo/redo and IndexedDB guest draft recovery.
- Signed-in WordPress users can save, reopen/edit, duplicate and delete designs. Share links are read-only bearer links, expire after 7 days, and can be revoked by the owner. Shared media is limited to assets referenced by that design.
- Preview download is a 480 × 480 JPEG with the configured watermark. No transparent HD download is exposed.
- Flat-color background removal runs locally on the device; it is not an AI cutout service and works best with a uniform edge-connected background.

## Assets, fonts and admin

- Openverse search uses its official API with commercial-use filtering. Only CC0, public-domain mark, CC BY and CC BY-SA results are eligible. Source, license and attribution are retained with imported private assets. Pixabay is disabled because its per-item merchandise terms are not verified by this plugin.
- Server-side media fetches are HTTPS-only, DNS/IP screened, redirect-disabled, size/MIME/dimension limited, and use only a cached official Openverse result ID.
- Anton is bundled from Google Fonts with its SIL Open Font License. Additional approved Google Fonts can be uploaded as WOFF2 by an administrator; font faces are local and `font-display: swap` loads them when used.
- WordPress-native SANRO Design Maker admin pages cover Dashboard, Assets, Templates, Fonts, Provider API, Watermark, Storage & Quota, Studio Bridge and Diagnostics.

## Private storage and rendering

Private customer assets and designs are user-scoped. Private storage fails closed unless the directory resolves outside both WordPress and document roots. The editor validates image MIME, dimensions, quotas and design references. Owner share routes expose only explicitly shared referenced assets and can be revoked.

The server can create a 4000 × 4000 resolution-independent SVG master and store it outside the web root with owner checks, a SHA256 record and restrictive permissions. No PNG rasterization engine is bundled or available in the development environment, so raster production render and Studio consumption are not implemented. The SVG master has no public download route.

## Studio status

No Studio endpoint is called by this plugin yet. The inspected Studio source requires a product ID, product-specific front/back geometry, an owner token and private master references. This editor's current single-canvas data has no verified mapping to that contract; do not infer staging availability from source inspection.

## Third-party licenses

- `assets/vendor/fabric/LICENSE`: Fabric.js MIT.
- `assets/fonts/Anton-OFL.txt`: SIL Open Font License 1.1 for the bundled Google Font Anton (`assets/fonts/Anton-Regular.ttf`). Source file: https://github.com/google/fonts/tree/main/ofl/anton.

## Tests

Run `php tests/php-smoke.php`, `php tests/storage-contract.php`, `php tests/provider-contract.php`, `php tests/render-contract.php` and `node tests/remove-background.test.cjs`. Browser and WordPress REST integration tests are not part of the local evidence yet.

## Install

Zip the `sanro-design-maker` directory as the plugin root and install it through WordPress. Add `[sanro_design_maker]` to the Design Maker page. Development setup does not require a build step; Openverse and admin font imports make their documented requests only when used.
