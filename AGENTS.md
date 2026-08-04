# Imagify – AI Coding & Architecture Guidelines

NON-NEGOTIABLE rules for any AI-assisted work in this repository.
Skills give behavioral guidance; AGENTS.md gives mandatory guardrails. On conflict, AGENTS.md wins.

Agents and orchestration come from whichever delivery pipeline is installed, not from this repo.
Everything project-specific it needs is here — never guess a command, path, or convention documented
below. Deeper reference lives on demand: `docs/E2E_TESTING.md` for browser testing, the `e2e` skill
for QA procedure, `.aiassistant/graph/` for code structure.

---

## Project Configuration

| Variable | Value |
|---|---|
| `REPO` | `wp-media/imagify-plugin` |
| `SLUG` / `TEXT_DOMAIN` | `imagify` |
| `BASE_BRANCH` | `develop` (protected, with `master` — never commit directly) |
| Branch naming | `<prefix>/<issue>-<slug>`, prefix `fix` \| `enhancement` \| `test` |
| Test (full / unit / integration) | `composer run-tests` / `composer test-unit` / `composer test-integration` |
| Test (one group) | `vendor/bin/phpunit --configuration Tests/Integration/phpunit.xml.dist --group <Name>` |
| Lint / changed-only / auto-fix | `composer phpcs` / `composer phpcs-changed` / `composer phpcbf` |
| Static analysis | `composer run-stan` |
| Build frontend | `npm run build` |
| Local env up / down / seed | `bash bin/dev-up.sh` / `bash bin/dev-down.sh` / `bash bin/dev-seed.sh` |
| E2E runner | `bash bin/test-e2e.sh` |
| Local URL | `http://localhost:8888` — admin `admin` / `password` |
| Settings page | `/wp-admin/options-general.php?page=imagify` |
| Key option | `imagify_settings` (API key + config) |
| PR template | `.github/PULL_REQUEST_TEMPLATE.md` |
| PR label | `Made by AI` on every AI-authored PR |
| Knowledge graph | `.aiassistant/graph/dependency-graph.json` (gitignored) — build: `node bin/build-knowledge-graph.js` |

**Run `composer install` before any test or lint command** — Strauss prefixing is a post-install
hook, and without it PHPCS/PHPUnit fail to resolve `Imagify\Dependencies\*` with misleading autoload
errors.

---

# 1. Project Overview

Single-edition WordPress image-optimization plugin. No FREE/PRO split. Namespace root `Imagify\`,
PSR-4 root `classes/`. Two layers:

- `classes/` — modern PSR-4, `declare(strict_types=1)` required. **New features go here.**
- `inc/classes/` — legacy classmap, `Imagify_` prefix. **Do not add classes here; migrate out.**

---

# 2. Technology Stack

- PHP 7.3+ (strict types, PSR-4 via Composer)
- WordPress plugin APIs (hooks, options, WP-CLI, AJAX)
- League Container (DI + service providers + subscribers), Strauss-prefixed:
  `Imagify\Dependencies\League\Container\Container`
- ActionScheduler (async jobs)
- Strauss (dependency namespace prefixing to `Imagify\Dependencies\`)
- JavaScript / Grunt (`_dev/` to `assets/`, `gruntfile.js`; `_dev/` also has its own `bud.config.js`)
- Playwright + TypeScript (E2E under `Tests/e2e/`)

**No JavaScript unit test runner exists** (no Jest/Vitest) — never invent `npm test`. Frontend
verification is `npm run build` plus Playwright; otherwise report JS tests as `N/A`.

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
.claude/skills/   Project-local skills not covered by the installed pipeline (e2e)
```

`assets/` is build output — edit `_dev/` and rebuild, or the next build discards the work.

---

# 4. Coding Standards & Static Analysis

