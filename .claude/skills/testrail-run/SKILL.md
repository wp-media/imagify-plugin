---
name: testrail-run
description: Fetch a TestRail test run, execute every scenario via Canary, and optionally post results back to TestRail.
---

# TestRail Run

Entry point for executing a TestRail test run end-to-end. Resolves the target run, then
spawns `testrail-run-agent`, which fetches every case in the run, executes each one via
Canary browser automation (sequentially), collects pass/fail/blocked outcomes with
trace/video evidence, prints a results table, and — only after the user confirms — posts the
results back to TestRail.

## Invocation

```
/testrail-run                       → the active milestone's run (single open run)
/testrail-run --milestone 2.3.0     → resolve the run via milestone name
/testrail-run --run-id 1283         → target a run by ID directly
```

## What to do

1. **Parse the target** from the arguments:
   - `--run-id <id>` → pass the run ID straight to the agent; no resolution needed.
   - `--milestone <name>` → pass the milestone name; the agent resolves it to a run.
   - no argument → the agent uses the active milestone's open run.

2. **Spawn `testrail-run-agent`** once, passing whichever of `{run-id, milestone name, or
   "active"}` was determined. Instruct it to:
   - resolve and fetch the run's cases,
   - execute each case via Canary sequentially (never in parallel),
   - print the results table,
   - **stop and ask for confirmation** before posting anything back to TestRail.

3. **Relay the agent's results table** to the user verbatim.

4. **On the user's confirmation** ("yes" / "post them" / "select C123 C456"), re-engage the
   agent (or continue it) to post the chosen results to TestRail. Pass through any selection
   the user makes.

## Constraints

- Execution is always sequential, matching `workers: 1` in `playwright.config.ts`.
- Never post results to TestRail without explicit user confirmation.
- TestRail credentials (`TESTRAIL_USERNAME`, `TESTRAIL_API_KEY`) live in the environment;
  do not prompt for them and never print them.
- This skill never calls the TestRail API or Canary directly. All of that is the agent's.
