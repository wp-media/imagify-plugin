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

Canary writes to `~/.canary/sessions/<id>/` initially. Our agents relocate each session to `.ai/{issue_N}/canary/<id>/` after `session end` and leave a symlink at the original path (so `npx @usecanary/ui` still works). The directory structure is the same either way:

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

## 2. Architecture

### 2.1 E2E mode routing

`e2e_mode` is set at orchestrator startup and passed to `qa-engineer`:

```
qa-engineer [receives e2e_mode]
    diff has UI/browser changes?
      yes + e2e_mode == "canary"     → canary-e2e
      yes + e2e_mode == "playwright" → e2e-qa-tester (default, untouched)
      no                             → skip E2E
```

Both agents return the same JSON contract — `qa-engineer` is agnostic to which ran.

### 2.2 Agent inheritance

```
~/.claude/agents/canary-wp-session-agent.md    ← user-level: login, nonce, REST, AJAX, notices
.claude/agents/canary-imagify-session-agent.md ← project: Imagify URLs, selectors, abilities
```

The plugin agent reads the WP base as its first instruction (EXTEND, not replace).

### 2.3 Artifact locations

Sessions are relocated from `~/.canary/sessions/<id>/` to `.ai/{N}/canary/<id>/` after `session end`; a symlink at the original path keeps `npx @usecanary/ui` working.

```
.ai/{N}/canary/<id>/
  results.json   ← step results + verdict
  report.html    ← self-contained HTML report
  trace.zip      ← npx playwright show-trace trace.zip
  video/         ← .webm
  network.har    ← HTTP traffic
  console.log    ← browser console
  screenshots/   ← per-step PNGs (daemon auto-captures one per step)
```

GitHub PR comment: Canary results table + screenshot SHA URLs + trace replay command per session.
`Tests/e2e/specs/` — Playwright specs committed by `canary-e2e` (existing CI re-runs them).

---

## 3. WordPress fixture snippets

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

## 4. QA plan format (`.ai/qa-plan.md`)

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

## 5. Canary results → GitHub PR comment

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

---

## 6. Playwright spec translation

`canary-e2e` writes fresh TypeScript specs — never a mechanical transpile of QuickJS steps:

| Canary QuickJS | Playwright TypeScript |
|---|---|
| `browser.getPage("main")` | `page` fixture from `test()` |
| `page.evaluate(async () => fetch(...))` | identical |
| `console.log("PASS: ...")` | `expect(...).toBe(...)` |
| No `import` | `import { test, expect } from '@playwright/test'` |
| Inlined helpers | POM methods in `Tests/e2e/pages/` |

---

## 7. Known Canary limitations (discovered in live sessions)

- **`console.log` in QuickJS counts as "consoleErrors"** if the Playwright daemon routes all console output through the error channel. Do not use `summary.consoleErrors > 0` as a FAIL signal — check `summary.stepsFailed` instead.
- **`networkFailures`** in the summary counts network events that returned 4xx/5xx — WP admin always generates some (favicon 404, etc.). Do not use `networkFailures > 0` as a FAIL signal.
- **Video recording requires `--capture video`** at session start. Omitting it means no `.webm` artifact.
- **Session must be explicitly ended** (`npx @usecanary/cli session end`) or `report.html` / `results.json` are not written. If the agent crashes mid-session, artifacts are incomplete.
- **Nonce TTL:** WP REST nonces expire after ~12 hours. Long sessions (left paused) may get 403 on authenticated calls. Re-fetch the nonce at the start of each step that makes REST calls, not just once at login.
