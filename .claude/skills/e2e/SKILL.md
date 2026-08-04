---
name: e2e
description: Run E2E smoke tests (basic) or full acceptance + regression suite (extended).
---

# E2E SKILL

## Config loading

Read `AGENTS.md` → **Project Configuration** for every value this skill needs (local URL, boot
command, settings page, repo, test commands). Do not expect values to be injected — this skill is
standalone and no orchestrator supplies them.

The values used below: local environment `http://localhost:8888` (`admin` / `password`), booted with
`bash bin/dev-up.sh`. Specs are committed permanently to `Tests/e2e/specs/`.

This skill provides end-to-end test execution at two tiers. The difference is scope and depth, not
tooling — browser driving belongs to whichever `e2e-qa-tester` agent the installed pipeline provides
(currently a `playwright-cli` based one), with `curl` for API-level checks.

---

## Tier 1 — Basic

**Purpose:** behavioral verification and smoke tests. Fast enough to fit inside a planning
agent's execution window.

**Invokers:**
- `grooming-agent` — verify behavioral assumptions about the current system *before*
  writing the spec. Use to confirm: does the current feature behave as described in the
  issue? What does the current API or AJAX endpoint return for the scenario being changed?

### Anti-rationalization table

| You'll be tempted to say | Why you can't |
|---|---|
| "The environment probably isn't up, I'll skip" | Run `bash bin/dev-up.sh`. It's idempotent. If it fails, log `SKIP` with the reason — do not silently omit the step. |
| "The change is backend-only, no need to smoke it" | The primary happy path must be verified. A backend change with no observable behavior change still needs a confirming assertion. |
| "I already read the code, I know it works" | "Seems right" never closes a task. Run the scenario. |
| "One scenario is too slow for this stage" | Basic tier is exactly one primary scenario. The cost is acceptable. |

### Basic tier process

1. Boot the environment (idempotent — safe to run if already up):
   ```bash
   bash bin/dev-up.sh
   ```
   If the script exits non-zero, set `status: "SKIP"`, note the reason, and do not block
   the pipeline.

2. Run the primary happy path scenario from the spec or grooming plan.

   **Backend / AJAX / REST:**
   ```bash
   # Public REST or AJAX
   curl -s -X POST http://localhost:8888/wp-admin/admin-ajax.php \
     -H "Cookie: $(cat .wp-session-cookie 2>/dev/null)" \
     -d 'action=<action>&nonce=...'

   # Cache headers
   curl -sI http://localhost:8888/ | grep -E '(x-cache|cf-cache)'
   ```

   **Browser (settings page, dashboard notices, interactive UI):**
   Drive the browser with whatever tooling the installed pipeline provides — currently
   `playwright-cli` via Bash. Do not delegate to `e2e-qa-tester` at this tier (that is the
   extended tier path). Log in at `http://localhost:8888/wp-login.php` as `admin` / `password`,
   navigate to the target page, and read the page snapshot to confirm the expected element or
   text is present.

   Take at most 1–2 screenshots if helpful, but do not publish them at this tier.

3. Report:
   ```json
   {
     "status": "PASS|FAIL|SKIP",
     "scenarios_tested": ["Settings page loads without errors after enabling X option"],
     "details": "Logged in as admin, navigated to http://localhost:8888/wp-admin/options-general.php?page=imagify, confirmed no JS console errors and X toggle present"
   }
   ```

   `SKIP`: `bash bin/dev-up.sh` failed or environment unreachable. Record reason. Do not block
   the pipeline.

### Basic tier boundaries

- Do: verify the **one primary scenario** from the spec or grooming plan
- Do: probe current-system behavior (grooming-agent only) when an assumption needs verification
- Do not: cover all acceptance criteria (that is extended tier)
- Do not: write or commit Playwright specs (that is extended tier via `e2e-qa-tester`)
- Do not: publish screenshots (that is extended tier)

---

## Tier 2 — Extended

**Purpose:** full acceptance criteria coverage, regression testing, edge cases, visual
comparison, and Playwright spec authoring with screenshot evidence.

