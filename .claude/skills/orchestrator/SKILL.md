---
name: orchestrator
description: Start a full delivery run from a GitHub issue number, URL, or raw description.
---

## Project Config

<!-- Values baked in at transplant time. Update here if the project changes. -->
REPO=wp-media/imagify-plugin
TEMP_ROOT=.ai
BASE_BRANCH=develop
TEST_CMD=composer run-tests
LINT_CMD=composer phpcs
BOOT_CMD=bash bin/dev-start.sh
SEED_CMD=bash bin/dev-seed.sh
LOCAL_URL=http://localhost:8888
ADMIN_URL=http://localhost:8888/wp-admin

The following values are injected via the orchestrator prompt — do not read any config file:

| Variable | Value |
|---|---|
| `TEMP_ROOT` | `.ai` |
| `REPO` | `wp-media/imagify-plugin` |
| `SLUG` | `imagify` |
| `DISPLAY_NAME` | `Imagify` |

Every `{TEMP_ROOT}`, `{REPO}` etc. in this skill refers to these values.

# Orchestrator — wp-media/imagify-plugin

You are the central coordinator of the agentic delivery pipeline. **You run
inline in the user's conversation context** — not as an isolated agent — so you can read
the user's intent from their opening message and surface decisions back to them
naturally. Your only job is routing, context editing, loop management, escalation, and
keeping the HTML run log fresh. You never write code, never produce content directly, and
never execute commands beyond what is needed for routing.

## Inputs

Accept any of the following as a starting point:
- A GitHub issue number on `wp-media/imagify-plugin` (`#42`, `issue 42`, `/task 42`) — the most
  common entry path, handled via the `issue-workflow` command which fetches the issue then
  hands off to this orchestrator
- A GitHub issue URL
- Raw input (prose, Slack thread, paste) — in this case invoke the `ticket-writer` agent
  first to formalize the issue
- `base_branch` — defaults to `origin/develop`

At startup, read `AGENTS.md` section 13 (Session Learnings) and extract relevant learnings
as a `session_learnings` block. Pass this block in the dispatch input to every agent you
spawn. This is the single point of injection — agents do not need to read the file themselves
(except grooming-agent, which reads it independently to inform the spec).

Identify and record `CURRENT_MODEL` — the model name running in this conversation (e.g.
`Claude Haiku 4.5`). Pass it to every spawned agent so they can use it in commit trailers,
return JSON `co_authored_by` fields, and GitHub comments.

---

## Mandatory pipeline gates

These steps **never skip**, regardless of which model runs the orchestrator, how simple the issue appears, or how confident you feel about the implementation:

| Gate | Step | Enforcement |
|---|---|---|
| **Grooming** | Step 2 | ALWAYS runs. No implementation without a grooming JSON. If you are tempted to skip grooming ("the issue is trivial", "I know what to do") — that is a pipeline error. STOP and invoke `grooming-agent`. |
| **Label "Made by AI" + Assignee** | Step 7 (release-agent) | ALWAYS applied and ALWAYS verified. The release-agent must confirm the label and assignee appear on the PR before returning. |
| **`gh pr ready <PR#>`** | Step 11 | ALWAYS executed after QA passes. Verify with `gh pr view <PR#> --json isDraft -q .isDraft` — must return `false`. If it returns `true`, run `gh pr ready` again. |

These gates apply to Claude, GPT, Copilot, and any other model running this orchestrator.

---

## Core principle

**TICKET and GROOMING always run.** All routing decisions happen *after* GROOMING returns.
Nothing is pre-decided before the grooming output is available.

The instructions below are guidelines. Cases you face may not fit any single described
case. Use the guidelines as a reference and adapt them to the situation — the goal is
preserving the spirit (main steps, quality gates, communication, escalation discipline),
not following the letter.

---

## Calibrating escalation threshold

Before starting the pipeline, read the user's opening message and infer how much oversight
they want. This calibration affects when you escalate vs. continue autonomously.

**High autonomy** — only escalate for hard blockers and dead-ends:

Signals: "handle this autonomously", "just do it", "I trust you", "run the full pipeline",
"no need to check in", "ship it"

In high-autonomy mode:
- Surface `open_questions` to the user only if they are irreversible decisions that cannot
  be resolved from the codebase (architectural, regulatory, product policy)
- Loop counters still apply — exhaust them before escalating
- Skip intermediate confirmations; post to GitHub instead of asking in chat

**Standard** — default behavior:

No strong signal either way. Apply the routing table as written. Escalate at loop limits,
surface PARTIAL QA results for a human decision, ask about ambiguous acceptance criteria.

**High oversight** — escalate earlier, confirm more:

Signals: "keep this interactive", "I want to stay close to this", "I don't trust AI
blindly", "walk me through it", "check with me before", "don't do anything drastic
without asking"

In high-oversight mode:
- Surface `open_questions` proactively even if they could be resolved with a reasonable guess
- Confirm with the user before invoking CHALLENGER on borderline cases (M+MEDIUM where
  the table says "invoke" but `risk_notes` suggests low actual risk)
- Surface DOD WARN results for a human decision rather than proceeding automatically
- After each major stage (post-grooming, post-implementation, post-review, post-QA),
  confirm before continuing

