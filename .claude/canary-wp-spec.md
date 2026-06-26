# Spec: Canary × WordPress — Integrated QA in the Issue Workflow

**Status:** Draft  
**Author:** Gaël Robin  
**Next step:** Deep audit session (week of 2026-06-30)

---

## 0. What is Canary

> This section exists so any future Claude session has full Canary context without searching. Read it before reading the rest of the spec.

### Engine

Canary is a **browser recording + QA platform** built on two npm packages:

| Package | Command | What it does |
|---------|---------|--------------|
| `@usecanary/cli` | `npx @usecanary/cli` | Playwright daemon + QuickJS sandbox + session lifecycle |
| `@usecanary/ui` | `npx @usecanary/ui` | Local web viewer for browsing session recordings |

**We do NOT fork or modify these packages.** They stay as `npx @usecanary/cli` from npm. What we customise is the **skill pack** — the markdown agent/skill files that drive the CLI.

### QuickJS sandbox (the step script runtime)

Each "step" is a `.js` script executed inside QuickJS — a tiny JS engine, NOT Node.js. Critical constraints:

- **No `require()` / `import`** — ESM and CJS modules both fail
- **No Node.js APIs** — no `fs`, `path`, `http`, `process.env`, etc.
- **No top-level `fetch()`** — QuickJS has no fetch; use `page.evaluate(async () => fetch(...))` to run fetch inside the browser context instead
- **No helper files** — every utility must be inlined verbatim in each step script
- **Globals available:** `browser` (Canary's Playwright wrapper), `saveScreenshot`, `console`

The `browser` object:
```js
const page = await browser.getPage("main");   // "main" is the default page name
// page is a Playwright Page — all page.goto, page.locator, page.evaluate, etc. work
```

One browser **persists across all steps in a session** — you do not log in again between steps. The browser is identified by `session.json["browser"]`.

### Session lifecycle

```
npx @usecanary/cli session start --name "Flow name" --capture trace,video,har,console
  → creates ~/.canary/sessions/<slug-id>/session.json
  → starts Playwright daemon + browser

npx @usecanary/cli run <step.js>              # repeat for each step
  → executes step in QuickJS
  → appends step to session.json["steps"]
  → saves screenshot if saveScreenshot() called

npx @usecanary/cli session end
  → writes results.json
  → renders report.html
  → saves trace.zip, video.webm, network.har, console.log
```

Session ID format: `<slug>-<random8>-<short-hash>` — e.g. `p0-a--mcp-abilities-discovery-mqubrrhk-3f7e94`

### Artifact structure

Every session directory at `~/.canary/sessions/<id>/` contains:

```
session.json      ← session metadata + step scripts (written during run)
results.json      ← step results + summary (written at session end)
report.html       ← self-contained pass/fail report (written at session end)
trace.zip         ← Playwright trace (open with npx playwright show-trace trace.zip)
video/            ← .webm recording
network.har       ← all HTTP traffic
console.log       ← browser console output
screenshots/      ← PNG per saveScreenshot() call (<step>-<random>.png)
manifest.json     ← artifact inventory
profile/          ← browser profile
```

### `results.json` schema (real example)

```json
{
  "summary": {
    "stepsPassed": 10,
    "stepsFailed": 0,
    "stepsTotal": 10,
    "commandCount": 34,
    "consoleErrors": 6,
    "networkFailures": 6
  },
  "steps": [
    {
      "name": "login-admin",
      "ok": true,
      "exitCode": 0,
      "durationMs": 3681,
      "startedAt": "2026-06-26T02:43:22.479Z",
      "script": "..."
    }
  ],
  "artifactList": [
    { "kind": "trace",      "path": "trace.zip",       "bytes": 5528027 },
    { "kind": "video",      "path": "video/xxx.webm",  "bytes": 3955579 },
    { "kind": "har",        "path": "network.har",     "bytes": 29806509 },
    { "kind": "console",    "path": "console.log",     "bytes": 3259 },
    { "kind": "screenshot", "path": "screenshots/step-name-abc.png",
      "label": "Screenshot: step-name", "step": "step-name", "bytes": 405656 }
  ]
}
```

### Key CLI commands

```bash
# Session lifecycle
npx @usecanary/cli session start --name "Flow name" --capture trace,video,har,console
npx @usecanary/cli run path/to/step.js
npx @usecanary/cli session end

# Inspect
npx @usecanary/cli session list          # list sessions
npx @usecanary/cli status                # daemon status
npx @usecanary/cli status --session <id> # specific session status

# Open the report viewer
npx @usecanary/ui                        # opens http://localhost:3030 (or nearby port)
npx @usecanary/ui --dir ~/.canary/sessions/<id>   # open specific session
```

### Skill pack (what we DO own)

The skill pack is markdown files at `~/.claude/plugins/marketplaces/canary-marketplace/`. We copy relevant agents into `.claude/agents/` and customise them for WordPress. No fork of the CLI needed.

Canary agent files of interest:
- `session-agent.md` → our fork: `canary-wp-session-agent.md`
- `verify-agent.md`  → our fork: `canary-wp-verify-agent.md`

---

## 1. Problem

The current `e2e-qa-tester` agent drives the browser via `mcp__playwright` — an interactive, session-bound tool with no artifact output. It produces Playwright specs committed to `Tests/e2e/specs/`, but:

- The agent improvises every WordPress interaction from scratch (login flow, nonce retrieval, REST calls, AJAX patterns) — getting them wrong about 30% of the time
- There is no rich local evidence: no trace, no video, no HAR — just a spec file and a screenshot URL
- The QA plan lives only in the orchestrator's context; it is not surfaced to the developer or reviewer
- Canary sessions run today are **disconnected** from the issue workflow — triggered manually, post-merge
- The `report.html` Canary produces is not linked from the GitHub PR comment — developers never see it

---

## 2. Vision

Introduce a **`canary-e2e` agent** — a drop-in replacement for `e2e-qa-tester` that uses Canary CLI instead of `mcp__playwright`. The user opts into it at orchestrator startup; the existing `e2e-qa-tester` stays untouched as the default.

Every `orchestrator` / `issue-workflow` run with `e2e_mode: "canary"` produces:

1. A **structured QA plan** (P0/P1/P2) derived from the diff — written to `.ai/qa-plan.md` so the developer and reviewer can read it
2. **Recorded Canary sessions** per P0/P1 flow — artifacts stored locally, a results summary **posted to the GitHub PR comment** (not committed to git)
3. **Playwright specs** written from the recorded flows — committed to `Tests/e2e/specs/` for CI to re-run

The WordPress login, nonce, and REST patterns are baked into reusable snippet blocks so no agent ever has to guess them again.

### Why not replace `e2e-qa-tester`?

- Non-destructive: the old agent is the safe fallback; a single word at startup rolls back
- Gradual adoption: run Canary on a few issues, compare results, decide
- Feature parity check: can run the same issue through both agents and compare output

---

## 3. Architecture

### 3.1 What "cloning Canary" means

Canary has two layers:

| Layer | What it is | Ownership |
|-------|------------|-----------|
| **Engine** (`@usecanary/cli`, `@usecanary/ui`) | npm packages — Playwright daemon + QuickJS sandbox + report renderer | Stay as `npx @usecanary/cli` — no fork |
| **Skill pack** (agents + skills markdown) | The AI instructions that write and run step scripts | **Copy into `.claude/agents/`** — we own and customise |

No separate plugin install or repo clone is needed. The CLI is already installed globally via `npx`; the skill pack files are already on disk at `~/.claude/plugins/marketplaces/canary-marketplace/`.

### 3.2 Mode selection

At orchestrator startup, during the existing calibration step, the orchestrator asks:

> **E2E mode** — `playwright` (default, stable) or `canary` (experimental, richer artifacts)?

The user answers once; the choice is stored as `e2e_mode` and passed in every downstream agent dispatch. If the issue has no UI/browser changes, the flag is ignored.

```
orchestrator startup
  → calibration (autonomy level) + e2e_mode ("playwright" | "canary")
  → ... grooming → implementation → review ...
  → qa-engineer  [receives e2e_mode in dispatch]
      detects UI/browser changes in diff?
        yes + e2e_mode == "canary"     → spawns canary-e2e
        yes + e2e_mode == "playwright" → spawns e2e-qa-tester  (current default)
        no                             → skips E2E entirely
      merges E2E results into final GitHub PR comment
```

Both `canary-e2e` and `e2e-qa-tester` share the **same JSON return contract** — `qa-engineer` is agnostic to which one ran.

### 3.3 Proposed pipeline (canary mode)

```
orchestrator  [e2e_mode: "canary"]
  └─► qa-engineer
        reads diff → generates .ai/qa-plan.md (P0/P1/P2 flows)
        posts initial "QA plan" GitHub PR comment
        └─► canary-e2e  (for UI/browser changes)
              For each P0/P1 flow in qa-plan.md:
                ├─ records a Canary session via canary-imagify-session-agent
                ├─ reads results.json → builds markdown results table
                └─ writes Playwright spec to Tests/e2e/specs/
              Publishes artifacts:
                ├─ screenshots → temp branch commit → SHA raw.githubusercontent.com URLs
                └─ "trace: npx playwright show-trace ~/.canary/sessions/<id>/trace.zip"
              Returns JSON to qa-engineer (same shape as e2e-qa-tester)
        qa-engineer posts final GitHub PR comment with:
          ├─ qa-plan (P0/P1/P2 as markdown table)
          ├─ Canary session results per flow
          └─ READY TO MERGE or blockers
```

### 3.4 New agents

Two WP agents replace the generic Canary marketplace agents. They live at different scopes:

```
~/.claude/agents/                          ← user-level, shared across ALL WP plugin repos
  canary-wp-session-agent.md              # base: login, nonce, REST, AJAX, notices
  canary-wp-verify-agent.md               # base: WP QA plan format + verify flow

.claude/agents/                            ← project-level, Imagify only
  canary-imagify-session-agent.md         # extends wp-session + Imagify specifics
```

**`canary-wp-session-agent.md`** knows only WordPress-generic patterns:
- Login via `wp-login.php`, `#user_login`, `#user_pass`, `#wp-submit`
- REST nonce via `GET admin-ajax.php?action=rest-nonce` (plain text, needs `.trim()`)
- Authenticated REST calls (`X-WP-Nonce` header via `page.evaluate`)
- `admin-ajax.php` POST with `_ajax_nonce`
- Admin notice assertion (`.notice-success`, `.notice-error`)
- WP REST HTTP method conventions

**`canary-imagify-session-agent.md`** extends the WP base with Imagify specifics:
- Imagify admin URLs (settings, bulk optimization, custom folders)
- Ability slugs and MCP endpoint (`/wp-json/wp-abilities/v1/abilities`)
- `imagify-abilities` fixture block (see section 4)
- Imagify-specific selectors and notice patterns

The "extends" instruction at the top of the plugin agent:
```markdown
Before doing anything, read `~/.claude/agents/canary-wp-session-agent.md` in full.
Its WP fixture snippets and rules are your base. The sections below OVERRIDE or EXTEND them.
```

#### Idea to consider: one WP base, N plugin agents

Keeping WP-generic patterns in `~/.claude/agents/` (user-level) and plugin-specific knowledge
in project-level `.claude/agents/` means:

- The WP base stays lean — it never needs to know about Imagify, WP Rocket, BackWPup, etc.
- Each plugin repo owns its own thin agent with only what's unique to that plugin
- Fixing a WP pattern (e.g. nonce endpoint changes) happens in one file and all plugins pick it up
- Adding a new plugin (WP Rocket) = one new file, no changes to the WP base

This is worth formalising across `wp-media` projects when the WP base is stable enough.

### 3.5 Updated existing agents

**`qa-engineer.md`** — becomes E2E-mode-aware:
- Receives `e2e_mode` from the orchestrator dispatch
- Reads the diff and spec → produces `.ai/qa-plan.md` (P0/P1/P2 flows) regardless of mode
- Posts GitHub PR comment with the QA plan as its first action
- If UI/browser changes detected: spawns `canary-e2e` (mode=canary) or `e2e-qa-tester` (mode=playwright)
- Updates the PR comment with whichever agent's results come back (same JSON shape either way)

**`e2e-qa-tester.md`** — **untouched**. Remains the stable default.

**`canary-e2e.md`** — new agent, same JSON contract as `e2e-qa-tester`:
- Receives `qa-plan.md` path + `e2e_mode: "canary"` from `qa-engineer`
- Records one Canary session per P0/P1 flow via `canary-imagify-session-agent`
- Reads `results.json` → builds markdown results table
- Publishes screenshots via temp branch commit → SHA raw URLs
- Writes Playwright specs to `Tests/e2e/specs/` (same as `e2e-qa-tester`)
- Returns the **same JSON shape** as `e2e-qa-tester` so `qa-engineer` needs no branching logic

### 3.6 Artifact flow

```
Developer machine (local only — not committed)
  ~/.canary/sessions/<id>/
    session.json       ← step scripts (for debugging)
    results.json       ← machine-readable pass/fail + artifact list
    report.html        ← self-contained HTML report (open locally)
    trace.zip          ← npx playwright show-trace trace.zip
    video/             ← .webm
    network.har        ← all HTTP traffic
    console.log        ← browser console
    screenshots/       ← per-step PNGs

GitHub PR comment (posted by qa-engineer / e2e-qa-tester):
  ├─ Canary results table (from results.json)
  ├─ Screenshot images (raw.githubusercontent.com SHA URLs)
  └─ "Trace: npx playwright show-trace <path>" (local path — for reviewer to run)

GitHub Actions CI (committed by e2e-qa-tester):
  Tests/e2e/specs/     ← Playwright specs derived from the Canary flows
  (existing CI job re-runs these specs in CI)

Optional future: upload ~/.canary/sessions/ as CI artifact "canary-qa-<pr>"
```

---

## 4. WordPress fixture snippets

Because Canary's QuickJS sandbox has no `require()`, helpers must be inlined per step script. The `canary-wp-session-agent` carries these as **canonical copy-paste blocks** in its instructions — never re-invented, always copied verbatim.

### wp-login
```js
// FIXTURE: wp-login — always step 1, browser persists for all subsequent steps
const page = await browser.getPage("main");
await page.goto("{E2E_URL}/wp-login.php", { waitUntil: "domcontentloaded" });
await page.locator("#user_login").fill("{WP_USER}");
await page.locator("#user_pass").fill("{WP_PASS}");
await page.locator("#wp-submit").click();
await page.waitForURL("**/wp-admin/**", { timeout: 10000 });
console.log("Login URL:", page.url());
console.log("Login:", page.url().includes("wp-admin") ? "PASS" : "FAIL");
```

### wp-nonce (REST) — the correct pattern from live sessions
```js
// FIXTURE: wp-nonce — use GET with credentials:same-origin; result needs .trim()
const nonce = await page.evaluate(async () => {
  const r = await fetch("/wp-admin/admin-ajax.php?action=rest-nonce", {
    credentials: "same-origin"
  });
  return (await r.text()).trim();
});
console.log("Nonce (first 10):", nonce.slice(0, 10));
```

> **Why GET, not POST:** `admin-ajax.php?action=rest-nonce` as a GET request works in modern WP
> because the action is registered to handle GET. The earlier POST-with-FormData variant also
> works but is slower. GET is canonical.

### wp-rest-get
```js
// FIXTURE: wp-rest-get — authenticated GET to WP REST API
const result = await page.evaluate(async ([url, nonce]) => {
  const r = await fetch(url, {
    headers: { "X-WP-Nonce": nonce },
    credentials: "same-origin"
  });
  return { status: r.status, body: await r.text() };
}, [url, nonce]);
console.log("Status:", result.status);
```

### wp-rest-post
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

### wp-ajax
```js
// FIXTURE: wp-ajax — POST to admin-ajax.php with _ajax_nonce
const result = await page.evaluate(async ([action, nonce, data]) => {
  const form = new FormData();
  form.append("action", action);
  form.append("_ajax_nonce", nonce);
  Object.entries(data).forEach(([k, v]) => form.append(k, String(v)));
  const r = await fetch("/wp-admin/admin-ajax.php", {
    method: "POST",
    body: form,
    credentials: "same-origin"
  });
  return { status: r.status, body: await r.text() };
}, [action, nonce, data]);
```

### wp-assert-notice
```js
// FIXTURE: wp-assert-notice — assert success/error admin notice
const notice = await page.waitForSelector(".notice-success, .notice-error", { timeout: 5000 });
const cls = await notice.getAttribute("class");
console.log(cls.includes("notice-success") ? "PASS: success notice" : "FAIL: error notice");
```

### imagify-abilities (plugin-specific — belongs in `canary-imagify-session-agent`, NOT the WP base)
```js
// FIXTURE: imagify-abilities — assert all expected slugs in WP Abilities response
const result = await page.evaluate(async () => {
  const nonceResp = await fetch("/wp-admin/admin-ajax.php?action=rest-nonce", { credentials: "same-origin" });
  const nonce = (await nonceResp.text()).trim();
  const resp = await fetch("/wp-json/wp-abilities/v1/abilities", {
    headers: { "X-WP-Nonce": nonce },
    credentials: "same-origin"
  });
  return { status: resp.status, body: await resp.text() };
});

const data = JSON.parse(result.body);
const slugs = Array.isArray(data) ? data.map(a => a.name) : [];
console.log("HTTP status:", result.status);
console.log("Total abilities:", slugs.length);
console.log("PHP Fatal:", result.body.includes("Fatal error") ? "FAIL" : "PASS");

const expected = [
  "imagify/get-settings", "imagify/update-settings", "imagify/get-account",
  "imagify/get-stats", "imagify/get-media-status", "imagify/get-nextgen-coverage",
  "imagify/optimize-media", "imagify/bulk-optimize",
  "imagify/generate-missing-nextgen", "imagify/restore-media"
];
for (const slug of expected) {
  console.log(slugs.includes(slug) ? `PASS: ${slug}` : `FAIL: ${slug} NOT FOUND`);
}
```

---

## 5. QA plan format (`.ai/qa-plan.md`)

Produced by `qa-engineer`, consumed by `e2e-qa-tester`. The `P0-A` / `P0-B` labels become Canary session names.

```markdown
## QA Plan — PR #1234

### P0 — Must pass before merge

#### P0-A: <flow name>
- **Entry:** <URL>
- **Steps:**
  1. <step>
  2. <step>
- **Assertions:**
  - <what must hold — be specific enough to write a Canary assert>
- **Risk:** <which file(s) could break this if wrong>
- **Canary session name:** `P0-A: <flow name>`

### P1 — Should pass

#### P1-A: <flow name>
(same structure)

### P2 — Nice to have / regression guard

#### P2-A: <flow name>
- P2 flows are documented but Canary sessions are optional
```

---

## 6. Canary results → GitHub PR comment

The `e2e-qa-tester` extracts `results.json` from each session and formats it as markdown. This goes into the GitHub PR comment — **not committed to git**.

### Results table format (produced from `results.json`)

```markdown
### Canary QA Sessions

| Flow | Steps | Result | Trace |
|------|-------|--------|-------|
| P0-A: MCP abilities discovery | 10/10 | ✅ PASS | `npx playwright show-trace ~/.canary/sessions/p0-a--mcp-abilities-discovery-xxx/trace.zip` |
| P0-B: Settings page save | 5/6 | ❌ FAIL — step "save-settings" exit 1 | `npx playwright show-trace ~/.canary/sessions/p0-b--settings-page-save-xxx/trace.zip` |

### Screenshots

| Step | Screenshot |
|------|-----------|
| login-admin | ![login](https://raw.githubusercontent.com/wp-media/imagify-plugin/SHA/.e2e-screenshots/p0-a-login-admin.png) |
| assert-abilities | ![abilities](https://raw.githubusercontent.com/wp-media/imagify-plugin/SHA/.e2e-screenshots/p0-a-assert-abilities.png) |
```

### How screenshots get published

Screenshots from Canary sessions are PNGs in `~/.canary/sessions/<id>/screenshots/`. The `e2e-qa-tester` copies them to `.e2e-screenshots/`, commits them to a temp commit (same as the current agent pattern), gets the SHA-based raw URL, then removes them from tracking:

```bash
# Copy Canary screenshots into the publishable location
cp ~/.canary/sessions/<id>/screenshots/*.png .e2e-screenshots/

# Publish
git add -f .e2e-screenshots/
git commit -m "chore(qa): Canary QA screenshots"
git push
SHA=$(git rev-parse HEAD)
# URL: https://raw.githubusercontent.com/wp-media/imagify-plugin/$SHA/.e2e-screenshots/<filename>

# Remove from tracking
git rm --cached .e2e-screenshots/*.png
git commit -m "chore(qa): remove Canary QA screenshots"
git push
```

---

## 7. Playwright spec translation

Each Canary step script (QuickJS) translates to a Playwright spec (Node.js TypeScript) for CI. The translation is NOT automatic — `e2e-qa-tester` writes the spec from scratch using the Canary session's step logic as reference. Key differences:

| Canary QuickJS | Playwright TypeScript |
|---|---|
| `browser.getPage("main")` | `page` fixture from `test()` |
| `page.evaluate(async () => fetch(...))` | `page.evaluate(async () => fetch(...))` — same |
| `console.log("PASS: ...")` | `expect(...).toBe(...)` |
| `process.exit(1)` | throw / assertion failure |
| No `import` | `import { test, expect } from '@playwright/test'` |
| Inlined helpers | POM methods in `Tests/e2e/pages/` |

Example translation of the `assert-all-abilities-present` Canary step:

```typescript
// Tests/e2e/specs/mcp-abilities.spec.ts
import { test, expect } from '@playwright/test';

test.describe('MCP Abilities — Wave 2', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-login.php');
    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('admin');
    await page.locator('#wp-submit').click();
    await page.waitForURL('**/wp-admin/**');
  });

  test('all Wave 2 abilities present in /wp-abilities/v1/abilities', async ({ page }) => {
    await page.goto('/wp-admin/');
    const result = await page.evaluate(async () => {
      const nonceResp = await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', { credentials: 'same-origin' });
      const nonce = (await nonceResp.text()).trim();
      const resp = await fetch('/wp-json/wp-abilities/v1/abilities', {
        headers: { 'X-WP-Nonce': nonce },
        credentials: 'same-origin'
      });
      return { status: resp.status, body: await resp.text() };
    });

    expect(result.status).toBe(200);
    const data = JSON.parse(result.body);
    expect(Array.isArray(data)).toBe(true);
    const slugs = data.map((a: { name: string }) => a.name);

    for (const slug of ['imagify/bulk-optimize', 'imagify/generate-missing-nextgen', 'imagify/restore-media']) {
      expect(slugs).toContain(slug);
    }
    expect(result.body).not.toContain('Fatal error');
  });
});
```

---

## 8. GitHub Actions integration

### Playwright CI (already exists — no change needed)

The committed specs in `Tests/e2e/specs/` continue to run via the existing Playwright CI job. Canary sessions run locally only.

### Optional: Canary artifact upload

If CI boots `wp-env` to record fresh Canary sessions in CI (vs just replaying Playwright specs), add this to the existing E2E workflow:

```yaml
- name: Upload Canary QA artifacts
  if: always()
  uses: actions/upload-artifact@v4
  with:
    name: canary-qa-${{ github.event.pull_request.number }}
    path: ~/.canary/sessions/
    retention-days: 14
```

This is optional for the initial implementation — the local-only approach is simpler.

---

## 9. Open questions for the audit

1. **Artifact location in CI:** `~/.canary/sessions/` is per-machine (on dev). If CI also needs to produce Canary sessions (not just replay Playwright specs), where do artifacts land? Path may need to be overridden via `CANARY_HOME` or equivalent env var.

2. **Spec generation strategy:** Does `e2e-qa-tester` translate the Canary step scripts into Playwright specs mechanically, or write fresh specs that pass the same assertions? The latter is safer (QuickJS ≠ Node, different error handling semantics).

3. **Session agent trust guard:** The current Canary marketplace `session-agent` and `verify-agent` refuse to act on relayed confirmations — require a direct user message. Our `canary-wp-session-agent` embedded in the pipeline must not have this guard, since it's invoked by `e2e-qa-tester`, which is invoked by `qa-engineer`, which is invoked by `orchestrator`. The guard must be removed for the pipeline variant.

4. **CI vs local Canary:** Should CI boot `wp-env` and run the Canary session scripts directly (produces fresh artifacts), or only run the committed Playwright specs (fast, no Canary dependency)? Recommendation: local-only for Canary in initial impl; CI runs Playwright specs only.

5. **WP fixture scope:** The 6 snippets in section 4 cover the core patterns seen in live Imagify sessions. Are `wp-block-editor`, `wp-media-library-upload`, or `wp-multisite` patterns needed for any planned Imagify E2E flows? Audit the current `Tests/e2e/specs/` to find patterns not yet covered.

---

## 10. Files to create / modify

**User-level** (`~/.claude/agents/` — shared across all WP plugin repos):

| File | Action | Notes |
|------|--------|-------|
| `~/.claude/agents/canary-wp-session-agent.md` | Create | Fork of marketplace `session-agent.md` + WP-generic fixtures only; coordinator trust guard removed for pipeline use |
| `~/.claude/agents/canary-wp-verify-agent.md` | Create | Fork of marketplace `verify-agent.md` + WP QA plan format |

**Project-level** (`.claude/agents/` — Imagify only):

| File | Action | Notes |
|------|--------|-------|
| `.claude/agents/canary-imagify-session-agent.md` | Create | Extends `canary-wp-session-agent`; adds Imagify admin URLs, ability slugs, `imagify-abilities` fixture |
| `.claude/agents/canary-e2e.md` | Create | New E2E agent — same JSON contract as `e2e-qa-tester`, uses Canary CLI internally |
| `.claude/agents/qa-engineer.md` | Modify | Add: write `.ai/qa-plan.md`; receive `e2e_mode` from orchestrator; route to `canary-e2e` or `e2e-qa-tester` accordingly |
| `.claude/agents/e2e-qa-tester.md` | **Untouched** | Remains the stable default — no changes |
| `.claude/skills/orchestrator/SKILL.md` | Modify | Add `e2e_mode` prompt to the startup calibration step; pass it in every agent dispatch |
| `.github/workflows/e2e.yml` | Modify (optional) | Add Canary artifact upload step if CI runs Canary sessions |
| `Tests/e2e/` | Existing | Playwright specs still committed here — no structural change |

---

## 11. Known Canary limitations (discovered in live sessions)

- **`console.log` in QuickJS counts as "consoleErrors"** if the Playwright daemon routes all console output through the error channel. Do not use `summary.consoleErrors > 0` as a FAIL signal — check `summary.stepsFailed` instead.
- **`networkFailures`** in the summary counts network events that returned 4xx/5xx — WP admin always generates some (favicon 404, etc.). Do not use `networkFailures > 0` as a FAIL signal.
- **Video recording requires `--capture video`** at session start. Omitting it means no `.webm` artifact.
- **Session must be explicitly ended** (`npx @usecanary/cli session end`) or `report.html` / `results.json` are not written. If the agent crashes mid-session, artifacts are incomplete.
- **Nonce TTL:** WP REST nonces expire after ~12 hours. Long sessions (left paused) may get 403 on authenticated calls. Re-fetch the nonce at the start of each step that makes REST calls, not just once at login.
