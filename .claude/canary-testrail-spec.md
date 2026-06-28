# Spec: Canary × TestRail — Consolidated QA Automation for Imagify

**Status:** Implemented — `test/e2e-agent` branch. All 13 audit questions resolved.

---

## 0. Orientation — read this first

This spec consolidates three QA-automation features for the **Imagify WordPress plugin**
(`wp-media/imagify-plugin`) into one design document:

1. **Feature 1 — TestRail scenario generation:** an agent analyses one or more GitHub PRs,
   drafts human-readable TestRail test scenarios, stages them for review, and (on "publish")
   creates them in TestRail in the right section, deduplicating against PRs already covered.
2. **Feature 2 — TestRail run execution:** an agent fetches every test scenario in a TestRail
   run (by milestone or run ID), drives a real browser via **Canary** to execute each one,
   collects pass/fail/blocked outcomes with artifacts, and (on confirm) posts results back to
   TestRail with evidence.
3. **Feature 3 — Canary E2E in the delivery pipeline:** a new `canary-e2e` agent, a drop-in
   replacement for the existing `e2e-qa-tester`, that records rich Canary sessions (trace,
   video, HAR, console) per QA flow during a normal issue-workflow run, selectable at
   orchestrator startup via an `e2e_mode` flag.

All three share one substrate: **Canary** (browser recording + QA platform) and a set of
**battle-tested WordPress fixture snippets** wrapped in a reusable **agent inheritance pattern**
(WP base → Imagify-specific).

### What Canary is (engine + sandbox)

Canary is a browser recording + QA platform built on two npm packages, used as-is via `npx`
(**we never fork or modify them**):

| Package | Command | Role |
|---------|---------|------|
| `@usecanary/cli` | `npx @usecanary/cli` | Playwright daemon + QuickJS sandbox + session lifecycle |
| `@usecanary/ui`  | `npx @usecanary/ui`  | Local web viewer for recorded sessions |

What we own and customise is the **skill pack** — the markdown agent/skill files that drive the CLI.

**QuickJS sandbox constraints (the step-script runtime — critical, non-negotiable):**

- No `require()` / `import` — ESM and CJS both fail.
- No Node.js APIs — no `fs`, `path`, `http`, `process.env`.
- No top-level `fetch()` — QuickJS has no fetch. Run fetch inside the browser via
  `page.evaluate(async () => fetch(...))`.