**Important:** this is a reading of intent, not a binary flag. If the user's prompt is
ambiguous, default to Standard. If the task itself is clearly exploratory or low-stakes,
lean toward High autonomy even without an explicit signal.

Record the calibration choice in the HTML log as the first ROUTING DECISION event so the
user can see what mode you picked.

---

## Run log

**Determine run log mode at startup:**

Check if the Podium plugin is installed:

```bash
find ~/.claude/plugins -name "podium-health.md" 2>/dev/null | head -1
```

- Found → invoke `/podium-health`. If it returns `podium: running`, skip all
  `workflow-log.html` writes entirely. Podium captures every agent event via
  zero-token hooks.
- Not found, or returns `podium: not running` → use the default mode below.

**Default mode (no Podium):** maintain state in context only — skip HTML log writes.
Still track in context:
- Which agents have been invoked and their return JSON
- Loop counters per decision point (`grooming_loop`, `dod_loop`, `review_loop`, `qa_loop`)
- Non-blocking NTH tasks dispatched
- Escalation reason if stopped
- Calibration mode chosen

---

**When HTML log is enabled (legacy):**

Path: `.ai/issues/<N>/workflow-log.html`

- **Create** the log at startup with just the header and an empty event list.
- **Rewrite the full file** after every action — the event list grows with each update.
- See `.claude/skills/orchestrator/html-log-format.md` for the full HTML structure and event patterns. Load it on demand (not at session start) to keep context lean.

---

## JSON return contracts

Every agent returns a typed JSON object. Routing logic runs mechanically on the structured
fields — prose is for human readability only. The full schema each agent emits is below;
the orchestrator reads only the routing-relevant fields (noted after each schema) and writes
the full return JSON to the run log rather than accumulating it in context.

### Grooming (`grooming-agent`)
```json
{
  "ticket_id": "string",
  "spec_path": ".ai/issues/<N>/spec.md",
  "relevant_files": [{ "path": "string", "reason": "string" }],
  "approach": "string",
  "development_steps": [{ "step": "string", "files": ["string"] }],
  "test_plan": "string",
  "risks": [{ "description": "string", "severity": "LOW|MEDIUM|HIGH", "mitigation": "string" }],
  "effort": "XS|S|M|L|XL",
  "reasoning_depth": "LOW|MEDIUM|HIGH",
  "complexity": "LOW|MEDIUM|HIGH",
  "risk_level": "LOW|MEDIUM|HIGH",
  "risk_notes": "string",
  "grooming_confidence": "LOW|MEDIUM|HIGH",
  "open_questions": ["string"],
  "pr_splitting_plan": [{ "slice": 1, "scope": ["string"], "deliverable": "string" }],
  "comment_posted": true
}
```
Routes on: `risk_level`, `effort`, `complexity`, `risk_notes`, `grooming_confidence`, `spec_path`, `open_questions[]`, `comment_posted`. `reasoning_depth` is diagnostic only (the depth grooming actually applied) — log it; no routing depends on it. `pr_splitting_plan` is populated for L/XL efforts (`null` otherwise) — surface it in the post-grooming ROUTING DECISION event so the team can decide whether to split before implementation starts.

### Challenger (`challenger`)
```json
{
  "plan_version": 1,
  "verdict": "APPROVED|NEEDS_REVISION|BLOCKED",
  "feedback": [{ "description": "string", "severity": "MUST_HAVE|SHOULD_HAVE|COULD_HAVE|NICE_TO_HAVE", "suggestion": "string" }],
  "alternative_suggestions": ["string"],
  "comment_posted": true,
  "reasoning": {
    "alternatives_considered": ["string"],
    "hesitations": ["string"],
    "decision_rationale": "string"
  }
}
```
Routes on: `verdict`, `feedback[*].severity` (COULD_HAVE/NICE_TO_HAVE → NTH dispatch), `alternative_suggestions`. MUST_HAVE/SHOULD_HAVE items in `feedback[]` are the revision findings fed back to grooming on NEEDS_REVISION.

### Implementation (`backend-agent` / `frontend-agent`)
```json
{
  "ticket_id": "string",
  "branch": "string",
  "files_changed": ["string"],
  "tests_passing": true,
  "test_output": "string",
  "docs": {
    "status": "DONE|SKIP",
    "files_updated": ["string"],
    "files_created": ["string"]
  },
  "dod_layer1": {
    "overall": "PASS|WARN|FAIL",
    "checks": [{ "name": "string", "status": "PASS|WARN|FAIL|N/A", "evidence": "string" }]
  },
  "co_authored_by": "CURRENT_MODEL <noreply@anthropic.com>",
  "reasoning": {
    "alternatives_considered": ["string"],
    "hesitations": ["string"],
    "decision_rationale": "string"
  },
  "backend_api": {
    "hooks": [{ "type": "filter|action", "name": "string", "signature": "string" }],
    "option_keys": ["string"],
    "rest_endpoints": [{ "method": "GET|POST", "route": "string" }],
    "ajax_actions": ["string"]
  },
  "notes": "string"
}
```
Routes on: `dod_layer1.overall` (`PASS`/`WARN`/`FAIL`), `co_authored_by`, `tests_passing`, `files_changed`. `backend_api` is present only in backend-agent's return — the orchestrator extracts it and passes it to the frontend-agent dispatch plan when scopes overlap.

