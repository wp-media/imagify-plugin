---
name: compliance
description: Check a change against WordPress.org plugin rules and PHPCS standards.
---

# WordPress Compliance

Ensure compatibility with:
- WordPress Plugin Check
- Repository PHPCS rules
- WordPress.org expectations

## Config loading

Read `AGENTS.md` → **Project Configuration** for project values. Do not expect them to be injected —
this skill is standalone and no orchestrator supplies them.

Text domain is `imagify`. For `current_user_can()` checks on plugin-specific actions, use the
project's own registered capability rather than the coarse `manage_options` (see AGENTS.md section 11).

## Responsibilities

- Respect repository PHPCS configuration.
- Follow WordPress escaping standards.
- Avoid forbidden or deprecated APIs.
- Avoid direct access to superglobals without sanitization.
- Ensure output is escaped for context.

## Escaping heuristics

HTML text: `esc_html()`
HTML attribute: `esc_attr()`
URL: `esc_url()`
Allowed HTML: `wp_kses_post()`

## Text domain

Use the `imagify` text domain for all translation calls:

```php
esc_html__( 'Clear Cache', 'imagify' )
esc_attr__( 'Plugin Settings', 'imagify' )
```

## Custom capabilities

If the project defines custom capabilities, always use those
(not `manage_options`) for capability checks. The PHPCS config should allow them
without warnings.

Example — use the project-registered capability, not manage_options directly:
```php
// Correct — use the project-registered capability, not manage_options directly
current_user_can( 'plugin_manage_options' )
```

Using `manage_options` directly for plugin-specific actions is incorrect and will flag
in code review unless the project's PHPCS config explicitly allows it.

## JavaScript

- Do not use jQuery. Use native DOM APIs (`document.querySelector`, `addEventListener`, `fetch`, etc.).
- jQuery is available in WordPress but its use introduces an unnecessary dependency and conflicts with modern bundling practices.

## Anti-patterns

- Echoing raw variables
- Introducing unescaped output
- Storing sensitive values in plain text
- Bypassing repository PHPCS configuration
- Using jQuery in new or modified JS code

## Suppressed sniffs

`WordPress.Security.NonceVerification.Missing` and `.Recommended` are deliberately suppressed in
`phpcs.xml`. Do not add `phpcs:ignore` for them, and do not restructure code to satisfy them.

## Git Operations
Follow the policy in AGENTS.md section 9.1. Outside a pipeline-driven ticket, do not run
`git commit` or `git push`.