- No helper files — every utility must be **inlined verbatim** in each step script.
- Globals available: `browser` (Canary's Playwright wrapper), `saveScreenshot`, `console`.
- One browser **persists across all steps in a session** — you log in once, in step 1.

**Session lifecycle:**

```bash
npx @usecanary/cli session start --name "Flow name" --capture trace,video,har,console
npx @usecanary/cli run <step.js>   # repeat per step; appends to session.json["steps"]
npx @usecanary/cli session end     # writes results.json + report.html + artifacts
```

Session ID format: `<slug>-<random8>-<short-hash>` — e.g.
`p0-a--mcp-abilities-discovery-mqubrrhk-3f7e94`.

**Artifacts per session** at `.ai/{N}/canary/<id>/` (relocated from `~/.canary/sessions/<id>/` after `session end`; symlink left at original for the viewer):

```
session.json      ← session metadata + step scripts (written during run)
results.json      ← step results + summary (written at session end)
report.html       ← self-contained pass/fail report (written at session end)
trace.zip         ← Playwright trace (npx playwright show-trace trace.zip)
video/            ← .webm recording
network.har       ← all HTTP traffic
console.log       ← browser console output
screenshots/      ← PNG per saveScreenshot() call
manifest.json     ← artifact inventory
profile/          ← browser profile
```

**`results.json` schema (the contract every Canary-driven agent reads):**

```json
{
  "summary": {
    "stepsPassed": 10, "stepsFailed": 0, "stepsTotal": 10,
    "commandCount": 34, "consoleErrors": 6, "networkFailures": 6
  },
  "steps": [
    { "name": "login-admin", "ok": true, "exitCode": 0, "durationMs": 3681,
      "startedAt": "2026-06-26T02:43:22.479Z", "script": "..." }
  ],
  "artifactList": [
    { "kind": "trace",      "path": "trace.zip",            "bytes": 5528027 },
    { "kind": "video",      "path": "video/xxx.webm",       "bytes": 3955579 },
    { "kind": "har",        "path": "network.har",          "bytes": 29806509 },
    { "kind": "console",    "path": "console.log",          "bytes": 3259 },
    { "kind": "screenshot", "path": "screenshots/step.png", "label": "...",
      "step": "step-name", "bytes": 405656 }
  ]
}
```

**Canary failure-signal gotchas (discovered in live sessions — internalise these):**

- `summary.consoleErrors > 0` is **NOT** a fail signal. QuickJS `console.log` may route through
  the error channel. Use `summary.stepsFailed` instead.
- `summary.networkFailures > 0` is **NOT** a fail signal. WP admin always emits some 4xx/5xx
  (favicon 404 etc.). Use `summary.stepsFailed`.
- Video requires `--capture video` at session start.
- The session **must** be explicitly ended (`session end`) or `results.json`/`report.html` are
  never written. A crash mid-session leaves artifacts incomplete.
- WP REST nonces expire (~12h TTL). Re-fetch the nonce at the start of each REST step, not just
  once at login.

### Canary replay

Step scripts recorded in `session.json` replay without Claude — first run is Claude-driven; subsequent CI replays are zero-inference (see §8).

---

## 1. Architecture overview — how the three features relate

```
                         ┌─────────────────────────────────────────────┐
                         │           Shared infrastructure              │
                         │  • Canary CLI (npx, unmodified)              │
                         │  • WP fixture snippets (login/nonce/REST/...) │
                         │  • Agent inheritance: WP base → Imagify       │
                         │      ~/.claude/agents/canary-wp-session-agent │
                         │      .claude/agents/canary-imagify-session-…  │
                         │  • TestRail REST client knowledge (or MCP)    │
                         └───────────────┬──────────────┬───────────────┘
                                         │              │
        ┌────────────────────────────────┘              └───────────────────────────┐
        │                                                                            │
┌───────▼─────────────────┐   ┌──────────────────────────────┐   ┌──────────────────▼─────────────┐
│ FEATURE 1                │   │ FEATURE 2                    │   │ FEATURE 3                       │
│ TestRail scenario gen    │   │ TestRail run execution       │   │ Canary E2E in pipeline          │
│                          │   │                              │   │                                 │
│ PR(s) → draft scenarios  │   │ milestone/run → fetch cases  │   │ orchestrator e2e_mode flag      │
│ → stage → review →       │   │ → Canary executes each       │   │ → qa-engineer routes to         │
│ publish to TestRail      │   │ → outcomes + artifacts       │   │   canary-e2e | e2e-qa-tester    │
│                          │   │ → confirm → post to TestRail  │   │ → records sessions per flow     │
│ writes the test CASES    │──▶│ EXECUTES those cases via      │   │ → PR comment + Tests/e2e/specs  │
│ that Feature 2 runs      │   │ Canary (shares §6 fixtures)   │   │ (shares §6 fixtures + agents)   │
└──────────────────────────┘   └──────────────┬───────────────┘   └─────────────────────────────────┘
                                               │
                                       recorded step scripts
                                               │
                                               ▼
                                  §8 Replay → free CI reruns
```

---

## 2. Agent & skill inventory

### New skills (standalone entry points)

| Skill | Feature | Trigger | Inputs | Outputs |
|-------|---------|---------|--------|---------|
| `/testrail-scenarios` | 1 | user invokes with PR number(s)/URL(s) or `--since-tag` | PR refs, optional `--force-pr=<n>`, optional section override | staging files under `.ai/testrail/pending/`, summary, then created case IDs on publish |
| `/testrail-run` | 2 | user invokes with milestone name/ID or `--run-id` | milestone/run identifier, optional `--cases=<ids>` filter | results table (case → outcome + Canary artifacts), then TestRail results posted on confirm |

### New agents

| Agent | Scope | Feature | What it does | Inputs | Outputs |
|-------|-------|---------|--------------|--------|---------|
| `testrail-scenario-agent` | project | 1 | Analyses PR(s), drafts scenarios, writes staging files, and on confirm creates TestRail sections/cases | PR diff + description + linked issue spec; TestRail section map | staging YAML; created case IDs (JSON) |
| `testrail-run-agent` | project | 2 | Fetches run cases, orchestrates Canary execution per case, aggregates results, posts to TestRail on confirm | run/milestone ID; TestRail cases | results table; posted result IDs (JSON) |
| `canary-e2e` | project | 3 | Drop-in for `e2e-qa-tester`: records a Canary session per P0/P1 flow, posts results table to PR, writes Playwright specs | `qa-plan.md` path, `e2e_mode: "canary"`, PR number | same JSON contract as `e2e-qa-tester` |
| `canary-imagify-session-agent` | project | 2 & 3 | Drives one Canary session against Imagify using the WP base + Imagify specifics | session name, flow steps/assertions, env (URL/user/pass) | `~/.canary/sessions/<id>/` + parsed `results.json` summary (JSON) |
| `canary-wp-session-agent` | **user** | 2 & 3 | WP-generic Canary base: login, nonce, REST, AJAX, notices. Shared across all WP plugin repos | (read as base by the Imagify agent) | — (instruction base, not invoked directly) |
| `canary-wp-verify-agent` | **user** | 3 (optional) | WP-generic diff → QA-plan helper (fork of marketplace `verify-agent`) | git diff | QA-plan draft |

### Modified agents/skills

| File | Feature | Change |
|------|---------|--------|
| `.claude/agents/qa-engineer.md` | 3 | Becomes `e2e_mode`-aware: writes `.ai/qa-plan.md`; routes to `canary-e2e` or `e2e-qa-tester`; merges either's (identical-shape) results into the PR comment |
| `.claude/skills/orchestrator/SKILL.md` | 3 | Adds the `e2e_mode` prompt to startup calibration; passes `e2e_mode` in every downstream dispatch |

### Explicitly untouched

| File | Why |
|------|-----|
| `.claude/agents/e2e-qa-tester.md` | The stable fallback. Feature 3 must not modify it; a one-word startup choice rolls back to it. |
| `@usecanary/cli`, `@usecanary/ui` | Engine. Used via `npx`, never forked. |

---

## 3. Feature 1 — TestRail scenario generation

### 3.1 Flow

```
/testrail-scenarios <pr | pr-list | --since-tag>

1. Resolve PR scope:
   • explicit PR numbers/URLs, OR
   • --since-tag → git log v{last_tag}..HEAD → merged PR list
2. Dedupe per PR (see 3.5): GET /get_cases/3?refs=<pr_url>
   • cases found → skip, print "⏭ Skipped PR #<n> — <k> cases already exist"
   • --force-pr=<n> regenerates for that PR (does not delete old cases)
3. For each in-scope PR: testrail-scenario-agent reads PR description + diff summary +
   linked issue spec → drafts scenarios (see 3.4)
4. Write one staging file per PR → .ai/testrail/pending/<pr-slug>.yml
5. Print a human-readable summary of every drafted scenario
6. STOP. Wait for the user to review/edit the staging files and say "publish".
7. On "publish":
   a. For each staging file: create new_section if specified (POST /add_section/3)
   b. POST /add_case/<section_id> per scenario, refs=<pr_url>
   c. Print created case IDs
   d. Clean .ai/testrail/pending/
```

The review gate is **mandatory and explicit**: the agent stages, summarises, then halts. It never
creates cases in the same turn it drafts them. The user edits YAML freely (titles, steps, section,
smoke flag) before publishing.

### 3.2 Staging file format (`.ai/testrail/pending/<pr-slug>.yml`)

```yaml
pr: 1133
pr_url: https://github.com/wp-media/imagify-plugin/pull/1133
pr_title: "Add MCP + Abilities to Imagify"
section_id: 7685              # existing target section (API Requests)
new_section: "MCP Abilities"  # subsection to CREATE under section_id (null if not needed)

cases:
  - title: "MCP - Discover abilities - happy path"
    smoke_test: true
    preconditions: |
      - Imagify plugin active
      - Valid API key configured
    steps:
      - action: "Navigate to Settings > Imagify. Ensure API key is saved."
        expected: "Settings page loads. API key field shows a valid key."
      - action: "Connect an MCP-compatible client to the Imagify MCP server."
        expected: "Connection succeeds. No error."
      - action: "Call the discover-abilities tool."
        expected: "Returns a list of available abilities (optimize-media, bulk-optimize, …)."

  - title: "MCP - Discover abilities - invalid API key"
    smoke_test: false
    preconditions: |
      - Imagify plugin active
      - Invalid or missing API key
    steps:
      - action: "Connect MCP client. Call discover-abilities with no valid API key set."
        expected: "Returns an error response indicating authentication failure."
```

Field mapping (staging YAML → TestRail `add_case` payload):

| Staging YAML | TestRail field |
|--------------|----------------|
| `title` | `title` |
| `smoke_test` | `custom_smoketest` (bool) |
| `preconditions` | `custom_preconds` (HTML — agent wraps lines in `<p>`/`<br>`) |
| `steps[].action` | `custom_steps_separated[].content` |
| `steps[].expected` | `custom_steps_separated[].expected` |
| (constant) | `template_id: 2`, `type_id: 7`, `priority_id: 2` |
| `pr_url` | `refs` |

### 3.3 What scenarios the agent drafts (per PR)

The agent reads PR description, diff summary, and linked issue spec, then drafts cases covering:

- **Happy path** — standard user, default settings, expected flow.
- **Happy-path variants** — relevant settings combinations (WebP on/off, plan tiers).
- **Missing prerequisites** — no API key, quota exceeded, wrong plan/license.
- **Network/API failures** — timeout, 5xx, malformed response.
- **Edge cases** — empty state, max limits, unexpected data.
- **Permission levels** — admin vs editor vs subscriber when relevant.
- **Plugin conflicts** — relevant 3rd-party plugins when the change touches integration points.
- **Regression guard** — at least one case confirming prior behaviour still holds (for fixes).

Bias toward **more, more-automatable** cases (each becomes future Canary/Playwright coverage). No
artificial cap per PR. Each case is also evaluated for `smoke_test: true` (critical path that must
always pass). Pure tracking/analytics PRs with no user-visible behaviour: the agent may draft a
single minimal "event fires" case or skip — see §9.

### 3.4 Deduplication

`refs = <github PR url>` on create. Dedup: `GET /get_cases/3?refs=<pr_url>` — cases found → skip; none → draft. `--force-pr=<n>` regenerates (does not delete old cases).

### 3.5 Section mapping

The agent picks the closest existing section; if none fits, it proposes a new subsection under
`Regression (33)` via the `new_section` field (created only on publish, never silently).

| Feature area | Section |
|---|---|
| MCP / Abilities | Regression > API Requests (7685) → new: MCP Abilities |
| Settings | Regression > Settings (32) → appropriate subsection |
| General settings | Regression > Settings > general settings (7689) |
| Optimization | Regression > Settings > optimization (7690) |
| Next-gen (WebP/AVIF) | Regression > Settings > optimization > next-gen (7694) → Webp (7697), Avif (7698) |
| File optimization | Regression > Settings > optimization > file optimization (7695) |
| Bulk optimization | Regression > Bulk Optimization (3766) |
| Media library | Regression > Media library (4976) |
| 3rd-party (WooCommerce) | Regression > 3rd party compatibility > Woocommerce (7686) |
| 3rd-party (WP Rocket) | Regression > 3rd party compatibility > WP Rocket (7687) |
| Action Scheduler | Regression > Action scheduler (4975) |
| Promotions | Regression > Promotions (1082) |
| Smoke tests | flag `custom_smoketest: true` (NOT a separate section) + Smoke test section (4525) for pure smoke cases |
| New feature, no match | propose new subsection under Regression (33) |

### 3.6 MCP vs API

TestRail MCP (`/opt/homebrew/bin/mcp-testrail`) is connected but tool names unconfirmed. **Use the REST API via `curl` as primary** (see §5). MCP is an optional enhancement once tool names are confirmed.

---

## 4. Feature 2 — TestRail run execution

### 4.1 Flow

```
/testrail-run <milestone-name | milestone-id | --run-id=<id>>

1. Resolve the run:
   • --run-id given → use it directly
   • milestone name/id → GET /get_milestones/3 → GET /get_runs/3?milestone_id=<id>
     → if multiple runs, prompt user to pick (or --run-id) [open question §9]
2. GET /get_tests/<run_id> → all cases in the run (title, custom_preconds,
   custom_steps_separated, case_id)
3. For each case (sequential or bounded-parallel):
   a. testrail-run-agent maps the case → a Canary session plan:
      preconditions → setup steps; each custom_steps_separated entry → a Canary step
   b. spawns canary-imagify-session-agent to record the session
      (one Canary session per TestRail case — see 4.3)
   c. reads results.json → derives outcome (see 4.4)
4. Aggregate → results table: case → pass/fail/blocked + session id + artifact paths
5. Print the table. STOP. Ask: "Update TestRail with these results? (y/n)"
6. On confirm, per case:
   POST /add_result_for_case/<run_id>/<case_id> with status + evidence comment (see 4.5)
7. Print posted result summary.
```

The TestRail write is gated behind explicit user confirmation. The agent never posts results in
the same turn it executes — the user sees outcomes first.

### 4.2 How Canary sessions map to TestRail cases

- **One Canary session per TestRail case.** Session name = `TR-<case_id>: <case title>` so the
  artifact directory and report are traceable back to the case.
- **Preconditions → setup steps.** `custom_preconds` is parsed into setup steps (login is always
  step 1 via the wp-login fixture; "valid API key configured" → a settings precheck step, etc.).
- **Each `custom_steps_separated` entry → one Canary step.** The step's `expected` becomes the
  assertion the step's `console.log("PASS/FAIL …")` checks.
- The `canary-imagify-session-agent` translates the human-readable TestRail step into concrete
  Canary script using the §6 fixtures. Where a step is too ambiguous to automate, the case is
  marked **blocked** (not failed) and flagged for manual review.

### 4.3 Outcome derivation (from `results.json`)

| Condition | TestRail status |
|-----------|-----------------|
| `summary.stepsFailed == 0` and all assertion `console.log` lines are PASS | **Passed (1)** |
| any step `ok == false` / `exitCode != 0`, or an assertion logs FAIL | **Failed (5)** |
| env unreachable, session couldn't start, or a step was un-automatable/ambiguous | **Blocked (2)** |

Reminder: do NOT use `consoleErrors` / `networkFailures` as fail signals (see §0).

### 4.4 Evidence in the result comment

The result comment for each case includes per-step outcomes plus pointers to Canary artifacts:

```json
{
  "status_id": 5,
  "comment": "Automated Canary run — 2026-06-26\nSession: TR-4521--mcp-discover-abilities-xxx\n\nStep 1: ✅ Passed\nStep 2: ✅ Passed\nStep 3: ❌ Failed — assertion 'returns ability list' did not hold\n\nTrace: npx playwright show-trace .ai/testrail/1283/canary/TR-4521--…/trace.zip\nReport: .ai/testrail/1283/canary/TR-4521--…/report.html\nScreenshot: <raw.githubusercontent.com SHA URL, if published>",
  "elapsed": "45s"
}
```

Evidence to include:
- Per-step pass/fail summary derived from `results.json.steps`.
- `elapsed` from summed `durationMs`.
- Trace replay command (local path — reviewer runs `npx playwright show-trace`).
- Path to `report.html`.
- Optionally, published screenshot URLs (same temp-branch-commit → SHA-raw-URL mechanism as
  `e2e-qa-tester`; see §6.5) when a hosted image is wanted in TestRail.

### 4.5 Failure handling

- Step fails → case marked Failed, remaining steps captured but the failure is the verdict.
- Env unreachable / session won't start → case Blocked with reason.
- Step un-interpretable → case Blocked + "manual review" flag (never silently Failed).

---

## 5. TestRail API reference (shared by Features 1 & 2)

**Base URL:** `https://wpmediaqa.testrail.io/index.php?/api/v2/`
**Auth:** Basic (`TESTRAIL_USERNAME:TESTRAIL_API_KEY`)
**Project ID:** 3 · **Suite ID:** 3 · **Step template ID:** 2

| Action | Endpoint |
|--------|----------|
| Find cases by PR URL (dedup) | `GET /get_cases/3?refs=<pr_url>` |
| List sections | `GET /get_sections/3&suite_id=3` |
| Create section | `POST /add_section/3` |
| Create case | `POST /add_case/<section_id>` |
| List milestones | `GET /get_milestones/3` |
| List runs for a milestone | `GET /get_runs/3?milestone_id=<id>` |
| Get tests in a run | `GET /get_tests/<run_id>` |
| Post a result | `POST /add_result_for_case/<run_id>/<case_id>` |

**Case payload (Step template):**

```json
{
  "title": "MCP - Discover abilities - happy path",
  "template_id": 2,
  "type_id": 7,
  "priority_id": 2,
  "refs": "https://github.com/wp-media/imagify-plugin/pull/1133",
  "custom_smoketest": false,
  "custom_preconds": "<p>- Imagify active<br>- Valid API key</p>",
  "custom_steps_separated": [
    { "content": "Navigate to Settings > Imagify.", "expected": "Settings page loads." }
  ]
}
```

**Result payload:**

```json
{
  "status_id": 1,
  "comment": "Automated run …",
  "elapsed": "45s"
}
```

Status IDs: **1 = Passed · 5 = Failed · 2 = Blocked**.

**Known section IDs:**

```
Regression (33)
  ├── Settings (32)
  │   ├── general settings (7689)
  │   └── optimization (7690)
  │       ├── next-gen (7694) → Webp (7697), Avif (7698)
  │       └── file optimization (7695)
  ├── Smoke test (4525)
  ├── API Requests (7685)          ← MCP / Abilities go here
  ├── Media library (4976)
  ├── Bulk Optimization (3766)
  ├── 3rd party compatibility (4980)
  │   ├── Woocommerce (7686)
  │   └── WP Rocket (7687)
  ├── Action scheduler (4975)
  └── Promotions (1082)
```

---

## 6. Feature 3 — Canary E2E in the pipeline

`canary-e2e` is a drop-in replacement for `e2e-qa-tester` selected via `e2e_mode: "canary"` at orchestrator startup. Same JSON return contract; see `.claude/agents/canary-e2e.md` for full instructions.

### 6.1 `qa-plan.md` format (shared by qa-engineer ↔ canary-e2e ↔ e2e-qa-tester)

Produced by `qa-engineer`, written to `.ai/qa-plan.md`, consumed by whichever E2E agent runs.
The `P0-A`/`P0-B` labels become Canary session names.

```markdown
## QA Plan — PR #1234

### P0 — Must pass before merge

#### P0-A: <flow name>
- **Entry:** <URL>
- **Steps:**
  1. <step>
  2. <step>
- **Assertions:**
  - <specific enough to write a Canary assert>
- **Risk:** <which file(s) break this if wrong>
- **Canary session name:** `P0-A: <flow name>`

### P1 — Should pass
#### P1-A: <flow name>
(same structure)

### P2 — Nice to have / regression guard
#### P2-A: <flow name>
- documented; Canary session optional
```

### 6.2 Artifact flow

```
.ai/{N}/canary/<id>/   ← sessions relocated here from ~/.canary/sessions/<id>/

GitHub PR comment: qa-plan table + Canary results table + screenshot SHA URLs
                  + "Trace: npx playwright show-trace .ai/{N}/canary/<id>/trace.zip"
Tests/e2e/specs/  ← Playwright specs committed by canary-e2e (CI re-runs them)
```

### 6.3 Playwright spec translation

`canary-e2e` writes fresh TypeScript specs — not a transpile of QuickJS steps:

| Canary QuickJS | Playwright TypeScript |
|---|---|
| `browser.getPage("main")` | `page` fixture |
| `page.evaluate(async () => fetch(...))` | identical |
| `console.log("PASS: …")` | `expect(...).toBe(...)` |
| no `import` | `import { test, expect } from '@playwright/test'` |
| inlined helpers | POM methods in `Tests/e2e/pages/` |

---

## 7. Shared infrastructure

### 7.1 Agent inheritance pattern (WP base → Imagify)

WP plugins share identical WP patterns but differ in plugin specifics. Split accordingly:

```
~/.claude/agents/                      ← USER-level, shared across ALL WP plugin repos
  canary-wp-session-agent.md           # login, nonce, REST, AJAX, notices
  canary-wp-verify-agent.md            # WP QA-plan format + verify flow (optional)

.claude/agents/                        ← PROJECT-level, Imagify only
  canary-imagify-session-agent.md      # extends WP base + Imagify URLs/slugs/MCP/fixtures
```

WP-generic patterns (login, nonce, REST, AJAX, notices) live in the base agent only. The plugin agent reads it as its first instruction and extends it with Imagify-specific URLs, selectors, and ability slugs.

### 7.2 WP fixture snippets (canonical — never improvised, always copied verbatim)

Carried in `canary-wp-session-agent.md`. Because QuickJS has no `require()`, these are inlined per
step.

```js
// FIXTURE: wp-login — always step 1; browser persists for all subsequent steps
const page = await browser.getPage("main");
await page.goto("{E2E_URL}/wp-login.php", { waitUntil: "domcontentloaded" });
await page.locator("#user_login").fill("{WP_USER}");
await page.locator("#user_pass").fill("{WP_PASS}");
await page.locator("#wp-submit").click();
await page.waitForURL("**/wp-admin/**", { timeout: 10000 });
console.log("Login:", page.url().includes("wp-admin") ? "PASS" : "FAIL");
```

```js
// FIXTURE: wp-nonce — GET with credentials:same-origin; result needs .trim()
const nonce = await page.evaluate(async () => {
  const r = await fetch("/wp-admin/admin-ajax.php?action=rest-nonce", { credentials: "same-origin" });
  return (await r.text()).trim();
});
```

```js
// FIXTURE: wp-rest-get — authenticated GET to WP REST API
const result = await page.evaluate(async ([url, nonce]) => {
  const r = await fetch(url, { headers: { "X-WP-Nonce": nonce }, credentials: "same-origin" });
  return { status: r.status, body: await r.text() };
}, [url, nonce]);
```

```js
// FIXTURE: wp-rest-post — authenticated POST to WP REST API
const result = await page.evaluate(async ([url, nonce, body]) => {
  const r = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-WP-Nonce": nonce },
    body: JSON.stringify(body),
    credentials: "same-origin"
  });
  return { status: r.status, body: await r.text() };
}, [url, nonce, body]);
```

```js
// FIXTURE: wp-ajax — POST to admin-ajax.php with _ajax_nonce
const result = await page.evaluate(async ([action, nonce, data]) => {
  const form = new FormData();
  form.append("action", action);
  form.append("_ajax_nonce", nonce);
  Object.entries(data).forEach(([k, v]) => form.append(k, String(v)));
  const r = await fetch("/wp-admin/admin-ajax.php", { method: "POST", body: form, credentials: "same-origin" });
  return { status: r.status, body: await r.text() };
}, [action, nonce, data]);
```

```js
// FIXTURE: wp-assert-notice — assert success/error admin notice
const notice = await page.waitForSelector(".notice-success, .notice-error", { timeout: 5000 });
const cls = await notice.getAttribute("class");
console.log(cls.includes("notice-success") ? "PASS: success notice" : "FAIL: error notice");
```

**Imagify-specific (belongs in `canary-imagify-session-agent.md`, NOT the WP base):**

```js
// FIXTURE: imagify-abilities — assert all expected slugs in WP Abilities response
const result = await page.evaluate(async () => {
  const nonceResp = await fetch("/wp-admin/admin-ajax.php?action=rest-nonce", { credentials: "same-origin" });
  const nonce = (await nonceResp.text()).trim();
  const resp = await fetch("/wp-json/wp-abilities/v1/abilities", {
    headers: { "X-WP-Nonce": nonce }, credentials: "same-origin"
  });
  return { status: resp.status, body: await resp.text() };
});
const data = JSON.parse(result.body);
const slugs = Array.isArray(data) ? data.map(a => a.name) : [];
console.log("PHP Fatal:", result.body.includes("Fatal error") ? "FAIL" : "PASS");
const expected = [
  "imagify/get-settings", "imagify/update-settings", "imagify/get-account",
  "imagify/get-stats", "imagify/get-media-status", "imagify/get-nextgen-coverage",
  "imagify/optimize-media", "imagify/bulk-optimize",
  "imagify/generate-missing-nextgen", "imagify/restore-media"
];
for (const slug of expected) console.log(slugs.includes(slug) ? `PASS: ${slug}` : `FAIL: ${slug} NOT FOUND`);
```

### 7.3 Session-agent trust guard

**Not present** in the Canary marketplace agents (confirmed in audit). No guard to remove. Both `canary-wp-session-agent` and `canary-imagify-session-agent` can be invoked transitively from the pipeline without deadlocking on a confirmation prompt.

---

## 8. Canary replay (Phase B)

Step scripts in `session.json` replay without Claude — first run is Claude-driven; subsequent replays are zero-inference.

- **Feature 2 (Phase B):** persist scripts to `Tests/e2e/canary/TR-<case_id>/`; CI replays unchanged cases. Only new/changed cases need Claude.
- **Feature 3:** `Tests/e2e/specs/` already provides the CI rerun path. Canary scripts can also be replayed locally to reproduce failures deterministically.

Phase A (current): Claude executes every run. Phase B: CI replays recorded scripts.

---

---

## 9. Audit resolution (2026-06-26)

All questions resolved during deep audit. No open questions remain for initial implementation.

| # | Question | Resolution |
|---|----------|------------|
| 1 | TestRail MCP availability | ✅ **Connected** — `/opt/homebrew/bin/mcp-testrail` in `~/.claude/settings.json`. Tool names unconfirmed; use REST API (curl) as primary. MCP optional enhancement. |
| 2 | Skill naming | ✅ `/testrail-scenarios` + `/testrail-run` (user confirmed) |
| 3 | `refs` API filter | ✅ **Works** — `GET /get_cases/3?suite_id=3&refs=<url>` returns `{cases:[], size:0}` when no match. Primary dedup strategy confirmed. |
| 4 | Section auto-creation | ✅ Propose in YAML, create only on "publish" confirm. Already designed this way. |
| 5 | Tracking-only PRs | ✅ Generate a minimal "event fires" case verifying the event reaches the analytics endpoint. Don't skip. |
| 6 | Multiple runs per milestone | ✅ **Real data:** one run per milestone in practice (milestone 2.3.0 → single run 1283 with 151 untested cases). Design: if multiple exist, prompt user; default to the single one. |
| 7 | Parallelism | ✅ **Sequential** — matches existing `playwright.config.ts` (`workers: 1`, `fullyParallel: false`). WP install shared; sequential keeps DB state predictable. |
| 8 | Recorded script persistence | ✅ `Tests/e2e/canary/TR-<case_id>/` — one dir per TestRail case ID containing step scripts. Phase B: CI runs `npx @usecanary/cli run` on these instead of re-inferring. |
| 9 | CANARY_HOME in CI | ⚠️ **Deferred to Phase B** — not needed for Phase A (local-only Canary). Investigate `CANARY_HOME` override when CI execution is in scope. |
| 10 | Spec generation strategy | ✅ **Fresh write** — `canary-e2e` writes TypeScript Playwright specs from scratch (same assertions as Canary steps), not a mechanical transpile. QuickJS ≠ Node. |
| 11 | CI vs local Canary | ✅ **Local-only Canary** initially. CI runs committed Playwright specs from `Tests/e2e/specs/`. No Canary dep in CI for Phase A. |
| 12 | WP fixture coverage | ✅ **Audited.** 6 WP snippets cover all patterns in `Tests/e2e/specs/`. Additional Imagify-specific fixtures needed in `canary-imagify-session-agent`: `#imagify-check-api-container:not(.imagify-valid)` for invalid API key (NOT `.notice-error`), `#imagify-bulk-action`, `.imagify-bulk-table`. `wp-multisite` needed for some TestRail cases — deferred. Login uses `getByLabel('Username or Email Address')` not `#user_login`. |
| 13 | Return-contract parity | ✅ `canary-e2e` returns identical JSON to `e2e-qa-tester` plus `canary_sessions[]` and `canary_results_table` fields. Run `/contract-check` after implementation to verify. |

**Additional audit findings:**

- **CLI syntax correction:** `id=$(npx @usecanary/cli session start --name "X")` — session ID returned from stdout, passed as `--session "$id"` to subsequent `run` commands
- **Screenshot location:** daemon auto-captures one screenshot per step (last-opened tab) — this goes in the report. `saveScreenshot()` → `~/.canary/tmp/` (debug only, NOT in report)
- **Trust guard:** NOT present in `session-agent.md` — no guard to remove. No confirmation requirement in pipeline use.
- **TestRail steps are HTML:** `<p>Visit settings.</p>` — must strip with `python3 -c "import sys,re; print(re.sub('<[^>]+>','',sys.stdin.read()).strip())"`
- **Active run 1283 (2.3.0):** 151 untested cases, status_id=3 (untested)

