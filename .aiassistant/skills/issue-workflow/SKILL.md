---
name: issue-workflow
description: Work on a GitHub issue by number for wp-media/imagify-plugin. Sync the issue locally, analyze it, create a branch, implement minimal changes, and prepare a PR draft.
---

# Issue Workflow

Repository: `wp-media/imagify-plugin`

When the user asks to work on an issue by number, such as:
- `/task 123`
- `issue 123`
- `#123`

follow this workflow:

1. Extract the issue number.
2. Run `.aiassistant/skills/issue-workflow/scripts/issue-sync.sh <issue-number>`.
3. Read `.TemporaryItems/Issues/imagify-plugin/issues/<issue-number>.md`.
4. If `Parent Epic (GitHub)` or `Parent Epics (Task List)` has entries, sync each epic with `.aiassistant/skills/issue-workflow/scripts/issue-sync.sh <epic-number>` and read those files for context (this usually means the current issue is a subtask).
5. If the issue looks like an Epic (label `epics`, Issue Type = `EPIC`, Project field `Type` = `EPIC`, or `Sub-issues (GitHub)`/`Sub-issues (Task List)` has entries), ask whether to work the Epic as a whole or a specific sub-issue. If a sub-issue is chosen, run `.aiassistant/skills/issue-workflow/scripts/issue-sync.sh <sub-issue-number>` and proceed with the Epic context in mind.
6. If relationships are unclear or missing (including Issue Type being `unknown` because Issue Types are disabled, or Project `Type` being `unknown` because the issue is not in a Project or access is missing), proceed as a standalone issue unless an Epic signal is present. Only ask for an epic/sub-issue number when at least one explicit Epic signal or parent/sub-issue is detected.
7. Summarize the issue, feasibility, constraints, and blockers.
8. If a truly blocking ambiguity exists, ask before coding. Otherwise proceed conservatively.
9. Run `.aiassistant/skills/issue-workflow/scripts/make-issue-branch.sh <issue-number> "<issue-title>"`.
10. Follow `AGENTS.md`.
11. Activate the relevant skills:
   - `imagify-architecture`
   - `wordpress-compliance`
12. Implement minimal changes and update tests if needed. Verify test coverage for all added/modified code.
13. Run PHPCS and static analysis; fix any new violations before committing.
14. Commit atomically: one `git commit` per logical change set using Conventional Commits format.
15. Run `.aiassistant/skills/issue-workflow/scripts/init-pr-draft.sh <issue-number>`.
16. Fill the PR draft at `.TemporaryItems/Issues/imagify-plugin/pull/<issue-number>.md` using `refs/pr-template.md` as guide.
17. Run `git push` to publish the branch.
18. Create the GitHub PR using the filled draft (set as draft if implementation is still in progress).
19. Monitor PR CI status checks until all pass. Report any failures with actionable details.

## Tooling — Prefer MCPs, Fall Back to Shell

This workflow uses MCP tools when available. Always prefer them over shell commands.
If an MCP tool is not available in the current session, fall back to the shell equivalent.

### Issue fetch
| Preferred (MCP) | Fallback |
|---|---|
| `mcp_github_github_issue_read` (method: `get`, `get_sub_issues`) | `issue-sync.sh <number>` → read `.TemporaryItems/…/<number>.md` |

### Branch creation
| Preferred (MCP) | Fallback |
|---|---|
| `mcp_gitkraken_git_branch` (action: `create`) + `mcp_gitkraken_git_checkout` | `make-issue-branch.sh <number> "<title>"` |

### Staging & committing
| Preferred (MCP) | Fallback |
|---|---|
| `mcp_gitkraken_git_add_or_commit` (action: `add`, then `commit`) | `git add` / `git commit` in terminal |

### Pushing
| Preferred (MCP) | Fallback |
|---|---|
| `mcp_gitkraken_git_push` | `git push` in terminal |

### PR creation
| Preferred (MCP) | Fallback |
|---|---|
| `mcp_github_github_create_pull_request` | Provide the filled draft manually |

### CI monitoring
| Preferred (MCP) | Fallback |
|---|---|
| `github-pull-request_pullRequestStatusChecks` or `mcp_github_github_pull_request_read` (method: `get_check_runs`) | Ask user to check GitHub Actions |

## Git Operations

This skill operates under the **Issue Workflow exception** defined in AGENTS.md §6.1.

You MAY:
1. Run atomic commits — one per logical change set, only after PHPCS + static analysis pass.
2. Push once all commits are ready.
3. Create the GitHub Pull Request using the filled PR draft from `.TemporaryItems/Issues/imagify-plugin/pull/<issue-number>.md`.
4. Monitor CI status checks until all pass or a failure is detected and reported.

Commit message format: `type(scope): short description` (Conventional Commits).
Do not amend commits that have already been pushed.

## Epic And Sub-Issue Sync
The sync script auto-downloads parent epics and sub-issues into
`.TemporaryItems/Issues/imagify-plugin/issues/`. To skip related sync, set
`IMAGIFY_SYNC_RELATED=0` when invoking the script.
