# Spec: Canary × TestRail — Consolidated QA Automation for Imagify

**Status:** Draft — brainstorm/design only. No implementation in this document.
**Author:** Gaël Robin
**Audience:** Future Claude sessions with zero prior context. This document is self-contained.
**Next step:** Deep audit session.

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

### The existing delivery pipeline (context)

Imagify ships via an agentic Claude Code pipeline:

```
orchestrator → grooming-agent → backend-agent → lead-reviewer → qa-engineer → e2e-qa-tester → release-agent
```

- Driven by `.claude/skills/orchestrator/SKILL.md` and `.claude/skills/issue-workflow/SKILL.md`.
- Agents live in `.claude/agents/`.
- `qa-engineer` is the QA gate; it currently spawns `e2e-qa-tester` for UI/browser changes.
- `e2e-qa-tester` drives the browser via `mcp__playwright`, writes specs to `Tests/e2e/specs/`,
  and publishes screenshots via temp-branch commit + SHA `raw.githubusercontent.com` URLs.
  **It is the current stable default and is NOT modified by any feature here.**

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

**Artifacts per session** at `~/.canary/sessions/<id>/`:

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

### The Canary replay advantage (the reason this is worth building)

Canary step scripts are recorded into `session.json` and can be **replayed without Claude**:

```bash
npx @usecanary/cli run ~/.canary/sessions/<id>/steps/<step>.js
```

This means: **the first run is Claude-driven (inference cost); every subsequent replay is free**
(zero inference). This underpins Feature 2's path to free CI reruns (see §8).

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

**How they connect:**

- **Feature 1 feeds Feature 2.** Feature 1 produces TestRail cases (human-readable steps);
  Feature 2 reads those exact cases and executes them via Canary. They are two halves of the
  "release QA" loop: generate coverage, then run it.
- **Feature 2 and Feature 3 share the Canary execution substrate.** Both spawn the
  `canary-imagify-session-agent` to drive the browser with the §6 WP fixtures. The difference is
  the *source of truth* for what to test:
  - Feature 3 reads `qa-plan.md` (P0/P1/P2 flows derived from a diff) — pre-merge, per PR.
  - Feature 2 reads TestRail cases (`custom_steps_separated`) — release-time, full suite.
- **Feature 3 is pipeline-embedded; Features 1 & 2 are standalone skills.** Feature 3 plugs into
  the orchestrator/qa-engineer flow. Features 1 & 2 are invoked on demand (`/testrail-*`).
- **All three reuse the agent inheritance pattern (§6).** The WP base agent is the single source
  of truth for login/nonce/REST/AJAX; the Imagify agent extends it with plugin specifics.

---

## 2. Agent & skill inventory

### New skills (standalone entry points)

| Skill | Feature | Trigger | Inputs | Outputs |
|-------|---------|---------|--------|---------|
| `/testrail-scenarios` | 1 | user invokes with PR number(s)/URL(s) or `--since-tag` | PR refs, optional `--force-pr=<n>`, optional section override | staging files under `.ai/testrail/pending/`, summary, then created case IDs on publish |
| `/testrail-run` | 2 | user invokes with milestone name/ID or `--run-id` | milestone/run identifier, optional `--cases=<ids>` filter | results table (case → outcome + Canary artifacts), then TestRail results posted on confirm |

> Names are proposals. The earlier draft used `/testrail-release-setup` and
> `/testrail-release-process`; this spec renames to `/testrail-scenarios` and `/testrail-run` to
> decouple from the word "release" (the features are useful per-PR too). Final names are an open
> question (§9).

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

### 3.1 Goal

Expand QA coverage beyond "what changed" by auto-drafting structured TestRail cases from PRs,
keeping a human in the loop (review → approve → publish), and never duplicating cases for a PR
already covered.

### 3.2 Flow

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

### 3.3 Staging file format (`.ai/testrail/pending/<pr-slug>.yml`)

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

### 3.4 What scenarios the agent drafts (per PR)

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

### 3.5 Deduplication

**Strategy:** TestRail's `refs` field links a PR to its cases.

