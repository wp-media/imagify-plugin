# TestRail Release Automation — Brainstorm & Plan

## Goal

Automate the TestRail side of release QA for Imagify:
1. **Case generation** — analyze what's in a release, generate test scenarios, stage for human review, push to TestRail.
2. **Case execution** — fetch a test run for a given release, run every case via browser automation, mark pass/fail in TestRail.

QA is currently a bottleneck because coverage is limited to "what changed." This system aims to run the full suite automatically, expanding coverage without expanding human time.

---

## Skill 1: `/testrail-release-setup`

### Flow

```
1. git log v{last_tag}..HEAD  →  list merged PRs
2. For each PR: check TestRail for existing cases (deduplication)
3. Agent analyzes new PRs only  →  generates staged test cases
4. Write to .ai/testrail/pending/ (one YAML file per PR/feature)
5. Print summary to user
6. Prompt: "Proceed to create these cases in TestRail? (y/n)"
7. On confirm:
   a. Create new sections if needed
   b. POST cases to TestRail (with refs = PR URL)
   c. Print created case IDs
   d. Clean .ai/testrail/pending/
```

### Deduplication

**Strategy:** use TestRail's `refs` field as the link between a PR and its cases.

- When a case is created, `refs` is set to the GitHub PR URL (e.g. `https://github.com/wp-media/imagify-plugin/pull/1133`).
- On the next run, for each PR in scope, query: `GET /get_cases/3?refs=<pr_url>`
- If cases are found → skip that PR. Print: `⏭ Skipped PR #1133 — 4 cases already exist`
- If no cases found → generate and stage.

**Edge cases:**
- PR behavior changed after initial test creation → `--force-pr=1133` flag regenerates and stages new cases for that PR (does not delete old ones — manual cleanup in TestRail).
- PR was a pure refactor with no tests → skip silently (agent decides during analysis).

> Note: verify that TestRail's `get_cases` API supports `refs` filtering. If not, fallback: query all cases in the relevant section and match by title prefix or a stored local index at `.ai/testrail/index.json` (persists between runs, not cleaned with `pending/`).

### Test Case Generation (per PR)

The agent reads the PR description, diff summary, and linked issue spec. It generates cases covering:

- **Happy path** — standard user, default settings, expected flow
- **Happy path variants** — relevant settings combinations (e.g. WebP on/off, different plans)
- **Missing prerequisites** — no API key, quota exceeded, wrong plan/license
- **Network/API failures** — timeout, 5xx, malformed response
- **Edge cases** — empty state, max limits, unexpected data
- **Permission levels** — admin vs editor vs subscriber if relevant
- **Plugin conflicts** — relevant 3rd party plugins if the change touches integration points
- **Regression guard** — at least one case checking that the previous behavior still works if this is a fix

The more automatable the test, the better — more cases = more Playwright coverage later. No artificial limit on number of cases per PR.

Each case is also evaluated: **is this a smoke test?** (`custom_smoketest: true`) if it covers a critical path that should always pass regardless of release.

### Staging File Format (`.ai/testrail/pending/<pr-slug>.yml`)

```yaml
pr: 1133
pr_url: https://github.com/wp-media/imagify-plugin/pull/1133
pr_title: "Add MCP + Abilities to Imagify"
section_id: 7685          # existing section (API Requests)
new_section: "MCP Abilities"  # subsection to create under section_id (null if not needed)

cases:
  - title: "MCP - Discover abilities - happy path"
    smoke_test: true
    preconditions: |
      - Imagify plugin active
      - Valid API key configured
    steps:
      - action: "Navigate to Settings > Imagify. Ensure API key is saved."
        expected: "Settings page loads. API key field shows a valid key."
      - action: "Open Claude Desktop or an MCP-compatible client. Connect to the Imagify MCP server."
        expected: "Connection succeeds. No error."
      - action: "Call the discover-abilities tool."
        expected: "Returns a list of available abilities (e.g. optimize-image, bulk-optimize)."

  - title: "MCP - Discover abilities - invalid API key"
    smoke_test: false
    preconditions: |
      - Imagify plugin active
      - Invalid or missing API key
    steps:
      - action: "Connect MCP client. Call discover-abilities with no valid API key set."
        expected: "Returns an error response indicating authentication failure."
```

### Section Mapping

The agent picks the closest existing section. If none fits, it creates a new subsection under `Regression`.

| Feature area | Section to use |
|---|---|
| MCP / Abilities | Regression > API Requests (7685) → new: MCP Abilities |
| Settings | Regression > Settings (32) → appropriate subsection |
| Bulk optimization | Regression > Bulk Optimization (3766) |
| Media library | Regression > Media library (4976) |
| WebP/AVIF | Regression > Settings > optimization > next-gen |
| Smoke tests | Regression > Smoke test (4525) (additional flag, not separate section) |
| New feature, no match | Create new subsection under Regression (33) |