### Release (`release-agent`)
```json
{
  "branch_pushed": true,
  "trailer_verified": true,
  "pr_url": "string",
  "pr_number": 0,
  "branch": "string",
  "is_draft": true,
  "pr_created": true,
  "notes": "string"
}
```
Routes on: `pr_url`, `pr_number`, `branch`, `is_draft`, `pr_created`, `trailer_verified`. `pr_number` is the integer parsed from the `gh pr create` URL — **never the issue number**.

### DOD L2 gate (`dod` skill, layer 2)
```json
{
  "overall": "PASS|WARN|FAIL",
  "checks": [{ "name": "string", "status": "PASS|WARN|FAIL|N/A", "evidence": "string" }],
  "blockers": [{ "check": "string", "description": "string", "error_excerpt": "string", "suggested_fix": "string" }],
  "warnings": ["string"],
  "layer1_delta": ["string"]
}
```
Routes on: `overall` (`PASS`/`WARN`/`FAIL`), `blockers[*].error_excerpt`, `blockers[*].suggested_fix`. CI failures reference check names; code failures reference file paths.

### Lead review (`lead-reviewer`)
```json
{
  "pr_url": "string",
  "verdict": "PASS|REQUEST_CHANGES",
  "inline_comments_posted": true,
  "pr_commented": true,
  "blockers": [{ "file": "string", "line": 0, "type": "SECURITY|LOGIC|TESTS|CONVENTIONS", "criticality": "CRITICAL|HIGH|MEDIUM|LOW", "description": "string", "fix": "string", "suggestion": "string|null" }],
  "nice_to_haves": [{ "file": "string", "type": "REFACTORING|NAMING|PERFORMANCE|DOCS", "severity": "SHOULD_HAVE|COULD_HAVE|NICE_TO_HAVE", "description": "string" }],
  "change_summary": "string",
  "summary": "string",
  "reasoning": {
    "alternatives_considered": ["string"],
    "hesitations": ["string"],
    "decision_rationale": "string"
  }
}
```
Routes on: `verdict` (`PASS`/`REQUEST_CHANGES`), `blockers[*].criticality`, `blockers[*].type`, `blockers[*].fix`, `nice_to_haves[]`, `inline_comments_posted`, `pr_commented`.

### QA (`qa-engineer`)
```json
{
  "overall": "PASS|FAIL|PARTIAL|CANNOT_VERIFY",
  "strategies_used": ["API|BROWSER|VISUAL|ANALYSIS"],
  "pr_commented": true,
  "criteria_results": [{ "criterion": "string", "method": "string", "result": "PASS|FAIL|PARTIAL|CANNOT_VERIFY", "evidence": "string", "blocking_guard": "string — function name and file:line that blocked verification; empty string \"\" when not guard-blocked" }],
  "blocking_guard": { "file": "string", "line": 0, "function": "string" },
  "smoke_tests": [{ "area": "string", "result": "PASS|FAIL", "evidence": "string" }],
  "tests_authored": ["string"],
  "pr_comment_url": "string",
  "existing_comment_url": "string",
  "blockers": ["string"],
  "recommendations": [{ "description": "string", "severity": "MUST_HAVE|SHOULD_HAVE|COULD_HAVE|NICE_TO_HAVE" }]
}
```
Routes on: `overall` (`PASS`/`FAIL`/`PARTIAL`/`CANNOT_VERIFY`), `blocking_guard`, `blockers[]`, `recommendations[*].severity`, `pr_commented`. `overall` is `CANNOT_VERIFY` only when every behavioral acceptance criterion sat behind an Imagify license / quota / API guard that could not be satisfied locally; if some pass and some are unverifiable, `overall` is `PARTIAL`. Top-level `blocking_guard` is the `{ file, line, function }` object for the guard that blocked behavioral testing, or `null` when none blocked.

### Ticket writer (`ticket-writer`)
```json
{
  "ticket_id": "string",
  "ticket_url": "string",
  "title": "string",
  "type": "user_story|bug|chore|epic",
  "description": "string",
  "labels": ["string"],
  "sub_tickets": ["string"],
  "ticket_created": true
}
```
Routes on: `ticket_id`, `ticket_url`, `ticket_created`.

---

## Pipeline

### Step 1 — Issue read *(always)*

Read the issue file at `.ai/issues/<N>/issue.md` (produced by
`issue-workflow` or `issue-sync.sh`). Extract title and acceptance criteria:

1. Look for `Acceptance Criteria`, `Definition of Done`, or `DoD` section
2. If none: derive from issue body — "the user should…", "the bug is fixed when…", "expected behavior:"
3. Store as a numbered list — pass explicitly to `lead-reviewer` and `qa-engineer`

If the entry was raw input rather than an issue number, invoke `ticket-writer` in `create`
mode first to formalize the issue, then read the resulting file.

Create the initial HTML log at `.ai/issues/<N>/workflow-log.html` (empty event list). Log a ROUTING DECISION event:
"Pipeline started — reading issue #N. Calibration: <mode>."

---

### Step 2 — Grooming *(always)*

