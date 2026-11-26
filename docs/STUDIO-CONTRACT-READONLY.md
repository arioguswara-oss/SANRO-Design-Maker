# SANRO Studio contract inspection (read-only)

Source inspected: `/home/sanro/Plugin-Kaoscetak`, branch `work/hermes-studio-restore-20261123`, source HEAD `9bd20d583c35db38b2120dc3c871bc8dda8b8d12`. The checkout had unrelated untracked `dist/` and `ops/hostinger/sanro-studio-pr50-staging-apply.sh` entries before this inspection; no files there were changed.

## Source-defined interface

- Namespace: `sanro-studio/v1`.
- Draft create: `POST /wp-json/sanro-studio/v1/drafts`; request has integer `product_id` and validated `design`. Response contains a UUID `design_id` and 64-hex `owner_token`.
- Draft read: `GET /wp-json/sanro-studio/v1/drafts/{uuid}`; update: `POST` to the same route with `design` and server-issued `expected_design_hash`.
- Owner-protected requests carry `X-Sanro-Owner-Token` and `X-Sanro-Draft-Nonce`. The nonce action is `sanro_studio_draft`; owner token is returned once at draft creation.
- Quote: `GET /wp-json/sanro-studio/v1/drafts/{uuid}/quote`.
- Cart handoff: `POST /wp-json/sanro-studio/v1/drafts/{uuid}/cart`, registered only when draft and cart/checkout feature flags and WooCommerce readiness allow it.
- Draft API registration itself requires `SANRO_STUDIO_ENABLE_DRAFT_API === true`. Private preview upload/read is a separate route and requires additional explicit preview flags.
- Draft metadata rejects embedded image data and arbitrary image URLs. The private master pipeline binds image geometry to owner-authorized private master references; master files are not public URLs.

## Integration constraints

Design Maker's current Fabric JSON is not yet the Studio draft schema: the editor has one canvas and no product selection, while Studio validates product-specific `front`/`back` geometry. Image masters must be uploaded and owner-bound through the separate private preview/master flow before image geometry can be accepted. The cart route cannot be assumed enabled.

These findings describe source code only. This branch was not proven to be the deployed staging version. A read-only WordPress MCP plugin inventory for staging ended with `Transport closed`; the earlier staging page response was HTTP 200, but plugin and feature-flag state remain unverified. No Studio endpoint was called and no staging or Studio state was changed.