Source of truth: `composer.json` scripts, `phpcs.xml`, `phpstan.neon.dist`, CI, and
[WordPress Plugin Check](https://github.com/WordPress/plugin-check/). Imagify must stay compatible
with WordPress.org validation rules; any change to public APIs, output, security, metadata, or
bootstrap behavior must be evaluated against Plugin Check expectations.

- Use the commands in Project Configuration. Do not invent lint/test commands or hardcode PHPCS
  standards — the ruleset is the single source of truth. If no PHPCS config exists, stop and ask.
- `WordPress.Security.NonceVerification.Missing` and `.Recommended` are deliberately suppressed in
  `phpcs.xml` — do not add `phpcs:ignore` for them, and do not restructure code to satisfy them.
- Text domain is `imagify`: `esc_html__( 'Label', 'imagify' )`.
- No jQuery in new or modified JavaScript — native DOM APIs only, no inline handlers, no unsafe
  `innerHTML`. Pass nonces via `wp_localize_script`.

---

# 5. Architectural Integrity

AI must NOT:

- Introduce global state.
- Add new singletons or `InstanceGetterTrait` usage in `classes/`.
- Bypass dependency injection patterns used in the project.
- Couple UI logic to infrastructure logic.
- Add new classes to `inc/classes/`.
- Call bare `add_action()` / `add_filter()` in new code under `classes/`.

Follow existing patterns: service providers (`classes/*/ServiceProvider.php`) registered in
`config/providers.php`; subscribers implementing `SubscriberInterface` and listed in
`ServiceProvider::get_subscribers()`; strict types in all new `classes/` files.

Nonce action naming: `imagify_<feature>_<action>`.

PHP-rendered admin UI (`wp_admin_notice()`, `add_action( 'admin_notices', ... )`,
`add_settings_error()`) counts as a frontend change despite living in PHP — scope and test it as one.

---

# 6. Testing & Validation

For every change: no new PHPCS violations, static analysis still passes, unrelated test behavior
unchanged, no tests deleted unless clearly obsolete. When modifying templates, validate escaping.

Prefer **integration tests** (`Tests/Integration/`, annotated `@group FeatureName`) — they exercise
real WordPress context, DI wiring, and hook execution. Unit tests only for pure logic with no WP
globals, container, or hooks.

Scale scope to risk: LOW = the relevant `--group`; MEDIUM = `composer test-unit` plus that group;
HIGH = `composer run-tests`.

Not scope violations: generated files (`*.min.js`, `*.min.css`), lockfiles, tests mirroring changed
source, auto-formatter output.

---

# 7. E2E Testing

Test directory `Tests/e2e/`. Runs in CI via `.github/workflows/e2e.yml`; the
`IMAGIFY_TESTS_API_KEY` GitHub secret must be set for optimization tests.

- **Admin URLs, page objects, environment variables, local running:**
  [`docs/E2E_TESTING.md`](docs/E2E_TESTING.md)
- **Tiers, spec conventions, screenshot evidence, and the license/quota guard rules:** the `e2e`
  skill (`.claude/skills/e2e/`)

Optimization behavior sits behind license, API-key, and quota guards. **Never report a behavioral
pass or failure through a blocked guard** — report *cannot verify* with the guard's `file:line`. The
guard table and the precondition check are in the `e2e` skill.

---

# 8. Local Development

Commands are in Project Configuration. `bin/dev-up.sh` is idempotent and accepts `--no-seed` and
`--reset`; `bin/dev-down.sh --clean` wipes. `bin/test-e2e.sh` takes `--headed`, `--ui`, or a spec
path, and sources `.env.local` automatically.

Create `.env.local` at the repo root (gitignored):
```
IMAGIFY_TESTS_API_KEY=your-key-here
```

---

# 9. AI Working Protocol

Work in small, incremental changes. After each logical change set, explain what changed, why, and
the potential edge cases.

AI must NOT perform large automated refactors without approval, reorganize files without explicit
instruction, or rewrite entire classes when a minimal fix suffices.

## 9.1 Git Commit & Push Policy

By default AI may only **suggest** commit messages and must not run `git commit` or `git push`.

**Exception — delivery pipeline:** when working a specific ticket under an installed pipeline, the
agent may make atomic commits, push once when all commits are ready, open the PR, and monitor CI.

Atomic commit rules:
- Each commit must pass PHPCS and static analysis before being committed.
- Message format: `type(scope): short description` (Conventional Commits).
- Pipeline-authored commits carry a `Co-Authored-By: <model> <noreply@anthropic.com>` trailer so
  automated work stays auditable. Human-authored commits do not.
- Do not squash unrelated changes. Do not amend commits already pushed.

---

# 10. PR Hygiene

Changes must be minimal, scoped, clear in intent, and free of unrelated formatting noise.

Every PR body follows `.github/PULL_REQUEST_TEMPLATE.md`, every section filled in, any unticked
mandatory item justified.

- PR title: `Closes #<N>: <short title>` — never a Conventional-Commit prefix (that is for commits).
- The body needs a standalone `Closes #<N>` line, not buried in prose — that is what auto-closes.
- The PR number is never the issue number; read it from the `gh pr create` output.
- Apply the `Made by AI` label to every AI-created PR and issue.

Split work vertically — each PR one complete behavior including its tests. Never "all backend in
PR 1, all frontend in PR 2".

---

# 11. Security First

Assume user input, remote API responses, and stored values are all untrusted and possibly tampered
with. Never store sensitive values in plain text without review, introduce unsafe serialization, or
echo unescaped dynamic data.

Escape at output, matching context: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`.

Authorization: use the project's own registered capability in `current_user_can()` for
plugin-specific actions, not the coarse `manage_options`.

---

# 12. When in Doubt

Stop. Explain the ambiguity. Ask. Architectural integrity beats speed.

---

# 13. Knowledge Graph

Before exploring codebase structure (finding a class, tracing dependencies, exploring namespaces),
read `.aiassistant/graph/dependency-graph.json` first — `nodes` gives per-file namespace, symbols
and imports; `symbol_index` maps every fully-qualified class/interface/trait/enum to its file.

Gitignored, so build it: `node bin/build-knowledge-graph.js` (`--full` to force, `--dry-run` for
stats). It rebuilds incrementally from the commit it recorded. If it cannot be built, fall back to
grep/glob rather than stopping.

---

# AI Task Priority

1. Security
2. WordPress.org compliance
3. Architectural integrity
4. Backward compatibility
5. Minimal diffs
6. Performance

AGENTS.md remains the final authority.