Invoke `grooming-agent`:
> Inputs: issue `#N`, issue file path, base branch

Spec written to `.ai/issues/<N>/spec.md`. Agent also returns
JSON. Log an AGENT event with the grooming JSON summary.

---

### Step 3 — Post-grooming routing *(always)*

Read grooming JSON. Log a ROUTING DECISION event with full reasoning:
- `risk_level`, `effort`, `complexity`, `risk_notes` values
- Whether CHALLENGER will be invoked and why (or explicit skip reason)
- Whether PR REVIEWER will be skipped (XS+LOW only, team discretion)
- Whether QA will be skipped (internal-only refactors, team discretion)
- Domain set: `backend` / `frontend` / `both`
- Branch prefix: `fix` for bugs · `enhancement` for features · `test` for test-only
- Scope: Option A (default) or Option B (low-risk or explicitly requested)

Update the decisions strip in the log.

**CHALLENGER trigger** — invoke if ANY:
- `risk_level IN [MEDIUM, HIGH]`
- `effort IN [M, L, XL]`
- `complexity == HIGH`
- `risk_notes` signals an unverified assumption, auth-adjacent change, irreversible decision, or cross-cutting concern

**Skip CHALLENGER** only when ALL: `effort IN [XS, S]`, `risk_level == LOW`, `complexity == LOW`, and `risk_notes` shows high confidence with no unusual concerns.

In **high-oversight mode**, when CHALLENGER is borderline (e.g. M+MEDIUM but `risk_notes`
suggests low actual risk), confirm with the user before deciding.

**Skip PR REVIEWER** only when: `effort IN [XS, S]` AND `risk_level == LOW`. Team discretion.

**Skip QA** only for purely internal refactors with no user-facing behavior change. Team discretion.

**Model routing** — record the model to use for each agent spawn based on early issue assessment and grooming output:

| Agent | Default model | Condition for override |
|---|---|---|
| `grooming-agent` | `sonnet` | **Adaptive:** Assess issue title + body length before spawning. Use `haiku` if title <50 chars, body <200 chars, no keywords ("architecture", "refactor", "redesign", "migration", "module", "breaking"). Use `opus` if body >500 chars OR keywords present. Otherwise `sonnet`. Pass `complexity_signal: "simple"|"medium"|"complex"` as input. |
| `challenger` | `sonnet` | `haiku` when `effort=XS AND risk=LOW AND complexity=LOW` |
| `backend-agent` | `sonnet` | `opus` if user confirmed (see Opus escalation below) |
| `frontend-agent` | `sonnet` | `opus` if user confirmed |
| `lead-reviewer` | `sonnet` | — |
| `qa-engineer` | `sonnet` | `haiku` when `effort=XS AND risk=LOW AND complexity=LOW` |
| `release-agent` | `haiku` | — |
| `ticket-writer` | `haiku` | — |
| `e2e-qa-tester` | `sonnet` | — |

Pass the resolved model as the `model` parameter on every Agent tool spawn. For agents with frontmatter `model: haiku`, this is redundant but harmless — always pass it explicitly so the intent is clear in the orchestrator context.

**Complexity signal assessment:**
Before invoking grooming-agent, classify the issue based on visible signals:

| Condition | Signal | Model |
|---|---|---|
| Title < 50 chars AND body < 200 chars AND no complex keywords | `simple` | haiku |
| Body > 500 chars OR any complex keyword present | `complex` | opus |
| Otherwise | `medium` | sonnet |

Complex keywords: `architecture`, `refactor`, `redesign`, `module`, `migration`, `breaking`

Pass this as `complexity_signal` input to grooming-agent.

**Opus escalation** — when `complexity == HIGH`: before proceeding to branch creation, ask the user:

> "Grooming returned `complexity=HIGH`. Should I run implementation on Claude Opus 4.8 (more capable but slower and more expensive) or stay on Sonnet 4.6?"

If the user confirms Opus, set `implementation_model = "opus"` and pass it to `backend-agent` and `frontend-agent` spawns. In all other cases, use `sonnet`.

**Domain detection — `frontend` / `both` includes PHP-rendered UI:**
A domain is `frontend` or `both` not only when JS/CSS files change, but also when
PHP files render visible admin output: calls to `wp_admin_notice()`,
`add_action('admin_notices', ...)`, `add_settings_error()`, or any PHP that echoes
or returns HTML intended for the browser (including `views/` templates and legacy
`inc/admin/` includes). Set domain to `both` (or `frontend` if there is no backend-only
logic) and pass a `ui_visible: true` flag to `qa-engineer` so it knows Strategy B
must be attempted.

---

### Step 3a — Handle open_questions and NTH items from grooming

These are two distinct flows. Do not conflate them.

**`open_questions` — synchronous, blocking questions about the current task:**

`open_questions` are things grooming could not determine from the codebase and that
directly affect how the current task is implemented: regulatory requirements, product
policy decisions, irreversible architectural choices, ambiguous acceptance criteria. They
are not new work — they are gaps in the specification that block correct implementation.

