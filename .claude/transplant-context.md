# Transplant Context: Imagify

**Target:** `/Users/gaelrobin/Local Sites/imagify/app/public/wp-content/plugins/imagify-plugin`
**Analysed:** 2026-06-10
**Analyst model:** Claude Opus 4.8

> Primary source of truth for customizations: `.aiassistant/transplant-preservation.md`.
> The project already contains an adapted workflow under `.aiassistant/` (skills, agents, specs,
> repo-map, issue-sync script, PR template). The writers must carry forward every item documented
> in the preservation file into the new `.claude/` output.

---

## 1. Project Identity

| Field | Value |
|---|---|
| Name | Imagify |
| Type | wp-plugin |
| Primary language | PHP (with TypeScript for E2E + JS/SCSS frontend source) |
| Framework | WordPress |
| Runtime / version | PHP >= 7.3 (CI matrix 8.0–8.4); WordPress (wp-env core: latest, phpVersion 8.2) |

---

## 2. Stack

### Backend

| Field | Value |
|---|---|
| Language | PHP |
| Framework | WordPress plugin |
| Test runner | PHPUnit (^8.5.52 \|\| ^9.6.33) |
| Test command | `composer run-tests` (= `composer test-unit` + `composer test-integration`) |
| Static analysis | PHPCS (WPCS ^3) + PHPStan (level 5, szepeviktor/phpstan-wordpress) |
| Lint command | `composer phpcs` (full) / `composer phpcs-changed` (./bin/phpcs-changed.sh) / `composer run-stan` |
| Package manager | composer (PHP) + npm (frontend/E2E) |
| Key config files | composer.json, phpcs.xml, phpstan.neon.dist, .wp-env.json, config/providers.php |

