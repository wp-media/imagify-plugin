---
name: testrail-review
description: Review staged TestRail scenario YAML files before publishing — checks pertinence, coverage gaps, redundancy, step clarity, smoke-test flags, and section targeting. Edits the YAML directly.
argument-hint: [filename(s) or blank for all]
---

# TestRail Review

Standalone entry point for reviewing staged TestRail scenario YAML files before they are
published. Spawns `testrail-scenario-agent` in **review mode** on whatever is staged (or a
specific subset).

## Invocation

```
/testrail-review                         → review all files in .ai/testrail/pending/
/testrail-review 1133-add-mcp-abilities.yml          → review one specific file
/testrail-review 1133-add-mcp-abilities.yml 1149-fix-capability.yml  → review a subset
```

## What to do

1. **Resolve the file list.**
   - If filenames are given as arguments, pass them to the agent.
   - If no arguments, pass no list — the agent will review everything staged.

2. **Check that there is something to review.**
   ```bash
   ls .ai/testrail/pending/ 2>/dev/null
   ```
   If the directory is empty or does not exist, tell the user there is nothing staged and stop.

3. **Spawn `testrail-scenario-agent`** in review mode, passing the file list (or "review all
   staged files" if no list). The agent will edit the YAML directly and print a review report.

4. **Relay the agent's report** verbatim, then remind the user:
   - To publish: `/testrail-scenarios publish` (reads from `.ai/testrail/reviewed/`)
   - To generate more scenarios: `/testrail-scenarios #PR`
   - To edit manually: reviewed files are in `.ai/testrail/reviewed/`

## Constraints

- This skill never edits YAML directly. All edits are the agent's.
- Never publish automatically — this skill only reviews.
