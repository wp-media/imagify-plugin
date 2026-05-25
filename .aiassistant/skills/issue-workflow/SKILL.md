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
7. **Invoke the `grooming-agent` sub-agent** — pass it the issue number and the path to the synced issue file. It will:
   - Read the issue and any parent epic for context.
   - Map every affected class, method, and module by reading the codebase.
   - Determine the architecturally correct layer for the fix and classify the solution as root-cause or workaround.
   - Write the implementation spec to `.TemporaryItems/Issues/imagify-plugin/issues/<issue-number>-spec.md`.
   - Return the spec path and a one-paragraph solution summary.
8. Read the spec produced by `grooming-agent`. Only ask the user if the spec contains an explicitly unresolvable ambiguity. Otherwise proceed with the solution it defines.
9. Determine the base branch: default to `origin/develop` unless the user specified a different one (e.g. `origin/feature/mcp`). Run `.aiassistant/skills/issue-workflow/scripts/make-issue-branch.sh <issue-number> "<issue-title>" <base-branch>`. Keep `<base-branch>` in context — it is passed to `lead-reviewer` and `qa-engineer`.10. Follow `AGENTS.md`.
11. Activate the relevant skills:
   - `imagify-architecture`
   - `wordpress-compliance`
12. Implement minimal changes following the spec at `.TemporaryItems/Issues/imagify-plugin/issues/<issue-number>-spec.md`. Update tests as specified. Verify test coverage for all added/modified code.
13. Run PHPCS and static analysis; fix any new violations before committing.
14. Commit atomically: one `git commit` per logical change set using Conventional Commits format. Every commit made by AI must include a `Co-Authored-By` trailer identifying the model that authored it (e.g. `Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>` or `Co-Authored-By: GPT-4o <noreply@openai.com>`). Use your own model name and provider.
14a. **Run the full E2E suite locally** — `bash bin/test-e2e.sh`:
   - If wp-env is not reachable (port 8888 not open), log a warning and continue — CI will catch regressions.
   - If all tests pass, continue.
   - If any test fails, investigate the root cause (read the error, check traces if available), fix the issue, re-commit, and re-run until the suite is fully green before proceeding.
   Do not move to step 15 while there are known local E2E failures.
15. **Invoke the `lead-reviewer` sub-agent** — pass it the issue number, the spec path, and the base branch. It will:
    - Review the diff against the implementation spec and project standards (architecture, PHP, JS, tests).
    - Return a structured verdict: **PASS** or **CHANGES REQUESTED** with specific blockers.
16. If `lead-reviewer` returns **CHANGES REQUESTED**: address every blocker, re-run PHPCS, commit the fixes, then re-invoke `lead-reviewer`. Repeat until **PASS**.
17. Run `.aiassistant/skills/issue-workflow/scripts/init-pr-draft.sh <issue-number>`.
18. Fill every section of the PR draft at `.TemporaryItems/Issues/imagify-plugin/pull/<issue-number>.md`. The file was already initialized from `refs/pr-template.md` by the script in step 17. Complete every section with relevant content — do not skip sections or invent a different structure. Replace all placeholder text (`*Explain…*`, `*Describe…*`, etc.) with real content. Select exactly ONE `Type of change` checkbox that best describes the change; leave all others unchecked.
19. Run `git push` to publish the branch.
20. Create the GitHub PR using the **exact content of the filled draft** as the PR body. Do not summarise or rewrite it — copy it verbatim. Set as draft if implementation is still in progress.
    - **PR title format**: `Closes #<issue-number>: <short descriptive title>` (use `Fixes` instead of `Closes` only when the issue should auto-close on merge to a non-default branch).
    - After creating the PR, assign it to yourself and apply the **Made by AI** label if it exists in the repository: `gh pr edit <PR_number> --add-assignee @me --add-label "Made by AI"`.
    - If the `Made by AI` label does not exist, skip the label silently — do not create it.
21. **Invoke the `qa-engineer` sub-agent** — pass it the issue number, PR number, and base branch. It will:
    - Read the issue spec and PR diff.
    - Select validation strategies (API, Browser, Analysis) based on what changed.
    - Delegate browser/UI flows to the `e2e-qa-tester` sub-agent when the change touches admin UI.
    - Write any missing Playwright tests under `Tests/e2e/` and verify they pass locally (`bash bin/test-e2e.sh`).
    - Commit new or updated test files to the branch and push before handing back a report.
    - **Post the QA report as a PR comment** (always, regardless of outcome) — the comment includes the full structured report and a list of any screenshots captured in `.e2e-screenshots/`.
    - Return a structured test report (see format in `.aiassistant/agents/qa-engineer.md`).