Notes:
- Unit suite config: `Tests/Unit/phpunit.xml.dist`; integration: `Tests/Integration/phpunit.xml.dist`.
- Strauss prefixes vendored deps into `Imagify\Dependencies\` (classmap prefix `Imagify_`). `composer install` runs `prefix-namespaces` automatically (post-install/post-update).
- DI: `league/container` (Strauss-prefixed to `Imagify\Dependencies\League\Container\Container`). ServiceProviders per module under `classes/*/ServiceProvider.php`, registered in `config/providers.php`. Hooks via `SubscriberInterface` listed in `ServiceProvider::get_subscribers()`.
- Dual-layer architecture (see Analyst Notes): modern `classes/` (PSR-4, `Imagify\`, `declare(strict_types=1)`) vs legacy `inc/classes/` (`Imagify_` prefix, do not extend).

### Frontend

| Field | Value |
|---|---|
| Present | yes |
| Framework | Vanilla JS + SCSS (jQuery-era admin scripts) |
| Build tool | Grunt (root `gruntfile.js`, `npm run build`); `_dev/` also has a `bud.config.js` (Bud/webpack) + own package.json |
| Asset source paths | `_dev/` (src), `_dev/src/` |
| Compiled asset paths | `assets/` (do not edit directly) |
| Test runner | N/A for frontend unit (no Jest/Vitest); behaviour covered by Playwright E2E |

---

## 3. Dev Environment

| Field | Value |
|---|---|
| Boot mechanism | wp-env (via project wrapper scripts in `bin/`) |
| Start command | `bash bin/dev-up.sh` (flags: `--no-seed`, `--reset`) |
| Stop command | `bash bin/dev-down.sh` (flag: `--clean` for full destroy) |
| Seeding | yes |
| Seed command | `bash bin/dev-seed.sh` (sets Imagify API key from IMAGIFY_TESTS_API_KEY, uploads a test JPEG) |
| Local URL | http://localhost:8888 |
| Admin / dashboard URL | http://localhost:8888/wp-admin (admin / password) |
| Temp root | .ai |
| Notes | wp-env tests instance on :8889 (used by PHPUnit). dev-up runs `composer install` + `@wordpress/env start` + activates `imagify` + seeds. .env.local (gitignored) supplies IMAGIFY_TESTS_API_KEY, auto-sourced by bin/test-e2e.sh. DISCREPANCY: interview temp_root=.ai is authoritative, but existing issue-sync.sh + .gitignore use `.TemporaryItems/Issues/imagify-plugin/...`. See Analyst Notes. |

---

## 4. Browser / E2E Testing

| Field | Value |
|---|---|
| Framework | Playwright (TypeScript) |
| Config path | Tests/e2e/playwright.config.ts |
| Base URL | http://localhost:8888 (env override: IMAGIFY_BASE_URL) |
| Test directory | Tests/e2e/specs/ (fixtures: Tests/e2e/fixtures/, page objects: Tests/e2e/pages/) |
| CI integration | yes (.github/workflows/e2e.yml) |

Run command: `bash bin/test-e2e.sh` (flags: `--headed`, `--ui`, or a spec pattern). Chromium-only, single worker, `fullyParallel:false`. Screenshots go to `.e2e-screenshots/` (gitignored). Page objects: SettingsPage, BulkOptimizationPage, MediaLibraryPage. API-key-gated tests use `test.skip(!process.env.IMAGIFY_TESTS_API_KEY, ...)`.

---

## 5. CI/CD

| Field | Value |
|---|---|
| Platform | GitHub Actions |
| Workflow files | .github/workflows/test.yml, test_legacy.yml, e2e.yml, lint_phpcs.yml, lint_phpstan.yml, code_coverage.yml, pr-template-checker.yml, assets-update.yml, deploy-tag.yml |
| Test job name | "Unit/Integration tests" (job `run`); steps: `composer test-unit`, `composer test-integration` |
| Lint job name | "PHPCS" (job `run`, `composer phpcs`); separate "PHPStan" workflow (lint_phpstan.yml) |
| Build job name | assets-update.yml / deploy-tag.yml (no dedicated PR build job) |

Notes: PR-template-checker uses `wp-media/pr-checklist-action`. test.yml installs Strauss before composer install and runs install-wp-tests.sh against MySQL. PR target branches: trunk, develop, branch-*, feature/*, enhancement/*, fix/*.

---

## 6. GitHub

| Field | Value |
|---|---|
| Repo | wp-media/imagify-plugin |
| Base branch | develop |
| PR template | `.aiassistant/skills/issue-workflow/refs/pr-template.md` (no `.github/PULL_REQUEST_TEMPLATE.md`; checker action enforces format) |

---

## 7. Source Structure

```
imagify.php / uninstall.php        # bootstrap + uninstall entrypoints
classes/                           # modern PSR-4 (Imagify\), strict_types=1 — new code here
inc/
  classes/                         # legacy Imagify_ classmap (do not extend)
  3rd-party/                       # PSR-4 integrations (WC, AS3CF, NGG, WP Rocket, ...)
  Dependencies/ActionScheduler/    # vendored (do not edit)
  functions/                       # legacy global helpers
  admin/  common/  deprecated/     # legacy admin / shared / deprecated
views/                             # PHP admin templates
_dev/                              # frontend source (JS/SCSS, Grunt + bud.config.js) -> assets/
assets/                            # compiled frontend (do not edit)
config/providers.php               # service provider registry
bin/                               # dev-up.sh, dev-down.sh, dev-seed.sh, test-e2e.sh, build-knowledge-graph.js, install-wp-tests.sh
Tests/
  Unit/  Integration/  Fixtures/  phpstan/   # PHPUnit
  e2e/                             # Playwright (specs/ fixtures/ pages/)
config/  vendor/  node_modules/
```

### Areas (for maestro.json)

| Path | Role | Notes |
|---|---|---|
| classes/ | source | Modern PSR-4 `Imagify\`, strict_types=1. New features go here. |
| inc/classes/ | source | Legacy `Imagify_` classmap. Do not add new classes; migrate out. |
| inc/3rd-party/ | source | PSR-4 third-party integrations (WC, AS3CF, NGG, WP Rocket). |
| inc/Dependencies/ | vendor | Vendored ActionScheduler. Do not edit. |
| inc/functions/ | source | Legacy global helpers; migrating to services. |
| inc/admin/ | source | Legacy admin includes. |
| inc/common/ | source | Shared legacy includes. |
| inc/deprecated/ | source | Deprecated classes/traits. Do not add or delete. |
| views/ | source-frontend | PHP admin templates. |
| _dev/ | source-frontend | Frontend source (JS/SCSS). Compiled to assets/. |
| assets/ | assets | Compiled frontend assets. Do not edit directly. |
| config/ | config | Plugin config; providers.php = service provider registry. |
| bin/ | tooling | Dev and CI scripts. |
| Tests/ | tests | PHPUnit unit + integration (capital T). |
| Tests/e2e/ | tests | Playwright E2E (TypeScript). |
| vendor/ | vendor | Composer deps. Do not edit. |
| node_modules/ | vendor | NPM deps. Do not edit. |

---

## 8. Workflow Component Disposition

| Component | Decision | Reasoning |
|---|---|---|
| `agents/grooming-agent.md` | ADAPT | WP-plugin grooming; reference dual-layer architecture + repo-map. |
| `agents/challenger.md` | KEEP_AS_IS | Project-agnostic. |
| `agents/backend-agent.md` | ADAPT | PHP/WordPress backend. Wire to PHPUnit (`composer run-tests`), PHPCS/PHPStan, classes/ vs inc/classes/ rules, DI container + ServiceProvider/Subscriber patterns, anti-patterns (no get_instance/InstanceGetterTrait in classes/), PHPCS specs. |
| `agents/frontend-agent.md` | ADAPT | Frontend exists (_dev/ JS/SCSS -> assets/, Grunt + bud). No JS unit runner; verification via build + Playwright. Do-not-edit assets/. |
| `agents/lead-reviewer.md` | ADAPT | Reference repo conventions (minimal diffs, dual-layer, escaping/nonce/sanitize specs). |
| `agents/qa-engineer.md` | ADAPT | DOD Check 2 -> PHPUnit (`composer test-unit`/`test-integration`); align with preserved .aiassistant/agents/qa-engineer.md. |
| `agents/e2e-qa-tester.md` | ADAPT | Browser-testable UI present. Carry forward Imagify env: localhost:8888, admin/password, bin/dev-up.sh + dev-seed.sh, admin routes, selectors, POM files, API-key guard, SHA-based screenshot publishing (preservation file §4 + .aiassistant/agents/e2e-qa-tester.md). |
| `agents/release-agent.md` | KEEP_AS_IS | Generic git/gh. |
| `agents/ticket-writer.md` | ADAPT | Repo reference -> wp-media/imagify-plugin. |
| `commands/orchestrator.md` | ADAPT | Constants: REPO=wp-media/imagify-plugin, base develop, temp_root, script paths. |
| `commands/issue-workflow.md` | ADAPT | Script paths, config keys, temp_root, base branch origin/develop (explicit). |
| `commands/dod.md` | ADAPT | Check 2 = PHPUnit; add PHPCS/PHPStan gates; E2E via Playwright. |
| `commands/e2e.md` | ADAPT | Playwright present; wire to bin/test-e2e.sh + Tests/e2e config. |
| `commands/docs.md` | KEEP_AS_IS | Generic. |
| `commands/compliance.md` | KEEP_AS_IS | WP project — keep (PHP escaping/nonce/sanitize). Align with .aiassistant/skills/wordpress-compliance/SKILL.md + specs/phpcs/. |
| `commands/knowledge-graph.md` | ADAPT | Project has bin/build-knowledge-graph.js + .aiassistant/skills/knowledge-graph/SKILL.md; wire paths. |
| `scripts/issue-sync.sh` | ADAPT | NOT pure-generic here: must hardcode REPO=wp-media/imagify-plugin and the issue/PR output paths, plus the GraphQL epic/sub-issue detection logic (preservation §5). Existing version: .aiassistant/skills/issue-workflow/scripts/issue-sync.sh (uses .TemporaryItems — reconcile with temp_root .ai). |
| `scripts/make-issue-branch.sh` | ADAPT | Base branch must be explicit origin/develop (preservation §5); otherwise generic. Existing: .aiassistant/skills/issue-workflow/scripts/make-issue-branch.sh. |
| `scripts/init-pr-draft.sh` | KEEP_AS_IS | Generic (existing: .aiassistant/skills/issue-workflow/scripts/init-pr-draft.sh). |
| `refs/pr-template.md` | ADAPT | Imagify-specific sections (Affected Features & QA Scope; Technical description: Documentation/Dependencies/Risks; Mandatory Checklist: Code validation + Code style; Unticked justification; Additional Checks). Preserve AI guardrail comment. Source: .aiassistant/skills/issue-workflow/refs/pr-template.md (preservation §6). |
| `bin/dev-start.sh` | ADAPT | wp-env based. Mirror existing bin/dev-up.sh (--no-seed/--reset, preflight, composer install, wp-env start, activate imagify, seed). |
| `bin/dev-seed.sh` | REWRITE | Seeding exists and is Imagify-specific (API key from IMAGIFY_TESTS_API_KEY, test JPEG upload). Mirror existing bin/dev-seed.sh. |
| `bin/dev-down.sh` | ADAPT | wp-env stop/destroy. Mirror existing bin/dev-down.sh (--clean). |

Counts: KEEP_AS_IS = 4 (challenger, release-agent, docs, init-pr-draft) + compliance KEEP = 5; ADAPT = 17; REWRITE = 1 (dev-seed); DROP = 0.

---

## 9. Analyst Notes

- **Existing adapted workflow already present.** `.aiassistant/` contains a previously-tailored Imagify workflow (skills imagify-architecture / issue-workflow / knowledge-graph / wordpress-compliance, agents qa-engineer + e2e-qa-tester, specs/phpcs/*, config/repo-map.json, scripts, pr-template). Writers should treat these as the de-facto baseline and port their content into the new `.claude/` layout rather than regenerating blind. `.aiassistant/transplant-preservation.md` is the authoritative checklist.
- **Dual-layer PHP architecture (hard constraint).** New code -> `classes/` (PSR-4 `Imagify\`, `declare(strict_types=1)`). Never add classes to `inc/classes/` (legacy `Imagify_`); migrate out instead. Anti-patterns to reject: `get_instance()`, `InstanceGetterTrait`, global state/static helpers replacing services. DI = Strauss-prefixed league/container; providers registered in `config/providers.php`; hooks via `SubscriberInterface` in `ServiceProvider::get_subscribers()`.
- **temp_root discrepancy (resolve in writer step).** Interview is authoritative: `temp_root = .ai`. But the live `issue-sync.sh` and `.gitignore` use `.TemporaryItems/Issues/imagify-plugin/...`. The generated workflow should standardize on `.ai`; the writer should also add `/.ai` to `.gitignore` (currently only `/.TemporaryItems` is ignored) or keep `.TemporaryItems` if the team prefers continuity — flag for human confirmation.
- **Three PHPCS sniffs are excluded in phpcs.xml** (NonceVerification.Missing, NonceVerification.Recommended, plus output/sanitize handled "later"). The compliance command + specs/phpcs/ guides (escaped-output, nonce-verification-recommended, validated-sanitized-input) document the correct remediation patterns; nonce action naming convention is `imagify_<feature>_<action>`. Do not blanket-add `phpcs:ignore`.
- **Strauss build step is mandatory** before tests/lint: `composer install` auto-runs `prefix-namespaces`. Any CI/agent that runs PHPUnit/PHPCS must ensure Strauss + composer install ran first (CI does this explicitly).
- **Single-edition plugin** — no FREE/PRO split. Remove the editions block from maestro.json.
- **E2E env specifics** must survive: localhost:8888, admin/password, bin/dev-up.sh (idempotent) + bin/dev-seed.sh, screenshots in `.e2e-screenshots/`, API-key guard `test.skip(!process.env.IMAGIFY_TESTS_API_KEY,...)`, and SHA-based raw.githubusercontent.com screenshot URLs for QA reports.
- **No frontend JS unit runner** (no Jest/Vitest); frontend verification = `npm run build` (Grunt) + Playwright. Do not invent a JS unit gate.
- `.aiassistant` is `export-ignore` in `.gitattributes` (excluded from dist) — safe location; the new `.claude/` output is the runtime home.
