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
| "The environment probably isn't up, I'll skip" | Run `{E2E_BOOT}`. It's idempotent. If it fails, log `SKIP` with the reason — do not silently omit the step. |
| "The change is backend-only, no need to smoke it" | The primary happy path must be verified. A backend change with no observable behavior change still needs a confirming assertion. |
| "I already read the code, I know it works" | "Seems right" never closes a task. Run the scenario. |
| "One scenario is too slow for this stage" | Basic tier is exactly one primary scenario. The cost is acceptable. |

### Basic tier process

1. Boot the environment (idempotent — safe to run if already up):
   ```bash
   {E2E_BOOT}
   ```
   If the script exits non-zero, set `status: "SKIP"`, note the reason, and do not block
   the pipeline.

2. Run the primary happy path scenario from the spec or grooming plan.

   **Backend / AJAX / REST:**
   ```bash
   # Public REST or AJAX
   curl -s -X POST {E2E_URL}/wp-admin/admin-ajax.php \
     -H "Cookie: $(cat .wp-session-cookie 2>/dev/null)" \
     -d 'action=<action>&nonce=...'

   # Cache headers
   curl -sI {E2E_URL}/ | grep -E '(x-cache|cf-cache)'
   ```

   **Browser (settings page, dashboard notices, interactive UI):**
   Use the Playwright MCP directly for basic-tier smoke. Do not delegate to
   `e2e-qa-tester` at this tier (that is the extended tier path):
   ```
   mcp__playwright__navigate({ url: "{E2E_URL}/wp-login.php" })
   # login
   mcp__playwright__fill({ selector: "#user_login", value: "admin" })
   mcp__playwright__fill({ selector: "#user_pass", value: "password" })
   mcp__playwright__click({ selector: "#wp-submit" })
   # primary scenario
   mcp__playwright__navigate({ url: "{E2E_SETTINGS}" })
   mcp__playwright__snapshot({})
   # Inspect snapshot output to confirm expected element/text is present
   ```

   Take at most 1–2 screenshots if helpful, but do not publish them at this tier.

3. Report:
   ```json
   {
     "status": "PASS|FAIL|SKIP",
     "scenarios_tested": ["Settings page loads without errors after enabling X option"],
     "details": "Logged in as admin, navigated to {E2E_SETTINGS}, confirmed no JS console errors and X toggle present"
   }
   ```

   `SKIP`: `{E2E_BOOT}` failed or environment unreachable. Record reason. Do not block
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

- The boot script `{E2E_BOOT}` is **idempotent**. Always run it before testing — don't pre-check whether the environment is up.
- Admin credentials: `admin` / `password`.
- Settings page URL: `{E2E_SETTINGS}`.
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
- The basic tier never writes Playwright spec files and is invoked by grooming-agent only. Implementation agents (backend-agent, frontend-agent) do not invoke the e2e skill — full E2E validation belongs to the qa-engineer + e2e-qa-tester tier.
- Known admin routes:

  | Area | URL |
  |---|---|
  | Settings | `/wp-admin/options-general.php?page=imagify` |
  | Bulk optimization | `/wp-admin/upload.php?page=imagify-bulk-optimization` |
  | Custom folders (Files) | `/wp-admin/upload.php?page=imagify-files` |
  | Media library (list) | `/wp-admin/upload.php?mode=list` |
