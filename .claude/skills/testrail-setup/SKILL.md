---
name: testrail-setup
description: Ground (or re-ground) the TestRail execution specs by exploring the live Imagify app — captures real locators, seed/teardown helpers, and verification criteria per feature into .claude/testrail/specs/. Supports --check for a drift report only.
argument-hint: "[feature | --check | blank for all]"
---

# TestRail Setup

Entry point for building the **grounded maps** the run agent executes against. Spawns
`testrail-explorer-agent`, which drives the live app via Canary, captures real locators +
seed/teardown + verification criteria per plugin feature, and writes/refreshes the committed
specs under `.claude/testrail/specs/`. This skill is thin — it routes and relays; all the work
is the agent's.

## Invocation

```
/testrail-setup                 → explore everything; write/refresh all feature specs
/testrail-setup <feature>       → re-ground one feature (e.g. /testrail-setup mcp-abilities)
/testrail-setup --check         → drift report only (which specs are stale vs current SHA); NO writes
```

## What to do

1. **Detect the mode from the argument.**
   - `--check` (optionally with a feature name) → drift-report mode.
   - a feature name → re-ground that one feature.
   - no argument → explore all features.

2. **Spawn `testrail-explorer-agent`**, passing the argument through verbatim
   (`all` when none was given). For `--check`, instruct it to run drift-report mode: read each
   spec's `source_files` + `derived_sha`, compare against the latest commit touching those
   files, and report STALE/FRESH **without writing anything**.

3. **Relay the agent's summary** verbatim:
   - Explore mode: which specs were written/refreshed, what locators/helpers changed, the new
     `derived_sha`, and any feature it could not ground (BLOCKED reason).
   - `--check` mode: the stale-vs-fresh table.

4. **Remind the user of next steps:**
   - The specs are committed grounding — review the diff before committing.
   - To execute a run against this grounding: `/testrail-run <run-id|milestone|active>`.
   - To re-ground a stale feature surfaced by `--check`: `/testrail-setup <feature>`.

## Constraints

- This skill never drives Canary, edits specs, or audits code directly — all of that is the
  agent's. It is an entry point, not a second copy of the agent.
- This skill never posts to TestRail. Setup only grounds specs; it never touches a run.
- `--check` must never write — it is a report only.
- Credentials live in the environment; do not prompt for them and never print them.