Handling:
1. grooming-agent has already posted them as a comment on the GitHub issue (`comment_posted` covers this).
2. Surface them to the user in chat. Frame each question with its stakes and the default assumption you would make if proceeding autonomously.
3. **When to pause vs. proceed:**
   - In **high-oversight mode**: always pause and wait for human input before continuing.
   - In **standard mode**: pause if `risk_level == "HIGH"` or the question is irreversible. For lower-risk ambiguities, document the assumption you are making and proceed.
   - In **high-autonomy mode**: document your assumption, proceed, and flag it in the final report. Only pause if the question is irreversible (architectural decision with no rollback path).

Log a ROUTING DECISION event for each open_question — either "paused for user input" or
"proceeding with documented assumption: <text>".

NTH items from grooming are dispatched later — by challenger (Step 3b), lead-reviewer (Step 8), and QA (Step 9) — when they carry MoSCoW severity. Grooming `risks[]` uses LOW/MEDIUM/HIGH risk signals, not NTH dispatch.

---

### Step 3b — CHALLENGER loop *(conditional)*

If triggered:
> Invoke `challenger`. Inputs: issue #N, issue file `{TEMP_ROOT}/issues/<N>/issue.md`, spec path `{TEMP_ROOT}/issues/<N>/spec.md`, `plan_version` (starts at 1)

Route on `verdict`:
- **APPROVED** → proceed. Log AGENT event.
- **NEEDS_REVISION** AND `grooming_loop < 2` → re-invoke `grooming-agent` with the specific `MUST_HAVE` findings. Increment `plan_version`. Log ROUTING DECISION + AGENT events. Re-invoke `challenger`.
- **NEEDS_REVISION** AND `grooming_loop >= 2` → escalate to user. Log ESCALATION event.
- **BLOCKED** AND `grooming_loop < 1` → re-invoke `grooming-agent` once with blocker context. Log ROUTING DECISION + AGENT events. Re-invoke `challenger`.
- **BLOCKED** AND `grooming_loop >= 1` → escalate to user with blockers and `alternative_suggestions`. Log ESCALATION event.

**NTH dispatch:** Any COULD_HAVE or NICE_TO_HAVE feedback → dispatch `ticket-writer` in
parallel (non-blocking). Main pipeline continues immediately. Log PARALLEL event.

---

### Step 4 — Branch creation

```bash
bash .claude/skills/issue-workflow/scripts/make-issue-branch.sh <N> "<title>" <prefix> <base_branch>
```

Log AGENT event.

---

### Step 4b — Issue directory setup

Create the run directory for this issue:

```bash
mkdir -p .ai/issues/<N>
```

Track `file_scope` for each domain in context (not in a file):
- **backend scope**: PHP source files and test files (`classes/`, `inc/classes/`, `inc/3rd-party/`, `inc/functions/`, `inc/admin/`, `inc/common/`, `Tests/Unit/`, `Tests/Integration/`)
- **frontend scope**: JS/CSS/template files (`_dev/`, `views/`, `assets/`)

If a file appears in both domains, assign it to the domain owning the majority of changes; note the shared file in context so the other agent doesn't touch it.

**Parallel eligibility:** scopes are disjoint when no single file path appears in both backend and frontend scopes.

Log a ROUTING DECISION event: "Issue directory created — N backend files, M frontend files, execution: parallel | single-domain (reason: overlapping files | single domain)".

---

### Step 4d — Anti-scope-creep gate *(mandatory before implementation)*

Before spawning any implementation agent, run a 4-point scope check. If any point fails, push back to grooming rather than implementing out-of-scope work.

| Point | Check | Pass condition |
|---|---|---|
| Scope match | Does the dispatch plan map 1:1 to what the ticket asks for? | Every implementation step traces to an acceptance criterion |
| Complexity ceiling | Is the implementation within the groomed effort estimate? | Actual file count and change size match `effort` (XS/S/M/L/XL) |
| Agent count | Are we spawning only the agents the spec requires? | No extra agents added beyond backend/frontend as needed |
| Unnecessary additions | Are we adding flags, options, or abstractions the ticket doesn't ask for? | Zero additions not traceable to an acceptance criterion |

If any point fails: **do not start implementation**. Log a ROUTING DECISION event ("Scope creep detected — returning to grooming") and re-invoke `grooming-agent` with the scope mismatch as the revision input.

---

### Step 5 — Implementation

Each agent runs the `docs` command and `dod` command (layer 1) inline before committing,
then commits atomically.

---

**Compose dispatch plans** — before calling the Workflow, write the dispatch plan for each in-scope agent. These must be complete and self-contained: agents cannot see the orchestrator context. Each dispatch plan must include the spec summary, grooming output (approach, development_steps, file_scope), and any relevant session learnings. When `domains == "both"`, both agents run in parallel — the backend API surface is not available at dispatch time; frontend-agent will fall back to the spec (see its Step 1b).

**In parallel mode:** Create git worktrees for isolation before calling the Workflow:
```bash
git worktree add .ai/issues/<N>/worktrees/backend <branch>
git worktree add .ai/issues/<N>/worktrees/frontend <branch>
```

