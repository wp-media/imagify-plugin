---
name: issue-workflow
description: Fetch a GitHub issue and run the full grooming → implementation → review → QA pipeline.
---

# Issue Workflow

## Config

The following values are injected via the orchestrator prompt — do not read any config file:
- `{TEMP_ROOT}` = `.ai`
- `{REPO}` = `wp-media/imagify-plugin`
- `{SLUG}` = `imagify`
- `{E2E_URL}` = `http://localhost:8888`
- `{E2E_BOOT}` = `bash bin/dev-start.sh`

Repository: `wp-media/imagify-plugin`

When the user asks to work on an issue by number, such as:
- `/task 123`
- `issue 123`
- `#123`

follow this workflow. The orchestrator runs **inline in this conversation** — read the
user's opening message before kicking it off, since it uses that for escalation
calibration (high autonomy / standard / high oversight).

## Tooling

Use shell commands as the primary approach. The GitHub MCP (`mcp_github_*`) may be used if it is connected — but shell is always the safe fallback and is preferred for reliability.

| Operation | Shell (primary) | GitHub MCP (if connected) |
|---|---|---|
| Issue fetch | `bash .claude/skills/issue-workflow/scripts/issue-sync.sh <N>` | `mcp_github_github_issue_read` |
| Branch creation | `bash .claude/skills/issue-workflow/scripts/make-issue-branch.sh` | — |
| Staging & committing | `git add` / `git commit` | — |
| Pushing | `git push -u origin <branch>` | — |
| PR creation | `gh pr create` | `mcp_github_github_create_pull_request` |
| CI monitoring | `gh pr checks <PR#>` | `mcp_github_github_pull_request_read` |

## Steps

1. **Extract** the issue number from the user's message.

2. **Fetch the issue** — run `bash .claude/skills/issue-workflow/scripts/issue-sync.sh <N>` (or use the MCP equivalent). Read the resulting file at `.ai/issues/<N>/issue.md`.

3. **Check for parent epics** — if `Parent Epic (GitHub)` or `Parent Epics (Task List)` has entries, sync each parent with `issue-sync.sh <epic-N>` and read those files at `.ai/issues/<epic-N>/issue.md` for context.

4. **Check if this is an Epic** — if the issue has label `epics`, Issue Type `EPIC`, or has sub-issues listed, ask the user: "Work the epic as a whole, or a specific sub-issue?" If a sub-issue is chosen, sync it and proceed with the epic context in mind.

5. **Determine base branch** — default is `origin/develop` unless the user specified otherwise.

6. **Invoke the `orchestrator` command inline** (do not spawn it as a sub-agent — it runs in this conversation context so it can read the user's intent for escalation calibration):
   > Inputs: issue number `N`, issue file `.ai/issues/<N>/issue.md`, base branch

The orchestrator command manages everything from here: calibration → grooming → spec review → dispatch → implementation → lead review → push & PR → CI → QA → finalize. It spawns the specialist agents (`grooming-agent`, `challenger`, `backend-agent`, `frontend-agent`, `release-agent`, `lead-reviewer`, `qa-engineer`, `ticket-writer`) as isolated sub-agents, but the orchestrator itself stays inline so it can surface decisions back to the user naturally.

Track progress in context; an HTML log is written at `.ai/issues/<N>/workflow-log.html` only if the Podium plugin is enabled (see orchestrator Run log section).
