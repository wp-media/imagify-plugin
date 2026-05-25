---
name: imagify-frontend-architecture
description: Use this skill when changing admin JavaScript, CSS, or HTML templates in Imagify.
---

# Imagify — Frontend Architecture

Guidelines for JavaScript, CSS, and template changes in Imagify's admin UI.

## Core principles

- No jQuery — use native DOM APIs only.
- No inline event handlers (`onclick`, `onchange`, etc.) — use `addEventListener`.
- No unsafe `innerHTML` assignments — use `textContent` or `createElement`/`appendChild`.
- Remove event listeners when the component is torn down to avoid memory leaks.

## Admin UI areas

- **Settings page** — `assets/js/` and `assets/css/`.
- **Media library column** — JS responsible for the Imagify column must not interfere with core library behaviour.
- **Bulk optimization page** — uses AJAX; always show progress and handle errors visibly.

## AJAX / REST

- Use native `fetch` with the localized nonce.
- Always handle errors explicitly — show user-facing feedback, never fail silently.
- Nonces must be passed via `wp_localize_script` — never hardcoded.

## Structural expectations

- New JS entry points must be registered and enqueued through a Subscriber.
- No new global variables — use data attributes or module patterns to share state.
- E2E tests for new UI flows live in `tests/e2e/` — update them alongside the implementation.
