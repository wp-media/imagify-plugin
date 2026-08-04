# Imagify – AI Coding & Architecture Guidelines

This file defines NON-NEGOTIABLE rules for any AI-assisted work
(Claude Code, ChatGPT, JetBrains AI Assistant, Cursor, etc.)
in this repository.

Skills define behavioral guidance.
AGENTS.md defines mandatory guardrails.
If a conflict exists, AGENTS.md prevails.

The objective is to keep Imagify:

- WordPress.org compliant
- Architecturally consistent
- Secure
- Maintainable
- Review-friendly

This document applies to ALL automated or AI-generated changes.

Agents and orchestration come from whichever delivery pipeline is installed, not from this
repository. Everything project-specific it needs is here — never guess a command, path, or
convention this file documents.

Two project-local skills remain under `.claude/skills/` because no installed pipeline covers them:
`e2e` (two-tier browser test execution) and `compliance` (WordPress.org and PHPCS standards). Both
are standalone and read their values from this file. Retire them once the pipeline provides
equivalents.

---

## Project Configuration

Canonical runtime values for any AI delivery pipeline operating on this repository.
Read them from here. Do not invent, re-derive, or assume them.

| Variable | Value |
|---|---|
| `REPO` | `wp-media/imagify-plugin` |
| `SLUG` | `imagify` |
| `TEXT_DOMAIN` | `imagify` |
| `BASE_BRANCH` | `develop` (fork point checked against `origin/develop`) |
| Protected branches | `develop`, `master` — never commit directly |
| Branch naming | `<prefix>/<issue-number>-<slug>`, prefix is `fix`, `enhancement`, or `test` |
| Test (full) | `composer run-tests` |
| Test (unit) | `composer test-unit` |
| Test (integration) | `composer test-integration` |
| Test (single group) | `vendor/bin/phpunit --configuration Tests/Integration/phpunit.xml.dist --group <Name>` |
| Lint | `composer phpcs` |
| Lint (changed files only, fast path) | `composer phpcs-changed` |
| Lint auto-fix | `composer phpcbf` |
| Static analysis | `composer run-stan` |
| Code coverage | `composer code-coverage` |
| Build (frontend) | `npm run build` |
| Boot local environment | `bash bin/dev-up.sh` |
| Stop local environment | `bash bin/dev-down.sh` (add `--clean` for a full wipe) |
| Seed local environment | `bash bin/dev-seed.sh` |
| E2E runner | `bash bin/test-e2e.sh` |
| Local URL | `http://localhost:8888` |
| Admin URL | `http://localhost:8888/wp-admin` — `admin` / `password` |
| Settings page | `http://localhost:8888/wp-admin/options-general.php?page=imagify` |
| PR template | `.github/PULL_REQUEST_TEMPLATE.md` |
| PR label | `Made by AI` on every AI-authored PR |
| E2E specs | committed permanently to `Tests/e2e/specs/` — never author-then-delete |
| Knowledge graph | `.aiassistant/graph/dependency-graph.json` (gitignored, build locally) |
| Knowledge graph build | `node bin/build-knowledge-graph.js` (`--full` to force, `--dry-run` for stats) |

**Run `composer install` before any test or lint command** — Strauss prefixing is a post-install
hook, and without it PHPCS/PHPUnit fail to resolve `Imagify\Dependencies\*` with misleading
autoload errors. See 4.1 for how these commands are discovered.

---

# 1. Project Overview

Imagify is a single-edition WordPress plugin for image optimization.

