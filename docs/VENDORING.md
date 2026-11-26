# Vendored dependencies and assets

## Fabric.js

- Version: 7.4.0 (upstream release tag `v740`).
- Source: https://github.com/fabricjs/fabric.js/tree/v740.
- Distribution: `sanro-design-maker/assets/vendor/fabric/index.min.js` from upstream `dist/index.min.js`.
- License: MIT; verbatim license is adjacent at `sanro-design-maker/assets/vendor/fabric/LICENSE`.
- SHA256: `94870a192fae79168d63c74371fc9735982ce36b15824184860ebbe7d514b1ba`.
- Loading: local WordPress script dependency; no CDN request.

## Google Fonts Anton

- File: `sanro-design-maker/assets/fonts/Anton-Regular.ttf`.
- Source: https://github.com/google/fonts/blob/main/ofl/anton/Anton-Regular.ttf (Google Fonts blob `4d65707db9d8663ccfa99cd28cd3a4e0025be178`).
- License: SIL Open Font License 1.1; verbatim `OFL.txt` is bundled as `sanro-design-maker/assets/fonts/Anton-OFL.txt`.
- SHA256: `a4ba3a92350ebb031da0cb47630ac49eb265082ca1bc0450442f4a83ab947cab`.
- Loading: local `@font-face`, `font-display: swap`; the browser requests it only when selected. Private SVG rendering embeds the local font data when used.