- On create, `refs = <github PR url>`.
- On the next run, per in-scope PR: `GET /get_cases/3?refs=<pr_url>`.
  - cases found → skip the PR.
  - none found → draft and stage.
- `--force-pr=<n>` regenerates and stages new cases for a PR even if cases exist (does **not**
  delete the old ones — manual cleanup in TestRail).

**Fallback if `refs` filtering is unsupported in this TestRail version** (open question §9):
query all cases in the relevant section and match by title prefix, OR maintain a local index at
`.ai/testrail/index.json` (persists between runs; NOT cleaned with `pending/`).

### 3.6 Section mapping

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

### 3.7 MCP vs API

TestRail may be reachable via an MCP server (not confirmed). The agent must handle both:

- **If a TestRail MCP is available:** prefer its tools for `get_cases` / `add_section` /
  `add_case` — typed, less error-prone.
- **Otherwise (default assumption):** use the TestRail REST API (see §5) with Basic auth from
  `TESTRAIL_USERNAME` / `TESTRAIL_API_KEY`.

The agent probes for the MCP first; if absent, falls back to REST. Both paths produce identical
staging files and identical `refs`-based dedup behaviour.

---

## 4. Feature 2 — TestRail run execution

### 4.1 Goal

Run an entire TestRail test run automatically via Canary browser automation, gather pass/fail/
blocked outcomes with rich artifacts, let the user review, then post results back to TestRail with
evidence — turning the full regression suite into an automated pass instead of a manual one.

### 4.2 Flow

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

### 4.3 How Canary sessions map to TestRail cases

- **One Canary session per TestRail case.** Session name = `TR-<case_id>: <case title>` so the
  artifact directory and report are traceable back to the case.
- **Preconditions → setup steps.** `custom_preconds` is parsed into setup steps (login is always
  step 1 via the wp-login fixture; "valid API key configured" → a settings precheck step, etc.).
- **Each `custom_steps_separated` entry → one Canary step.** The step's `expected` becomes the
  assertion the step's `console.log("PASS/FAIL …")` checks.
- The `canary-imagify-session-agent` translates the human-readable TestRail step into concrete
  Canary script using the §6 fixtures. Where a step is too ambiguous to automate, the case is
  marked **blocked** (not failed) and flagged for manual review.

### 4.4 Outcome derivation (from `results.json`)

| Condition | TestRail status |
|-----------|-----------------|
| `summary.stepsFailed == 0` and all assertion `console.log` lines are PASS | **Passed (1)** |
| any step `ok == false` / `exitCode != 0`, or an assertion logs FAIL | **Failed (5)** |
| env unreachable, session couldn't start, or a step was un-automatable/ambiguous | **Blocked (2)** |

Reminder: do NOT use `consoleErrors` / `networkFailures` as fail signals (see §0).

### 4.5 Evidence in the result comment

The result comment for each case includes per-step outcomes plus pointers to Canary artifacts:

```json
{
  "status_id": 5,
  "comment": "Automated Canary run — 2026-06-26\nSession: TR-4521--mcp-discover-abilities-xxx\n\nStep 1: ✅ Passed\nStep 2: ✅ Passed\nStep 3: ❌ Failed — assertion 'returns ability list' did not hold\n\nTrace: npx playwright show-trace ~/.canary/sessions/TR-4521--…/trace.zip\nReport: ~/.canary/sessions/TR-4521--…/report.html\nScreenshot: <raw.githubusercontent.com SHA URL, if published>",
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

### 4.6 Failure handling

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

### 6.1 Problem with today's `e2e-qa-tester`

- Improvises WP interactions (login, nonce, REST, AJAX) from scratch — wrong ~30% of the time.
- No rich evidence: no trace, no video, no HAR — just a spec file and a screenshot URL. Debugging
  failures is blind.
- The QA plan lives only in the orchestrator's context — invisible to developer and reviewer.

### 6.2 Vision

A new `canary-e2e` agent — a **drop-in replacement** for `e2e-qa-tester` that uses Canary CLI
instead of `mcp__playwright`. Opt-in at orchestrator startup; `e2e-qa-tester` stays the untouched
default. Both share the **same JSON return contract**, so `qa-engineer` is agnostic to which ran.

### 6.3 Mode selection at orchestrator startup

During the existing calibration step the orchestrator asks one extra question:

> **E2E mode** — `playwright` (default, stable) or `canary` (experimental, richer artifacts)?

Answered once; stored as `e2e_mode`; passed in every downstream dispatch. If the issue has no
UI/browser changes, the flag is ignored.

```
orchestrator startup
  → calibration (autonomy level) + e2e_mode ("playwright" | "canary")
  → grooming → implementation → review …
  → qa-engineer  [receives e2e_mode]
      diff has UI/browser changes?
        yes + e2e_mode == "canary"     → spawn canary-e2e
        yes + e2e_mode == "playwright" → spawn e2e-qa-tester   (current default)
        no                             → skip E2E entirely
      merge E2E results into the final GitHub PR comment