- **Repo:** `wp-media/imagify-plugin`
- **Plugin slug:** `imagify`
- **PHP namespace root:** `Imagify\`
- **PSR-4 root:** `classes/`

There is no FREE/PRO split. The codebase has two layers:

- `classes/` — modern PSR-4 code, namespace `Imagify\`, `declare(strict_types=1)` required. **New features go here.**
- `inc/classes/` — legacy classmap code, `Imagify_` prefix. **Do not add new classes here; migrate out instead.**

When modifying architecture:
- Prefer the modern `classes/` layer for all new work.
- Follow service provider + subscriber pattern for wiring.

---

# 2. Technology Stack

- PHP 7.3+ (strict types, PSR-4 autoloading via Composer)
- WordPress plugin APIs (hooks, options, WP-CLI, AJAX)
- League Container (DI container + service providers + event subscribers).
  Strauss-prefixed FQN: `Imagify\Dependencies\League\Container\Container`
- ActionScheduler (async background jobs)
- Strauss (Composer dependency namespace prefixing to `Imagify\Dependencies\`)
- JavaScript / Grunt (`_dev/` pipeline to `assets/`, config in `gruntfile.js`).
  `_dev/` additionally carries its own `bud.config.js` and `package.json` — check which one a task targets.
- Playwright + TypeScript (E2E testing under `Tests/e2e/`)

**No JavaScript unit test runner exists** (no Jest, no Vitest) — never invent `npm test`. Frontend
verification is `npm run build` plus Playwright; otherwise report JS tests as `N/A`.

Key WordPress option: `imagify_settings` (API key and plugin configuration).

---

# 3. Code Structure

```
classes/          New PHP code (PSR-4, Imagify\ namespace)
inc/              Legacy PHP includes (procedural, no namespace)
inc/classes/      Legacy class files migrating toward classes/
assets/           Compiled frontend assets (do not edit directly)
_dev/             Frontend source (JS, SCSS, Grunt config)
views/            PHP view templates
Tests/            PHPUnit tests
Tests/e2e/        Playwright E2E tests (TypeScript)
bin/              CLI scripts (dev-up, dev-down, dev-seed, test-e2e, build-knowledge-graph)
docs/             Documentation (E2E_TESTING.md, etc.)
.github/          CI workflows and the pull request template
.claude/skills/   Project-local skills not covered by the installed pipeline (e2e, compliance)
```

`assets/` is build output — edit `_dev/` and rebuild, or the next build discards the work.

---

# 4. Coding Standards & Static Analysis

Source of truth:

- Composer scripts (`composer.json`)
- PHPCS ruleset (`phpcs.xml`)
- PHPStan config (`phpstan.neon.dist`)
- WordPress Plugin Check: https://github.com/WordPress/plugin-check/
- CI pipeline rules

Imagify must remain compatible with WordPress.org validation rules.

Any change affecting public APIs, output, security, metadata, or
plugin bootstrap behavior must be evaluated against WordPress Plugin Check expectations.

AI MUST:

- Read `composer.json` first and use the defined scripts (e.g. `phpcs`, `phpcbf`, `run-stan`, `test-unit`, `test-integration`) instead of inventing commands.
- Auto-discover PHPCS configuration and follow it as the single source of truth.

`WordPress.Security.NonceVerification.Missing` and `.Recommended` are deliberately suppressed in
`phpcs.xml` — do not add `phpcs:ignore` for them, and do not "fix" code to satisfy them.

Use `composer phpcs-changed` while implementing; full `composer phpcs` before opening a PR.

Text domain is `imagify`: `esc_html__( 'Label', 'imagify' )`.

No jQuery in new or modified JavaScript — native DOM APIs only, no inline handlers, no unsafe
`innerHTML`. Pass nonces via `wp_localize_script`.

## 4.1 Tooling Auto-Discovery (MANDATORY)

Before making changes that affect standards or formatting, the agent MUST locate and respect the repository configuration files.

### Required reads (in this order)
1. `composer.json` — use scripts defined in `"scripts"` whenever possible; prefer the exact commands used by CI; do not invent lint/test commands.
2. PHPCS ruleset (first match wins): `phpcs.xml`, `phpcs.xml.dist`
3. Static analysis configs (if present): `phpstan.neon.dist`

### Execution rules
- Do NOT hardcode PHPCS standards.
- Do NOT assume WordPress-Core or WordPress-Extra unless defined in the ruleset.

If no PHPCS configuration exists, stop and ask.

---

# 5. Architectural Integrity

AI must NOT:

- Introduce global state.
- Add new singletons or `InstanceGetterTrait` usage in `classes/`.
- Bypass dependency injection patterns used in the project.
- Couple UI logic to infrastructure logic.
- Add new classes to `inc/classes/`.
- Call bare `add_action()` / `add_filter()` in new code under `classes/`.

Follow existing patterns:

- Service providers (`classes/*/ServiceProvider.php`), registered in `config/providers.php`
- Subscribers (`classes/*/Subscriber.php` implementing `SubscriberInterface`), listed in
  `ServiceProvider::get_subscribers()`
- Container-based wiring (`config/providers.php`)
- Strict types in all new `classes/` files

Nonce action naming convention: `imagify_<feature>_<action>`.

PHP-rendered admin UI (`wp_admin_notice()`, `add_action( 'admin_notices', ... )`,
`add_settings_error()`) counts as a frontend change despite living in PHP — scope and test it as one.

---

# 6. Testing & Validation

For every change:

1. Ensure no new PHPCS violations.
2. Ensure static analysis still passes.
3. Avoid altering unrelated test behavior.
4. Do not delete tests unless clearly obsolete.

If modifying templates:
- Validate escaping correctness.
- Ensure no functional regressions.

## 6.1 Test strategy

Prefer **integration tests** (`Tests/Integration/`, annotated `@group FeatureName`) — they exercise
real WordPress context, DI wiring, and hook execution. Unit tests only for pure logic with no WP
globals, container, or hooks.

Scale scope to risk: LOW = the relevant `--group`; MEDIUM = `composer test-unit` plus that group;
HIGH = `composer run-tests`. To run one group directly, bypassing the exclude list baked into
`composer test-integration`:

```bash
vendor/bin/phpunit --configuration Tests/Integration/phpunit.xml.dist --group FeatureName
```

## 6.2 Definition of Done — file scope

Not scope violations: generated files (`*.min.js`, `*.min.css`), lockfiles, tests mirroring changed
source, auto-formatter output.

---

# 7. E2E Testing

Full E2E testing documentation: [`docs/E2E_TESTING.md`](docs/E2E_TESTING.md)

The test directory is `Tests/e2e/` (capital T, consistent with the existing `Tests/` PHPUnit directory).

The E2E suite runs in CI via `.github/workflows/e2e.yml`. The `IMAGIFY_TESTS_API_KEY` GitHub secret must be configured for optimization tests to run.

## 7.1 Admin routes

| Area | URL |
|---|---|
| Settings | `/wp-admin/options-general.php?page=imagify` |
| Bulk optimization | `/wp-admin/upload.php?page=imagify-bulk-optimization` |
| Custom folders (Files) | `/wp-admin/upload.php?page=imagify-files` |
| Media library (list mode) | `/wp-admin/upload.php?mode=list` |

Frequently used selectors: `#imagify-api-key`, `[name="imagify_settings[api_key]"]`,
`th.column-imagify`.

