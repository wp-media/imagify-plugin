---
name: testrail-scenarios
description: Analyse PRs and generate TestRail test scenarios for review and publication.
---

# TestRail Scenarios

Entry point for generating TestRail test scenarios from merged GitHub PRs. Analyses each
PR, drafts a comprehensive set of test cases (happy path, failures, edge cases, regression
guards), stages them as YAML for human review, and — on an explicit `publish` command —
creates the cases in TestRail with deduplication.

## Invocation

```
/testrail-scenarios #1133              → single PR
/testrail-scenarios #1133 #1134 #1135  → multiple PRs
/testrail-scenarios                    → all PRs merged since the last release tag
/testrail-scenarios publish            → publish whatever is staged in .ai/testrail/pending/
```

You may also pass full PR URLs instead of `#`-prefixed numbers; both are accepted.

## What to do

1. **Detect publish mode.** If the argument is `publish` (or the user says "publish the
   staged scenarios"), skip to the **Publish mode** section below.

2. **Resolve the PR list.**
   - If one or more PR numbers / URLs are given as arguments, use exactly those.
   - If **no** arguments are given, derive the list from PRs merged since the last tag:
     ```bash
     git fetch --tags --quiet
     LAST_TAG=$(git describe --tags --abbrev=0)
     git log "$LAST_TAG"..HEAD --merges --pretty=format:'%s' \
       | grep -oE 'Merge pull request #[0-9]+' \
       | grep -oE '[0-9]+'
     ```
     If `git log ... --merges` yields nothing (squash-merge workflows produce no merge
     commits), fall back to the GitHub API:
     ```bash
     LAST_TAG_DATE=$(git log -1 --format=%aI "$LAST_TAG")
     gh pr list --state merged --base develop --limit 100 \
       --json number,mergedAt,title \
       --jq ".[] | select(.mergedAt > \"$LAST_TAG_DATE\") | .number"
     ```
     If both yield nothing, report that there are no PRs to process since `$LAST_TAG` and stop.

3. **Normalise** each entry to a bare PR number (strip `#` and any URL prefix).

4. **Spawn `testrail-scenario-agent`** once, passing the full list of PR numbers. Instruct it
   to run its full generate-and-stage workflow (dedup → analyse → generate → write staging
   YAML → print summary) and to stop and await review. Do **not** ask it to publish in this
   invocation.

5. **Relay the agent's summary** to the user verbatim, then remind them how to proceed:
   - To publish: `/testrail-scenarios publish` (or "publish the staged scenarios").
   - To revise: edit the YAML files under `.ai/testrail/pending/` directly, then publish.

6. **Offer the reviewer.** After relaying the summary, ask the user:
   > "Would you like the TestRail reviewer to check the staged scenarios before publishing?
   > It will flag low-signal cases, fill coverage gaps, and clean up step wording.
   > Run `/testrail-review` or reply **yes** to launch it."
   If the user replies yes (or any affirmative), spawn `testrail-review-agent` immediately
   with "review all staged files" and relay its report. Otherwise proceed — the reviewer
   is optional and skipping it is fine.

## Publish mode

When invoked as `/testrail-scenarios publish`, spawn `testrail-scenario-agent` in **publish
mode**: instruct it to read every YAML file under `.ai/testrail/reviewed/`, create the
sections and cases in TestRail, print the created case IDs, and delete each published YAML
file. Pass no PR list in this mode — the agent operates on whatever is in `reviewed/`.
If `reviewed/` is empty but `pending/` has files, the agent warns the user those files
haven't been reviewed and asks whether to proceed anyway. If both are empty, the agent
should say so and stop.

## Constraints

- This skill never calls the TestRail API directly. All API work is the agent's.
- Never publish automatically. Generation and publication are two distinct, user-gated steps.
- The TestRail credentials (`TESTRAIL_USERNAME`, `TESTRAIL_API_KEY`) live in the environment;
  do not prompt for them and never print them.