```

### 6.4 `canary-e2e` agent design

- **Inputs:** `qa-plan.md` path, `e2e_mode: "canary"`, PR number, env (`E2E_URL`, `WP_USER`,
  `WP_PASS`).
- **Behaviour:** for each **P0/P1** flow in `qa-plan.md`:
  1. Record a Canary session via `canary-imagify-session-agent` (session name = the flow label,
     e.g. `P0-A: MCP abilities discovery`).
  2. Read `results.json` → build a markdown results row.
  3. Write a Playwright spec to `Tests/e2e/specs/` (translated from the flow — see §6.6).
- **P2 flows** are documented in the plan but Canary sessions are optional.
- **Publishes:** screenshots via temp-branch-commit → SHA raw URL (§6.5); trace replay command per
  session.
- **Returns:** the **same JSON shape** as `e2e-qa-tester` so `qa-engineer` needs no branching.
- **Tools:** `Bash` (drives `npx @usecanary/cli`), `Read`, `Edit`, `Write`, `Glob`, `Grep`,
  `WebFetch`. Notably **not** `mcp__playwright` — Canary is the browser driver.

### 6.5 `qa-plan.md` format (shared by qa-engineer ↔ canary-e2e ↔ e2e-qa-tester)

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

### 6.6 Artifact flow & PR comment

```
Developer machine (local only — NOT committed)
  ~/.canary/sessions/<id>/  → session.json, results.json, report.html,
                              trace.zip, video/, network.har, console.log, screenshots/