Plugin activation check: `npx @wordpress/env run cli wp plugin list --name=imagify`

## 7.2 Test architecture

`Tests/e2e/` holds `playwright.config.ts`, `specs/`, `fixtures/`, and `pages/`. Reuse the page
objects rather than duplicating selectors: `settings.ts` → `SettingsPage`, `bulk-optimization.ts`
→ `BulkOptimizationPage`, `media-library.ts` → `MediaLibraryPage`.

## 7.3 Conventions

- No `setTimeout` / `waitForTimeout` — web-first assertions with explicit timeouts.
- Gate specs needing a live key: `test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, '...' );`
- Specs authored during QA are committed permanently. Never write one then delete it.
- Screenshot evidence: commit `.e2e-screenshots/` (gitignored) to the branch, push, then untrack in
  a follow-up commit. The SHA-based URL resolves permanently:
  `https://raw.githubusercontent.com/wp-media/imagify-plugin/<SHA>/.e2e-screenshots/<filename>`

## 7.4 License, quota, and API guards

Optimization behavior is gated behind license, API-key, and quota checks. Before claiming a feature
works or is broken, check whether one of these short-circuited the tested path.

| Guard | Function | Location |
|---|---|---|
| API reachable | `Imagify_Requirements::is_api_up()` | `inc/classes/class-imagify-requirements.php:225` |
| API key valid | `Imagify_Requirements::is_api_key_valid()` | `inc/classes/class-imagify-requirements.php:258` |
| Over quota | `Imagify_Requirements::is_over_quota()` | `inc/classes/class-imagify-requirements.php:299` |
| API key valid (wrapper) | `imagify_is_api_key_valid()` | `inc/functions/api.php:340` |
| API key valid (deprecated) | `imagify_valid_key()` | `inc/deprecated/deprecated.php:206` |