22. If `qa-engineer` reports **FAIL** or **PARTIAL**: fix the identified blockers, re-commit, re-push, and re-run the agent before continuing.
23. If `qa-engineer` reports **READY TO MERGE**:
    1. **Update the PR body** — edit the **"What was tested"** section under `## Detailed scenario` to include the full QA report: strategies used, each acceptance criterion with its validation method and result, and smoke-test outcomes. Use `gh pr edit <PR_number> --body "..."` with the updated body. Also update the local draft at `.TemporaryItems/Issues/imagify-plugin/pull/<issue-number>.md` to match.
    2. **Convert the PR from draft to ready-for-review**: `gh pr ready <PR_number>`.
24. Monitor PR CI status checks until all pass. Report any failures with actionable details.

## Agent Pipeline

This workflow uses four sub-agents defined in `.aiassistant/agents/`. Each runs in an isolated context and communicates via structured output.

### grooming-agent (issue analyst)

Invoke after fetching the issue (step 7). Provide:
- The issue number
- The path to the synced issue file

```
Invoke sub-agent: grooming-agent
Inputs: issue #<N>, issue file path
```

Produces `.TemporaryItems/Issues/imagify-plugin/issues/<N>-spec.md`. The implementing agent reads this spec before writing any code.

### lead-reviewer (code reviewer)

Invoke after all commits are made (step 15). Provide:
- The issue number
- The spec path
- The base branch

```
Invoke sub-agent: lead-reviewer
Inputs: issue #<N>, spec path, base branch
```

Returns **PASS** or **CHANGES REQUESTED** with specific blockers. Loop until PASS before proceeding to push.

### qa-engineer (QA orchestrator)

Invoke after the PR is created (step 21). Provide:
- The issue number (for acceptance criteria)
- The PR number (for diff and "How to test" section)
- The base branch

```
Invoke sub-agent: qa-engineer
Inputs: issue #<N>, PR #<M>, base branch
```

The agent selects strategies automatically:
- **API/functional** — if backend logic changed (AJAX, hooks, WP-CLI, data processing)
- **Browser/UI** — if admin UI changed; delegates to `e2e-qa-tester`
- **Analysis fallback** — if local environment is unavailable

### e2e-qa-tester (browser specialist)

Invoked by `qa-engineer` automatically for UI changes. Can also be invoked directly:

```
Invoke sub-agent: e2e-qa-tester
Inputs: acceptance criteria, changed frontend files, PR number
```

It will:
1. Boot `wp-env` if not running (`bash bin/dev-up.sh`)
2. Walk through the UI flows using Playwright MCP
3. Write temporary Playwright specs under `.e2e-temp/`
4. Run those specs against the local environment
5. Capture screenshots and publish them via the commit-SHA method
6. Remove all temp files and return per-criterion results and permanent screenshot URLs

### Decision tree

```
Issue fetched
  └─ invoke grooming-agent → writes <N>-spec.md

Spec ready
  └─ implement following spec
  └─ invoke lead-reviewer
       ├─ PASS             → push → open PR
       └─ CHANGES REQUESTED → fix blockers → re-invoke lead-reviewer

PR created
  └─ invoke qa-engineer
       ├─ backend only     → Strategy A (API/WP-CLI)
       ├─ UI touched       → Strategy B → delegate to e2e-qa-tester
       │                        └─ writes .e2e-temp/ specs → runs → screenshots
       │                        └─ publishes screenshots via commit-SHA → cleans up
       │                        └─ returns results + permanent URLs to qa-engineer
       └─ env unavailable  → Strategy C (Analysis)

qa-engineer always posts full report as PR comment (PASS, FAIL, or PARTIAL)
qa-engineer returns READY TO MERGE → update PR body with QA findings → mark PR ready for review
qa-engineer returns FAIL/PARTIAL   → fix blockers → re-run qa-engineer
```

---

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
| `mcp__GitKraken__pull_request_create` with `assign_to_me: true` | `gh pr create ... && gh pr edit <number> --add-assignee @me` |

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

Commit message format: `type(scope): short description` (Conventional Commits), followed by a `Co-Authored-By` trailer identifying the AI model on every AI-authored commit. Use your own model name and provider.
Do not amend commits that have already been pushed.

## Epic And Sub-Issue Sync
The sync script auto-downloads parent epics and sub-issues into
`.TemporaryItems/Issues/imagify-plugin/issues/`. To skip related sync, set
`IMAGIFY_SYNC_RELATED=0` when invoking the script.
