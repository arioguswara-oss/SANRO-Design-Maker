# SANRO Studio contract inspection (read-only)

Source inspected: `/home/sanro/Plugin-Kaoscetak`, branch `work/hermes-studio-restore-20261123`, source HEAD `9bd20d583c35db38b2120dc3c871bc8dda8b8d12`. No files in the Studio repository were changed.

## Source-defined interface

- Namespace: `sanro-studio/v1`.
- Draft create: `POST /wp-json/sanro-studio/v1/drafts`; request has integer `product_id` and validated `design`. Response contains a UUID `design_id` and 64-hex `owner_token`.
- Draft read: `GET /wp-json/sanro-studio/v1/drafts/{uuid}`; update: `POST` to that route with `design` and server-issued `expected_design_hash`.
- Owner-protected requests carry `X-Sanro-Owner-Token` and `X-Sanro-Draft-Nonce`; nonce action is `sanro_studio_draft`.
- Quote: `GET /wp-json/sanro-studio/v1/drafts/{uuid}/quote`.
- Cart handoff: `POST /wp-json/sanro-studio/v1/drafts/{uuid}/cart`, registered only when draft/cart feature flags and WooCommerce readiness allow it.
- Draft API registration requires `SANRO_STUDIO_ENABLE_DRAFT_API === true`. Private preview/master upload/read is a separate feature-flagged route family.
- Draft metadata rejects embedded image data and arbitrary image URLs. The private master pipeline binds image geometry to owner-authorized private master references.

## Current Maker boundary

The Design Maker now has its own private, owner-scoped SVG master generation route. That master identifier and storage contract are not proven compatible with Studio's private master references. The Maker canvas is single-sided and has no product ID; Studio validates product-specific front/back geometry. No mapping, endpoint, or staging flag has been invented, and no Studio route is called.

These are source findings only. Deployed staging plugin/feature flags remain NOT VERIFIED. The earlier WordPress MCP inventory ended with `Transport closed`. Staging and SANRO Studio were not changed.