**Invoker:** `qa-engineer` only.

**Execution:** the qa-engineer agent delegates browser flows to the `e2e-qa-tester`
sub-agent, which handles Playwright MCP driving, temporary spec authoring under
`.e2e-temp/`, screenshot publishing via the commit-SHA method, and clean-up.

The qa-engineer agent itself handles:
- Strategy A (API / functional validation via curl)
- Strategy C (test-suite-only fallback when the environment is unreachable)

Strategy selection, report format, browser flow execution, and spec authoring belong to the
installed pipeline's `qa-engineer` and `e2e-qa-tester` agents — read those agent definitions for
details.

Specs authored during the extended tier are **committed permanently** to `Tests/e2e/specs/` on this
project, never authored-then-deleted. If the pipeline's agent defaults to discarding them, override
it.

Screenshots go to `.e2e-screenshots/` (gitignored). Screenshots are published using the commit-SHA method: commit screenshots temporarily to the branch, push, capture the SHA, then remove them in a follow-up commit. Use the SHA-based raw.githubusercontent.com URL in QA reports (permanent even after file removal):
```
https://raw.githubusercontent.com/wp-media/imagify-plugin/<SHA>/.e2e-screenshots/<filename>
```

---

## When to use which tier

| Invoker | Tier | Purpose |
|---|---|---|
| `grooming-agent` | Basic | Verify a behavioral assumption before writing the spec |
| `qa-engineer` | Extended | Full acceptance criteria + regression + screenshots |

---

## Project-specific notes

- The boot script `bash bin/dev-up.sh` is **idempotent**. Always run it before testing — don't pre-check whether the environment is up.
- Admin credentials: `admin` / `password`.
- Settings page URL: `http://localhost:8888/wp-admin/options-general.php?page=imagify`.
- Plugin activation check:
  ```bash
  npx @wordpress/env run cli wp plugin list --name=imagify
  ```
- Playwright config: `Tests/e2e/playwright.config.ts`. Test specs: `Tests/e2e/specs/`. Page objects: `Tests/e2e/pages/`. Fixtures: `Tests/e2e/fixtures/`.
- Page Object Model files:
  - `Tests/e2e/pages/settings.ts` → `SettingsPage`
  - `Tests/e2e/pages/bulk-optimization.ts` → `BulkOptimizationPage`
  - `Tests/e2e/pages/media-library.ts` → `MediaLibraryPage`
- API-key-gated tests require `IMAGIFY_TESTS_API_KEY` to be set (sourced from `.env.local`):
  ```typescript
  test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set' );
  ```
- Run the full E2E suite via: `bash bin/test-e2e.sh` (flags: `--headed`, `--ui`, or a spec pattern).
- The basic tier never writes Playwright spec files and is invoked by grooming-agent only. Implementation agents do not invoke the e2e skill — full E2E validation belongs to the qa-engineer + e2e-qa-tester tier.
- Known admin routes:

  | Area | URL |
  |---|---|
  | Settings | `/wp-admin/options-general.php?page=imagify` |
  | Bulk optimization | `/wp-admin/upload.php?page=imagify-bulk-optimization` |
  | Custom folders (Files) | `/wp-admin/upload.php?page=imagify-files` |
  | Media library (list) | `/wp-admin/upload.php?mode=list` |

---

## License, quota, and API guards

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

## Spec conventions

- No `setTimeout` / `waitForTimeout` — use web-first assertions with explicit timeouts.
- Never use `test.skip()` as a fallback for missing seed data. It reports green with zero
  assertions, so CI passes while nothing is tested. Assert hard instead, with a message naming the
  seed command that fixes the environment.
- Always call `locator.scrollIntoViewIfNeeded()` before screenshotting, or use the shared
  `screenshotElement()` helper from `Tests/e2e/fixtures/screenshot.ts`. A screenshot taken at
  page-load position captures the top of the page rather than the feature under test, which is zero
  evidence and misleads reviewers.