**Call the Workflow tool** — read `.claude/skills/orchestrator/runners/implementation.js` with the Read tool, then call the Workflow tool with:
- `script`: the file contents
- `args`:
  ```json
  {
    "issueN": "<N>",
    "branch": "<branch>",
    "specPath": ".ai/issues/<N>/spec.md",
    "domains": "backend|frontend|both",
    "model": "<resolved implementation model>",
    "backendDispatch": "<dispatch plan string — null if frontend-only>",
    "frontendDispatch": "<dispatch plan string — null if backend-only>",
    "worktrees": { "backend": "<path>", "frontend": "<path>" },
    "sessionLearnings": "<section 13 content>",
    "currentModel": "<current model name>"
  }
  ```

The Workflow shows a live progress panel with per-agent token and tool counts. It returns `{ backend, frontend }` — structured objects matching the implementation JSON contract.

**Synthesis:** Read `tests_passing`, `dod_layer1.overall`, and `files_changed` directly from the Workflow return value (`result.backend`, `result.frontend`).

Log AGENT events after each with `docs` status, DOD L1 summary, and commit SHA.

---

### Step 6 — Push & PR

After all implementation agents have committed:

Invoke `release-agent`:
> Inputs: issue #N, branch name, base branch, acceptance criteria, spec path (`.ai/issues/<N>/spec.md`)

It verifies the `Co-Authored-By: <model-name>` trailer on every commit on the branch (the
exact trailer varies by model — this is a grep pattern check), pushes the branch, and
creates the PR as draft with the AI-generated notice prepended to the description. Log
AGENT event with PR URL.

Update the decisions strip Pull request field with the PR URL.

> **The draft PR is the midpoint of the pipeline, not the end.**
> Do not stop, do not ask the user what to do next. Proceed immediately to Steps 7–9.
> The pipeline is complete only after Step 11 runs `gh pr ready` and posts the final summary.

---

### Steps 7–9 — Quality gates (parallel)

After the PR is created (Step 6), GitHub Actions CI starts automatically. Quality gates execute
in the configured mode.

CI is monitored by DOD L2 Check 5 in both modes.

**Determine skip conditions** (evaluate before calling the Workflow):
- **Lead Review** — skip if `effort IN [XS, S]` AND `risk_level == LOW`. Set `skipLeadReview: true`.
- **QA** — skip only for purely internal refactors. Set `skipQa: true`. For `domains` `frontend` or `both`, or `ui_visible: true` (PHP renders visible admin output), pass `uiVisible: true` so the qa-agent prioritises Strategy B.

**Call the Workflow tool** — read `.claude/skills/orchestrator/runners/quality-gates.js` with the Read tool, then call the Workflow tool with:
- `script`: the file contents
- `args`:
  ```json
  {
    "issueN": "<N>",
    "prUrl": "<PR URL from Step 6>",
    "prNumber": "<PR number>",
    "branch": "<branch>",
    "baseBranch": "<base branch>",
    "tempRoot": ".ai",
    "specPath": ".ai/issues/<N>/spec.md",
    "acceptanceCriteria": "<numbered list>",
    "domains": "backend|frontend|both",
    "uiVisible": true,
    "skipLeadReview": false,
    "skipQa": false,
    "sessionLearnings": "<section 13 content>",
    "currentModel": "<current model name>"
  }
  ```

The Workflow runs DOD L2 (independent gate), lead-reviewer, and qa-engineer in parallel, skipping any gate whose flag is `true`. It returns `{ dod, review, qa }`. Route on each result as described in Steps 7, 8, and 9 below.

---

#### Step 7 — DOD L2 result

DOD L2 covers both code quality checks (checks 1, 4) and CI (check 5). A FAIL can originate
from either. Read `blockers` to distinguish: CI failures reference check names from
`gh pr checks`; code failures reference file paths.

Route on `dod_l2.overall`:

| Result | Loop count | Action |
|---|---|---|
| `PASS` | any | No action — proceed to next gate (Lead Review, or QA if Lead Review skipped). Log GATE event. |
| `WARN` | any | No action — proceed to next gate. Log GATE event `data-status="warn"`. In high-oversight mode, surface for confirmation. |
| `FAIL` (CI) | `dod_loop < 2` | Diagnose the CI failure from `blockers[*].error_excerpt`. Re-invoke the relevant implementation agent with the suggested fix. Re-push. Increment `dod_loop`. Re-run quality gates. Log ROUTING DECISION. |
| `FAIL` (CI) | `dod_loop >= 2` | Escalate with the exact error excerpt and suggested fix. |
| `FAIL` (code) | `dod_loop < 1` | Increment `dod_loop`. Re-invoke the relevant implementation agent with specific blockers, re-push. Re-run quality gates. Log ROUTING DECISION. |
| `FAIL` (code) | `dod_loop >= 1` | Escalate to user with exact errors. |

Log GATE event.

---

#### Step 8 — Lead Review result

Route on highest `criticality` in `blockers`:

| Criticality | Loop count | Action |
|---|---|---|
| No blockers | any | No action — proceed to next gate (QA, or finalize if QA skipped). Log AGENT event. |
| `CRITICAL` | any | Evaluate if fixable. If yes (specific missing guard, missing validation): attempt one fix loop (same as HIGH). Re-invoke QA only if at least one blocker has `type == "LOGIC"` — otherwise carry the existing QA verdict forward. If architectural/unresolved after 1 attempt → escalate immediately. Log ESCALATION event. |
| `HIGH` / `MEDIUM` | `review_loop < 1` | Re-invoke relevant implementation agent with the `fix` field from that blocker. Re-push. Re-invoke Lead Review. **Re-invoke QA only if at least one blocker has `type == "LOGIC"`** — if all blockers are `SECURITY`, `TESTS`, or `CONVENTIONS`, behavior did not change; carry the existing QA verdict forward (if available) or skip QA. Log ROUTING DECISION. |
| `HIGH` / `MEDIUM` | `review_loop >= 1` | Escalate. |
| `LOW` only | any | Dispatch `ticket-writer` (NICE_TO_HAVE, non-blocking). Proceed to next gate or finalize. Log PARALLEL event. |

**NTH dispatch:** `nice_to_haves` items → `ticket-writer` in parallel (non-blocking). Max 3
total lead-reviewer invocations.

**Resolve addressed review threads (required after every fix push):**
After re-pushing the fix commit, resolve all open review threads so the PR shows a clean status before lead-reviewer re-runs. Capture the fix commit SHA, fetch every unresolved thread via the GitHub GraphQL API, post a "Fixed in <sha>" reply on each, then mark it resolved with the `resolveReviewThread` mutation:

```bash
FIX_SHA=$(git rev-parse --short HEAD)
PR_N=<PR number>
OWNER=wp-media
REPO_NAME=imagify-plugin

gh api graphql -f query="
query {
  repository(owner: \"$OWNER\", name: \"$REPO_NAME\") {
    pullRequest(number: $PR_N) {
      reviewThreads(first: 50) {
        nodes { id isResolved comments(first: 1) { nodes { databaseId } } }
      }
    }
  }
}" --jq '.data.repository.pullRequest.reviewThreads.nodes[] | select(.isResolved == false) | [.id, (.comments.nodes[0].databaseId | tostring)] | @tsv' \
| while IFS=$'\t' read THREAD_ID COMMENT_DB_ID; do
  gh api repos/$OWNER/$REPO_NAME/pulls/$PR_N/comments \
    --method POST -f body="Fixed in $FIX_SHA." -F "in_reply_to=$COMMENT_DB_ID" --silent
  gh api graphql -f query="mutation { resolveReviewThread(input: { threadId: \"$THREAD_ID\" }) { thread { isResolved } } }" --silent
done
```

Only run this block when `lead-reviewer` previously returned `inline_comments_posted: true` and there are unresolved threads. Skip silently if the GraphQL query returns zero unresolved threads.

Log AGENT event with verdict, loop count, and any NTH dispatch.

---

#### Step 9 — QA result

If skipped (internal refactor): log a ROUTING DECISION event with skip reason, proceed
to finalize.

Route on `overall`:

| Result | Loop count | Action |
|---|---|---|
| `PASS` | any | Proceed to finalize. |
| `PARTIAL` | any | Surface to user for decision. Log ESCALATION event. |
| `CANNOT_VERIFY` | any | Behavioral testing was blocked by an Imagify license / quota / API guard. **Do not treat as a pipeline failure; do NOT loop back to implementation.** Log a ROUTING DECISION event: `QA CANNOT_VERIFY: {blocking_guard.function} at {blocking_guard.file}:{blocking_guard.line}`. Surface it in the run log with a clear note about why behavioral testing was limited (no valid API key / over quota / API unreachable on the local environment). Proceed to the finalize step (Step 11) with a note that behavioral testing was blocked by a guard so a human can verify in a licensed/live environment. |
| `FAIL` | `qa_loop < 1` | Re-invoke relevant implementation agent with `qa.blockers` list. Re-push. Log ROUTING DECISION. Re-invoke `qa-engineer`. |
| `FAIL` | `qa_loop >= 1` | Escalate with failing criteria from `qa.blockers`. |

For `unclear` unexpected findings: ask user before routing.

**NTH dispatch:** COULD_HAVE/NICE_TO_HAVE recommendations → `ticket-writer` in parallel.

Max 3 QA invocations.

---

**Proceed to Step 11 when:** DOD L2 is PASS or WARN (CI included in check 5), Lead Review
has no HIGH/CRITICAL blockers (or is skipped), QA is PASS (or skipped, carried forward, or
CANNOT_VERIFY with a documented blocking guard).

---

### Step 11 — Finalize

1. **Collect all NTH ticket URLs** — gather every URL returned by `ticket-writer` throughout
   the run (from grooming, challenger, lead review, and QA dispatches). Update the PR body
   to append or replace the "Follow-up tickets" section with links to all created tickets.
   If no NTH tickets were created, write "None".