---

## Skill 2: `/testrail-release-process <version>`

### Flow

```
1. Fetch milestone by version name  →  GET /get_milestones/3
2. Get test run(s) for that milestone  →  GET /get_runs/3?milestone_id=<id>
3. Get all test cases in run  →  GET /get_tests/<run_id>
4. For each case (in parallel where possible):
   a. Read preconditions + steps (custom_steps_separated)
   b. Claude drives browser via Playwright MCP (wp-env)
   c. Per step: execute, observe, capture screenshot
   d. Determine: pass / fail / blocked
5. POST results to TestRail  →  POST /add_result_for_case/<run_id>/<case_id>
   - Include step-level results and screenshot URLs as comment
6. Print summary: X passed, Y failed, Z blocked
```

### Execution Environment

- `wp-env` (local or CI) — consistent, reproducible
- Local: `npx wp-env start` before running the skill
- CI: already part of the pipeline
- Screenshots stored temporarily, URLs included in TestRail result comment as evidence

### Result Posting

```json
{
  "status_id": 1,    // 1=Passed, 5=Failed, 2=Blocked
  "comment": "Automated run — 2026-06-26\n\nStep 1: ✅ Passed\nStep 2: ✅ Passed\nStep 3: ❌ Failed — element not found\n\n[Screenshot](url)",
  "elapsed": "45s"
}
```

### Failure handling

- If a step fails → case marked failed, remaining steps skipped, failure details captured
- If wp-env is unreachable → case marked blocked with reason
- If Claude can't interpret a step → case flagged for manual review (not marked failed)

### Canary integration (future)

- Phase 2a (now): Claude executes every time (inference cost per run)
- Phase 2b (later): Canary records the Playwright script on first execution → subsequent CI runs replay without Claude → zero inference cost at scale

---

## TestRail API Reference

**Base URL:** `https://wpmediaqa.testrail.io/index.php?/api/v2/`  
**Auth:** Basic (`TESTRAIL_USERNAME:TESTRAIL_API_KEY`)  
**Project ID:** 3  
**Suite ID:** 3  
**Step template ID:** 2  

| Action | Endpoint |
|---|---|
| Find cases by PR URL | `GET /get_cases/3?refs=<url>` |
| List sections | `GET /get_sections/3&suite_id=3` |
| Create section | `POST /add_section/3` |
| Create case | `POST /add_case/<section_id>` |
| List milestones | `GET /get_milestones/3` |
| List runs for milestone | `GET /get_runs/3?milestone_id=<id>` |
| Get tests in run | `GET /get_tests/<run_id>` |
| Post result | `POST /add_result_for_case/<run_id>/<case_id>` |

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
    {
      "content": "Navigate to Settings > Imagify.",
      "expected": "Settings page loads."
    }
  ]
}
```

**Known section IDs:**
```
Regression (33)
  ├── Settings (32)
  │   ├── general settings (7689)
  │   └── optimization (7690)
  │       ├── next-gen (7694) → Webp (7697), Avif (7698)
  │       └── file optimization (7695)
  ├── Smoke test (4525)
  ├── API Requests (7685)       ← MCP goes here
  ├── Media library (4976)
  ├── Bulk Optimization (3766)
  ├── 3rd party compatibility (4980)
  │   ├── Woocommerce (7686)
  │   └── WP Rocket (7687)
  ├── Action scheduler (4975)
  └── Promotions (1082)
```

---

## Open Questions / Decisions Needed

1. **`refs` API filter** — confirm `GET /get_cases?refs=<url>` works in this TestRail version. If not, use local index file.
2. **Section creation** — agent creates sections automatically or always proposes in the staging file for human review? (Safer: propose in YAML, create on confirm.)
3. **Multiple runs per milestone** — if a milestone has multiple test runs (e.g. smoke + full), which one does `/testrail-release-process` target? Flag: `--run-id=1281` or pick latest.
4. **CI vs local for Phase 2** — does Phase 2 run as a skill locally, or as a CI job triggered at release time?
5. **Mixpanel/analytics changes** — pure tracking changes with no user-visible behavior: skip test generation or generate a minimal "event fires" case?

---

## Implementation Order

1. **Phase 1a** — `/testrail-release-setup` (full flow: git → analyze → stage → confirm → create)
2. **Phase 1b** — deduplication via `refs` field
3. **Phase 2a** — `/testrail-release-process` (Claude executes, posts results)
4. **Phase 2b** — Canary integration for script recording + CI replay
