---
name: testrail-run
description: Fetch a TestRail test run, execute every scenario via Playwright, and optionally post results back to TestRail.
---

# TestRail Run

Entry point for executing a TestRail test run end-to-end. Resolves the target run, then
spawns `testrail-run-agent`, which provisions (or verifies) the QA environments, fetches
every case in the run, executes each one via Playwright (sequentially — one `.spec.ts` per
case, **reused from the committed cache** `Tests/e2e/testrail-cases/` when the case is
unchanged, translated fresh otherwise), keeps `.ai/testrail/<run>/results.md` updated
case-by-case, and — only after the user confirms — posts the results back to TestRail and
proposes CI promotion candidates.

The environments are ephemeral Docker containers managed by `bin/qa-env.sh` (one nginx, one
apache — built from `bin/build-zip.sh`'s `imagify.zip`). The agent provisions them itself at
the start of the run and **tears them down at the end** — they never outlive the run.

## Invocation

```
/testrail-run                              → the active milestone's run (single open run)
/testrail-run --milestone 2.3.0           → resolve the run via milestone name
/testrail-run --run-id 1283               → target a run by ID directly
/testrail-run --run-id 1283 --cases 155,156,174,14169   → execute only these case IDs
```

`--cases` accepts a comma-separated list of **case IDs** (without the `C` prefix). When
provided, the agent fetches all tests in the run but executes **only** the listed cases —
all others are skipped. Status filter (Untested-only) still applies within the selection
unless overridden by the user.

## What to do

1. **Parse the target** from the arguments:
   - `--run-id <id>` → pass the run ID straight to the agent; no resolution needed.
   - `--milestone <name>` → pass the milestone name; the agent resolves it to a run.
   - no argument → the agent uses the active milestone's open run.
   - `--cases <ids>` → pass the comma-separated case ID list to the agent; it will filter
     the fetched test list to only those IDs before executing.

2. **Spawn `testrail-run-agent`** once, passing whichever of `{run-id, milestone name, or
   "active"}` was determined, and the `--cases` filter if provided. Instruct it to:
   - resolve and fetch the run's cases (then filter to `--cases` list if given),
   - execute each case via Playwright sequentially (never in parallel),
   - publish the live results dashboard,
   - **stop and ask for confirmation** before posting anything back to TestRail.

3. **If the agent stops with a coverage question** (Step 2b — one or more TestRail sections
   have no grounded spec), relay its list of affected sections/case-counts verbatim and ask
   the user: generate the missing spec(s) now, mark those cases BLOCKED and continue, or
   select per-section.
   - **generate** (all or selected) → for each named feature, spawn `testrail-explorer-agent`
     with that feature name (the slug the run agent derived from the section name, e.g.
     `media-library`). Wait for it to finish grounding, then re-invoke `testrail-run-agent` on
     the **same run/case selection** as the original call — it will now resolve those sections
     normally instead of blocking them.
   - **block** (all or selected) → re-engage `testrail-run-agent` telling it to proceed with
     those sections marked BLOCKED and execute the rest.
   - **select** → split the list per the user's answer and apply both branches above.

4. **Relay the agent's summary and the results file path**
   (`.ai/testrail/<run>/results.md`) to the user verbatim.

5. **Relay the spec-disposition question.** At end of run the agent lists what it produced
   under `Tests/e2e/testrail-cases/` and shortlists CI promotion candidates. Relay the
   shortlist and the question (promote / keep all / delete specific) verbatim — the specs
   are never deleted without the user choosing so.

6. **On the user's confirmation** ("yes" / "post them" / "select C123 C456"), re-engage the
   agent (or continue it) to post the chosen results to TestRail. Pass through any selection
   the user makes.

7. **If the agent stopped on its turn budget**, it wrote a partial results file — tell the
   user the run is resumable by re-invoking `/testrail-run` with the same target (the agent
   skips already-recorded cases).

## Constraints

- Execution is always sequential, matching `workers: 1` in `Tests/e2e/testrail.config.ts`.
- Never post results to TestRail without explicit user confirmation.
- Never delete anything under `Tests/e2e/testrail-cases/` — that is user-gated in the
  agent's disposition step.
- TestRail credentials (`TESTRAIL_USERNAME`, `TESTRAIL_API_KEY`) live in the environment or
  `.ai/settings.local.json`; do not prompt for them and never print them.
- This skill never calls the TestRail API, Playwright, or Docker directly. All of that is
  the agent's.
