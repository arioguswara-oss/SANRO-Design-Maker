# Vendored dependency: Fabric.js

- Version: 7.4.0 (stable upstream release tag `v740`).
- Source: `https://github.com/fabricjs/fabric.js/tree/v740`.
- Distribution: `sanro-design-maker/assets/vendor/fabric/index.min.js` from upstream `dist/index.min.js`.
- License: MIT; verbatim upstream license is adjacent at `sanro-design-maker/assets/vendor/fabric/LICENSE`.
- Bundle size: 299,022 bytes.
- SHA256: `dc3a9e32423eb9c23299631646da4f9bee818b05e6a93c2f8670fdea76113e8c`.
- Loading: WordPress enqueues the local bundle before `assets/editor.js`; the editor makes no CDN request.

Validation: `node --check` passes for the bundled distribution. No production page or staging site was modified.