GitHub PR comment (posted by qa-engineer via canary-e2e's JSON):
  ├─ qa-plan (P0/P1/P2 markdown table)
  ├─ Canary results table (from results.json)
  ├─ Screenshot images (raw.githubusercontent.com SHA URLs)
  └─ "Trace: npx playwright show-trace <local path>" per session

GitHub Actions CI (committed by canary-e2e):
  Tests/e2e/specs/  ← Playwright specs derived from the flows (existing CI re-runs them)

Optional future: upload ~/.canary/sessions/ as CI artifact "canary-qa-<pr>"
```

**Results table format (from `results.json`):**

```markdown
### Canary QA Sessions

| Flow | Steps | Result | Trace |
|------|-------|--------|-------|
| P0-A: MCP abilities discovery | 10/10 | ✅ PASS | `npx playwright show-trace ~/.canary/sessions/p0-a--…/trace.zip` |
| P0-B: Settings page save | 5/6 | ❌ FAIL — step "save-settings" exit 1 | `npx playwright show-trace ~/.canary/sessions/p0-b--…/trace.zip` |

### Screenshots

| Step | Screenshot |
|------|-----------|
| login-admin | ![login](https://raw.githubusercontent.com/wp-media/imagify-plugin/SHA/.e2e-screenshots/p0-a-login-admin.png) |
```

**Screenshot publishing (same mechanism as today's `e2e-qa-tester`):**

```bash
cp ~/.canary/sessions/<id>/screenshots/*.png .e2e-screenshots/
git add -f .e2e-screenshots/ && git commit -m "chore(qa): Canary QA screenshots" && git push
SHA=$(git rev-parse HEAD)   # URL base: https://raw.githubusercontent.com/wp-media/imagify-plugin/$SHA/.e2e-screenshots/
git rm --cached .e2e-screenshots/*.png && git commit -m "chore(qa): remove Canary QA screenshots" && git push
```

### 6.7 Playwright spec translation (not mechanical)

`canary-e2e` writes fresh Playwright specs that pass the same assertions — it does **not** transpile
QuickJS to Node (different runtimes, different error semantics).

| Canary QuickJS | Playwright TypeScript |
|---|---|
| `browser.getPage("main")` | `page` fixture |
| `page.evaluate(async () => fetch(...))` | identical |
| `console.log("PASS: …")` | `expect(...).toBe(...)` |
| step exit 1 | thrown assertion |
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

**`canary-wp-session-agent.md`** knows only WordPress-generic patterns: `wp-login.php` login,
REST nonce via `admin-ajax.php?action=rest-nonce` (plain text, `.trim()`), authenticated REST
calls (`X-WP-Nonce`), `admin-ajax.php` POST with `_ajax_nonce`, admin-notice assertions.

**`canary-imagify-session-agent.md`** extends it with: Imagify admin URLs (settings, bulk
optimization, custom folders), ability slugs, the MCP/abilities endpoint
(`/wp-json/wp-abilities/v1/abilities`), the `imagify-abilities` fixture, Imagify selectors/notice
patterns.

The "extends" instruction at the top of the plugin agent:

```markdown
Before doing anything, read `~/.claude/agents/canary-wp-session-agent.md` in full.
Its WP fixture snippets and rules are your base. The sections below OVERRIDE or EXTEND them.
```

**Why this split:** the WP base stays lean (never learns about Imagify/WP Rocket/BackWPup); each
plugin repo owns a thin agent with only its specifics; fixing a WP pattern (e.g. nonce endpoint
change) happens once and every plugin inherits it; adding a new plugin = one new file, zero changes
to the base. Worth formalising across `wp-media` projects once the base is stable.

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

### 7.3 Session-agent trust guard (critical pipeline detail)

The Canary marketplace `session-agent`/`verify-agent` refuse to act on **relayed** confirmations —
they require a direct user message. In our pipeline these agents are invoked *transitively*
(`orchestrator → qa-engineer → canary-e2e → canary-imagify-session-agent`, or
`/testrail-run → testrail-run-agent → canary-imagify-session-agent`). The trust guard **must be
removed** in our forked `canary-wp-session-agent`/`canary-imagify-session-agent`, or every nested
spawn deadlocks waiting for a direct user confirmation that never comes.

---

## 8. Canary replay advantage — free CI reruns (Feature 2 & 3)

Recorded Canary step scripts in `session.json` replay without Claude:

```bash
npx @usecanary/cli run ~/.canary/sessions/<id>/steps/<step>.js   # zero inference
```

Implications:

- **Feature 2 — TestRail run execution:** the *first* execution of a TestRail case is Claude-driven
  (it interprets human-readable steps into a Canary script). Once recorded, that script is the
  durable automation for the case. A nightly/release CI job can **replay** all recorded scripts for
  a run — no inference, deterministic, fast — and still post fresh results + artifacts to TestRail.
  A recorded script could be associated back to its case (e.g. stored under
  `Tests/e2e/canary/TR-<case_id>/` or referenced from the case) so the next run replays instead of
  re-inferring. Only *new* or *changed* cases need Claude.
- **Feature 3 — pipeline E2E:** the per-PR Canary sessions are recorded; the committed Playwright
  specs in `Tests/e2e/specs/` already give CI a free rerun path. The Canary scripts themselves can
  additionally be replayed locally to reproduce a failure deterministically without re-driving
  through Claude.

**Phasing:**
- Phase A (initial): Claude executes every run (inference cost per run). Simplest; ship this first.
- Phase B (scale): persist recorded scripts; CI replays them for unchanged cases → zero inference
  at scale; Claude only handles new/changed cases.

Open: exact storage location and the "case → recorded script" linkage (§9).

---

## 9. Files to create / modify

**User-level** (`~/.claude/agents/` — shared across all WP plugin repos):

| File | Action | Feature | Notes |
|------|--------|---------|-------|
| `~/.claude/agents/canary-wp-session-agent.md` | Create | 2, 3 | Fork of marketplace `session-agent` + WP-generic fixtures only; **trust guard removed** for pipeline use |
| `~/.claude/agents/canary-wp-verify-agent.md` | Create (optional) | 3 | Fork of marketplace `verify-agent` + WP QA-plan format |

**Project-level** (`.claude/` — Imagify only):

| File | Action | Feature | Notes |
|------|--------|---------|-------|
| `.claude/agents/canary-imagify-session-agent.md` | Create | 2, 3 | Extends WP base; adds Imagify URLs, ability slugs, MCP endpoint, `imagify-abilities` fixture |
| `.claude/agents/canary-e2e.md` | Create | 3 | New E2E agent — same JSON contract as `e2e-qa-tester`, Canary CLI internally |
| `.claude/agents/testrail-scenario-agent.md` | Create | 1 | Analyse PR(s) → stage → publish cases; MCP-or-REST |
| `.claude/agents/testrail-run-agent.md` | Create | 2 | Fetch run → orchestrate Canary execution → post results |
| `.claude/skills/testrail-scenarios/SKILL.md` | Create | 1 | Standalone entry point for scenario generation |
| `.claude/skills/testrail-run/SKILL.md` | Create | 2 | Standalone entry point for run execution |
| `.claude/agents/qa-engineer.md` | Modify | 3 | Write `.ai/qa-plan.md`; receive `e2e_mode`; route to `canary-e2e`/`e2e-qa-tester` |
| `.claude/skills/orchestrator/SKILL.md` | Modify | 3 | Add `e2e_mode` startup prompt; pass it in every dispatch |
| `.claude/agents/e2e-qa-tester.md` | **Untouched** | 3 | Stable fallback — no changes |
| `.github/workflows/e2e.yml` | Modify (optional) | 2, 3 | Canary artifact upload / replay job if CI runs Canary |
| `Tests/e2e/` | Existing | 3 | Playwright specs still committed here — no structural change |
| `.ai/testrail/pending/` | Runtime dir | 1 | Staging YAML (cleaned on publish) |
| `.ai/testrail/index.json` | Runtime file | 1 | Dedup fallback index (only if `refs` filtering unavailable) |
| `.ai/qa-plan.md` | Runtime file | 3 | Shared QA plan |

**Environment / secrets required:**

| Var | Used by | Notes |
|-----|---------|-------|
| `TESTRAIL_USERNAME`, `TESTRAIL_API_KEY` | 1, 2 | TestRail Basic auth |
| `E2E_URL`, `WP_USER`, `WP_PASS` | 2, 3 | WP fixture login target |
| `CANARY_HOME` (maybe) | 2, 3 | Override `~/.canary` artifact root in CI (§ open question) |

---

## 10. Open questions — audit resolution (2026-06-26)

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

---

## 11. Implementation order (suggested)

1. **Shared base first:** `canary-wp-session-agent` (user) + `canary-imagify-session-agent`
   (project) with the §7.2 fixtures and the trust guard removed. Everything else depends on these.
2. **Feature 3 (pipeline E2E):** `canary-e2e` + `qa-engineer`/`orchestrator` changes. Highest
   day-to-day value; exercises the shared agents on real PRs.
3. **Feature 1 (scenario generation):** `/testrail-scenarios` + `testrail-scenario-agent` +
   dedup. Produces the cases Feature 2 consumes.
4. **Feature 2 Phase A (Claude-driven run):** `/testrail-run` + `testrail-run-agent` reusing the
   Canary session agents; post results on confirm.
5. **Feature 2 Phase B (replay):** persist recorded scripts; CI replays unchanged cases for zero
   inference at scale.
```
