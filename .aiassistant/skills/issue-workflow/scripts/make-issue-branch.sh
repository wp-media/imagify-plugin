#!/usr/bin/env bash
# Create a branch name from an issue number and title.
# Usage: make-issue-branch.sh <issue-number> "<issue-title>"
set -euo pipefail

# Required arguments.
ISSUE_NUMBER="${1:?issue number required}"
TITLE="${2:?issue title required}"

# Build a short, URL-safe slug from the title (first 4 words max).
SLUG="$(printf '%s' "$TITLE" \
  | tr '[:upper:]' '[:lower:]' \
  | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//' \
  | cut -d- -f1-4)"

# Branch naming convention: fix/<issue>-<slug>
BRANCH="fix/${ISSUE_NUMBER}-${SLUG}"

# Create and switch to the branch.
git checkout -b "$BRANCH"
echo "$BRANCH"
