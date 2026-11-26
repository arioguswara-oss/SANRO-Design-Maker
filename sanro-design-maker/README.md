# SANRO Design Maker

Guest-first graphic editor for kaoscetak.id. The plugin currently provides the local editor foundation and transparent 1000 x 1000 canvas. Use shortcode `[sanro_design_maker]` on the Design Maker page.

## Current editor

- Fabric.js 7.4.0 is bundled locally from upstream `fabricjs/fabric.js` release tag `v740`. No CDN or paid service is used.
- Text, rectangle/circle/triangle/line shapes, six original editable starter templates (vintage, streetwear, adventure, cartoon, floral and typography), PNG/JPEG/WebP upload (1.5 MiB maximum), selection, move/resize/rotate, duplicate/delete, layer selection, fill/stroke/opacity, alignment and shadow controls.
- Undo/redo, keyboard shortcuts, guest draft recovery in IndexedDB (3 MiB serialized draft limit), mobile toolbar and responsive canvas.
- The print CTA links to `/custom/`. Permanent account saves, asset providers, background removal, server rendering, private master storage and Studio transfer are not implemented in this editor cycle.

## Third-party license

`assets/vendor/fabric/LICENSE` contains the upstream MIT license. The pinned distribution file is `assets/vendor/fabric/index.min.js`; upstream release: https://github.com/fabricjs/fabric.js/releases/tag/v740.

## Install

Zip the `sanro-design-maker` directory as the plugin root and upload it through WordPress. Add `[sanro_design_maker]` to the Design Maker page. No build step or external runtime request is required.