**Check the precondition first.** `bin/dev-seed.sh` seeds `IMAGIFY_TESTS_API_KEY` into the
`imagify_settings` option. A result of `1` means the key is present and the API-key guards are not
blockers:

```bash
npx @wordpress/env run cli wp option get imagify_settings --format=json | grep -c '"api_key":"[^"]\+'
```

**Reporting rule.** If a guard on the tested path evaluates false locally, report *cannot verify*
citing its `file:line`. Never claim a behavioral pass through a blocked guard, and never report a
failure for a feature that was merely gated. Structural claims (file exists, hook registered) stay
verifiable.

---

# 8. Local Development

```bash
# Start the local WordPress environment (Docker via wp-env)
bash bin/dev-up.sh

# Stop (preserves data) / full wipe
bash bin/dev-down.sh
bash bin/dev-down.sh --clean

# Seed test data (idempotent)
bash bin/dev-seed.sh

# Run E2E tests locally (sources .env.local for API key automatically)
bash bin/test-e2e.sh
bash bin/test-e2e.sh --headed     # watch the browser
bash bin/test-e2e.sh --ui         # Playwright interactive UI
bash bin/test-e2e.sh specs/smoke  # single spec
```

Create `.env.local` at the repo root (gitignored) with:
```
IMAGIFY_TESTS_API_KEY=your-key-here
```

- Site: `http://localhost:8888`
- Admin: `http://localhost:8888/wp-admin` — `admin` / `password`

`bin/dev-up.sh` accepts `--no-seed` (boot without seeding) and `--reset` (destroy the existing
environment first). It is idempotent and safe to re-run.

---

# 9. AI Working Protocol

AI must work in small, incremental changes.

After each logical change set:
- explain what changed
- explain why
- list potential edge cases

AI must NOT:

- Perform massive automated refactors without approval.
- Reorganize files without explicit instruction.
- Rewrite entire classes when a minimal fix is sufficient.

## 9.1 Git Commit & Push Policy

By default, AI may only **suggest** commit messages and must not run `git commit` or `git push`.

**Exception — delivery pipeline:** when operating under an installed issue-delivery pipeline
working on a specific ticket, the agent MAY:

1. Run atomic `git commit` calls — one commit per logical, self-contained change set.
2. Run `git push` exactly once after all commits are ready, to publish the branch.
3. Create a GitHub Pull Request using the prepared PR draft.
4. Monitor PR CI status checks until all pass or a failure is detected.

Atomic commit rules:
- Each commit must pass PHPCS and static analysis before being committed.
- Commit message format: `type(scope): short description` (Conventional Commits).
- Every pipeline-authored commit carries a `Co-Authored-By: <model> <noreply@anthropic.com>`
  trailer, so automated work stays auditable. Commits authored by a human do not.
- Do not squash unrelated changes into a single commit.
- Do not amend commits that have already been pushed.

---

# 10. PR Hygiene

Changes must:

- Be minimal and scoped.
- Have clear intent.
- Avoid noise in diff.
- Avoid unrelated formatting changes.

