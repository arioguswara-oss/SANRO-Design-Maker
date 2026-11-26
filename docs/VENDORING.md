# Vendored dependency: Fabric.js

- Version: 7.4.0 (stable upstream release tag `v740`).
- Source: `https://github.com/fabricjs/fabric.js/tree/v740`.
- Distribution: `sanro-design-maker/assets/vendor/fabric/index.min.js` from upstream `dist/index.min.js`.
- License: MIT; verbatim upstream license is adjacent at `sanro-design-maker/assets/vendor/fabric/LICENSE`.
- Bundle size: 299,021 bytes.
- SHA256: `94870a192fae79168d63c74371fc9735982ce36b15824184860ebbe7d514b1ba`.
- Loading: WordPress enqueues the local bundle before `assets/editor.js`; the editor makes no CDN request.

Validation: `node --check` passes for the bundled distribution. No production page or staging site was modified.
