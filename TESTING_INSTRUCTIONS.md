# Testing Instructions — Issue #1180

Fix: IIS duplicate `<preConditions>` singleton collision in WebP/AVIF rewrite-rules classes.

## Requirements
- WordPress (any host; IIS/Windows required to reproduce the live 500, but the DOM-level behavior can be checked on any host by inspecting `web.config`).
- Imagify installed, replacing the 3 patched files (or the attached `imagify-fix-1180.zip`).

## Files changed
- `classes/WriteFile/AbstractIISDirConfFile.php` — strips a class-owned `<preCondition>` by name before re-adding, so WebP and AVIF share one `<preConditions>` container; drops now-empty containers.
- `classes/Webp/RewriteRules/IIS.php` — emits a bare `<preCondition name="IsWebp">` targeted at `…/outboundRules/preConditions` instead of a full wrapped `<preConditions>`.
- `classes/Avif/RewriteRules/IIS.php` — same for `IsAvif`.
- `Tests/Integration/classes/WriteFile/AbstractIISDirConfFile/RewriteRulesPreConditionsTest.php` — new integration tests.

## Install
1. If using the zip: extract `imagify-fix-1180.zip` into `wp-content/plugins/` and activate.
2. Otherwise copy the 3 changed class files over the existing plugin.

## Test steps (on an IIS site, or seed `web.config` manually)
1. Enable **Display next-gen format** for BOTH WebP and AVIF in Imagify settings.
2. Open the site root `web.config`.
3. Confirm exactly ONE `<preConditions>` collection under `system.webServer/rewrite/outboundRules`, containing both `<preCondition name="IsWebp">` and `<preCondition name="IsAvif">`.
4. Disable WebP only (keep AVIF): confirm `IsWebp` is removed, `IsAvif` survives, and the `<preConditions>` container still exists.
5. Disable AVIF only (re-enable WebP): confirm `IsAvif` is removed, `IsWebp` survives.
6. Disable both: confirm the `<preConditions>` container is removed entirely (no empty leftover).
7. Re-enable both: confirm a single `<preConditions>` with both entries again.

## Automated tests
```
composer run-tests
```
Integration suite runs `RewriteRulesPreConditionsTest` against a real `DOMDocument` + temp `web.config`.

## Expected result
No duplicate `<preConditions>` siblings, no missing `<preCondition>` after toggling one format, no HTTP 500 from IIS parsing the rewrite section.
