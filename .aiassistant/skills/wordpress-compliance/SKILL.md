---
name: wordpress-compliance
description: Use this skill when modifying templates, admin UI, output, hooks, plugin metadata, sanitization, escaping, or any code that must remain compliant with WordPress.org and repository PHPCS rules.
---

# WordPress Compliance

Ensure compatibility with:
- WordPress Plugin Check
- Repository PHPCS rules
- WordPress.org expectations

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

## Anti-patterns

- Echoing raw variables
- Introducing unescaped output
- Storing sensitive values in plain text
- Bypassing repository PHPCS configuration

## Related Specs

When relevant, consult repository specs under `.aiassistant/specs/`, especially:

- `.aiassistant/specs/phpcs/nonce-verification-recommended.md`
- `.aiassistant/specs/phpcs/validated-sanitized-input.md`
- `.aiassistant/specs/phpcs/escaped-output.md`

## Git Operations
Follow the policy defined in AGENTS.md §6.1. Outside the issue workflow, do not run `git commit` or `git push`.
