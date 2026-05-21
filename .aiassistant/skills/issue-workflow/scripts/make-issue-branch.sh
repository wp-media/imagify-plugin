#!/usr/bin/env bash
# Create a branch name from an issue number and title.
# Usage: make-issue-branch.sh <issue-number> "<issue-title>" [base-ref]
#
# base-ref: optional git ref to branch from (default: current HEAD).
#           Pass an explicit ref such as "origin/chore/add-ai-assistant" when
#           running inside a worktree or after a git fetch to avoid inheriting
#           unexpected commits from a different branch.
set -euo pipefail

# Required arguments.
ISSUE_NUMBER="${1:?issue number required}"
TITLE="${2:?issue title required}"
BASE_REF="${3:-HEAD}"

# Build a short, URL-safe slug from the title (first 4 words max).
SLUG="$(printf '%s' "$TITLE" \
  | tr '[:upper:]' '[:lower:]' \
  | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//' \
  | cut -d- -f1-4)"

# Branch naming convention: fix/<issue>-<slug>
BRANCH="fix/${ISSUE_NUMBER}-${SLUG}"

# Create and switch to the branch from the explicit base.
git checkout -b "$BRANCH" "$BASE_REF"
echo "$BRANCH"