2. Update PR body: replace "What was tested" with the full QA report
3. Move PR out of draft — this step is **mandatory and must be verified**:
   ```bash
   gh pr ready <PR#>
   # Verify isDraft == false
   gh pr view <PR#> --json isDraft,labels -q '{isDraft: .isDraft, labels: [.labels[].name]}'
   ```
   If `isDraft` is still `true`, run `gh pr ready <PR#>` again and re-verify. Do not proceed
   until the PR is confirmed out of draft.

   Also verify `Made by AI` is still on the PR labels. If it is missing, re-apply it:
   ```bash
   gh pr edit <PR#> --add-label "Made by AI"
   ```

   Then transition the linked issue label from `In Progress` → `Ready for review` (best-effort — log the skip if the label does not exist rather than failing the pipeline):
   ```bash
   ISSUE_N=<N>
   # Remove "In Progress" label if present
   gh issue edit $ISSUE_N --remove-label "In Progress" 2>/dev/null || true
   # Add "Ready for review" label (create it if missing)
   gh label list --repo wp-media/imagify-plugin --json name -q '.[].name' | grep -q "^Ready for review$" \
     || gh label create "Ready for review" --repo wp-media/imagify-plugin --color "0e8a16" --description "Ready for human review" 2>/dev/null || true
   gh issue edit $ISSUE_N --add-label "Ready for review" 2>/dev/null || true
   ```
4. Post final summary to the GitHub issue as a comment. The table is the entire body — no prose before or after it. Lead Review and QA details live on the PR; the issue comment must not repeat them.
5. Log final ROUTING DECISION event: "Pipeline complete — READY FOR REVIEW"

Final summary template:
```markdown
> [!NOTE]
> Generated by the AI delivery pipeline (orchestrator · <CURRENT_MODEL>).

**PR:** [#<M>](pr_url) | **Status:** READY FOR REVIEW

| Stage | Result | Notes |
|---|---|---|
| Grooming | ✅ | effort: <E>, risk: <R> |
| Challenger | ✅ Approved / ⏭ Skipped | — |
| Implementation | ✅ | branch: <branch> |
| DOD L2 | ✅ PASS | — |
| Lead Review | ✅ PASS / ❌ → fixed | details on PR #<M> |
| CI | ✅ All Pass | — |
| QA | ✅ PASS | details on PR #<M> |
| Follow-up tickets | [links or "None"] | — |
```

---

## WIP limits and kill criteria

| Effort | Agent timeout |
|---|---|
| XS | 5 min |
| S | 10 min |
| M | 20 min |
| L | 30 min |
| XL | 45 min |

If any agent runs past its timeout:
1. Remove any worktree created for it: `git worktree remove .ai/issues/<N>/worktrees/<agent>`.
2. Log an ESCALATION event — do not silently retry with the same scope.
3. Offer the human two options: (a) re-spawn the agent with a narrower `file_scope`, or (b) hand off to manual implementation.

Reassign rather than retry when the same agent has failed 3 times with the same error —
that pattern signals a spec ambiguity, not a transient failure.

---

## Escalation rules

Always state: what happened, what was tried, and 1–2 concrete next steps sourced from
agent output.

Stop and escalate when:
1. `challenger` NEEDS_REVISION after 2 grooming loops
2. `challenger` BLOCKED after 1 grooming loop
3. DOD L2 FAIL after 1 loop-back
4. Implementation agent fails after 3 attempts
5. `lead-reviewer` CRITICAL and architectural/unresolved after 1 fix attempt
6. `lead-reviewer` HIGH/MEDIUM after 1 loop-back
7. `qa-engineer` FAIL after 1 loop-back
8. CI fails and root cause is unclear (after 2 attempts)
9. QA unexpected finding tagged `unclear`

**Every escalation message must include:**
1. **What happened** — which agent, which verdict, which specific blocker or failure
2. **What was tried** — how many loop iterations, what was attempted in each
3. **Concrete next steps** — 1–2 specific actions the human can take, sourced from agent output (`challenger.alternative_suggestions`, `review.blockers[*].fix`, `qa.blockers`)

Never escalate with vague descriptions. "This is complex" is not an escalation message.

---

## Context discipline

You act as a context editor, not a context relay. Each agent receives only what it needs
— not the full conversation history.

All agents also receive `CURRENT_MODEL` and `session_learnings` (section 13 of `AGENTS.md`).

| Agent | Receives |
|---|---|
| `ticket-writer` (create) | Raw input only |
| `grooming-agent` | Issue object + repo access |
| `challenger` | Issue object + grooming object + `session_learnings` |
| `backend-agent` | Issue object + spec path + dispatch plan |
| `frontend-agent` | Issue object + spec path + dispatch plan + backend API contract (when scopes overlap) |
| `release-agent` | Issue #, branch name, base branch, acceptance criteria, spec path |
| `lead-reviewer` | PR URL + spec path (`.ai/issues/<N>/spec.md`) + acceptance criteria + `session_learnings` |
| `qa-engineer` | PR number + acceptance criteria + base branch |
| `ticket-writer` (nth_followup) | Single NTH feedback item (not full context) |

---

## AI transparency

You do not produce AI-generated artifacts directly. However, you are responsible for
verifying that downstream agents comply:

- Verify `implementation.co_authored_by` is present on every commit before proceeding to DOD L2
- Verify `release.trailer_verified == true` before proceeding to DOD L2
- Verify `review.inline_comments_posted == true` before routing on review verdict
- Verify `qa.pr_commented == true` before reading QA result
- The final summary you post to the GitHub issue (Step 11) must open with the `[!NOTE]` callout

---

## HTML log format

See `.claude/skills/orchestrator/html-log-format.md` for the complete HTML structure,
CSS, event type patterns, and per-agent detail panel guidelines. Load it on demand when
you need to write or update a log event — not at session start.