Every PR body follows `.github/PULL_REQUEST_TEMPLATE.md`, with every section filled in and any
unticked mandatory checklist item justified.

Conventions for pipeline-authored PRs:

- PR title: `Closes #<N>: <short descriptive title>`. Never use a Conventional-Commit prefix in a
  PR title — that format is for commit messages.
- The body must contain a standalone `Closes #<N>` line, not the reference buried in prose. This is
  what GitHub uses to link and auto-close the issue.
- The PR number is never the issue number. Read it back from the `gh pr create` output.
- Apply the `Made by AI` label to every PR and issue an automated pipeline creates.

When work must be split across PRs, split vertically — each PR one complete behavior including its
tests. Never "all backend in PR 1, all frontend in PR 2".

---

# 11. Security First

Always assume:

- User input is untrusted.
- Remote API responses are untrusted.
- Stored values may be tampered with.

Never:

- Store sensitive values in plain text without review.
- Introduce unsafe serialization.
- Echo unescaped dynamic data.

Escape at output, matching the context: `esc_html()`, `esc_attr()`, `esc_url()`,
`wp_kses_post()`.

Authorization: use the project's own registered capability in `current_user_can()` checks for
plugin-specific actions, rather than falling back to the coarse `manage_options`.

---

# 12. When in Doubt

Stop.
Explain the ambiguity.
Ask for clarification.

Architectural integrity is more important than speed.

---

# 13. Knowledge Graph

A dependency graph can be built at `.aiassistant/graph/dependency-graph.json` (gitignored — each
environment builds its own).

Before exploring the codebase structure (finding a class, tracing dependencies, exploring
namespaces), **read this file first**. It contains:
- `nodes`: per-file namespace, declared symbols, and imports.
- `symbol_index`: maps every fully-qualified PHP class/interface/trait/enum to its file.

Build or refresh it with `node bin/build-knowledge-graph.js` (`--full` to force a full rebuild,
`--dry-run` to print stats without writing). The graph records the commit it was built from and
rebuilds incrementally; refresh it after structural changes.

If the graph is absent and cannot be built, fall back to `grep` and `glob` rather than stopping.

---

# 14. Session Learnings

Lessons learned from past pipeline runs. Worth injecting into agent dispatches.

### E2E — Screenshots must show the target element

**Rule:** Always call `locator.scrollIntoViewIfNeeded()` before `page.screenshot()`. Use the shared helper `screenshotElement()` from `Tests/e2e/fixtures/screenshot.ts` instead of calling `page.screenshot()` directly.

**Why:** A screenshot taken at page-load position captures the top of the page, not the feature being tested. Screenshots showing unrelated content (e.g. General Settings header instead of the target button) provide zero QA evidence and mislead reviewers.

**How to apply:** In every Playwright spec, import `screenshotElement` from `../fixtures/screenshot` and pass the target locator as the third argument. Verify each screenshot visually shows the element it documents before committing.

### E2E — Never use `test.skip()` as a fallback for missing seed data

**Rule:** If a required UI element is not visible due to missing seed data, use a hard `expect(...).toBe(true)` assertion, not `test.skip()`.

**Why:** `test.skip()` causes the test suite to report green with zero assertions — CI passes but nothing is tested. A hard fail surfaces the missing seed as a real problem.

**How to apply:** Check element visibility after navigation. If not visible, fail with a descriptive message that includes the seed command needed to fix the environment.

---

# 15. Repository Specs

The repository may define task-specific implementation specs for recurring technical problems
(e.g. PHPCS warnings, architecture migrations, WordPress compliance patterns).

When a relevant spec exists, agents must follow it in addition to AGENTS.md and the applicable
skills.

---

# AI Task Priority

When executing tasks, agents must prioritize:

1. Security
2. WordPress.org compliance
3. Architectural integrity
4. Backward compatibility
5. Minimal diffs
6. Performance

AGENTS.md remains the final authority.
