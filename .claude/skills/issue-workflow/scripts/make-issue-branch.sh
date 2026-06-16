#!/usr/bin/env bash
# Create a branch name from an issue number and title.
# Usage: make-issue-branch.sh <issue-number> "<issue-title>" [prefix] [base-ref]
# prefix: fix (default), enhancement, test
#
# base-ref: pass an explicit ref such as "origin/develop" when running inside a
#           worktree or after a git fetch to avoid inheriting unexpected commits.
#           Defaults to "origin/develop" when omitted.
set -euo pipefail

# Required arguments.
ISSUE_NUMBER="${1:?issue number required}"
TITLE="${2:?issue title required}"

# Optional prefix — determines branch type per naming convention.
PREFIX="${3:-fix}"

# Validate prefix.
case "$PREFIX" in
  fix|enhancement|test) ;;
  *) PREFIX="fix" ;;
esac

# Optional base ref — defaults to origin/develop (Imagify base branch).
BASE_REF="${4:-origin/develop}"

# Build a short, URL-safe slug from the title (first 4 words max).
SLUG="$(printf '%s' "$TITLE" \
  | tr '[:upper:]' '[:lower:]' \
  | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//' \
  | cut -d- -f1-4 \
  | sed 's/-*$//')"

# Branch naming convention: <prefix>/<issue>-<slug>
BRANCH="${PREFIX}/${ISSUE_NUMBER}-${SLUG}"

# Create and switch to the branch (idempotent — safe if branch already exists).
if git show-ref --verify --quiet "refs/heads/${BRANCH}"; then
  git checkout "$BRANCH"
elif [[ -n "$BASE_REF" ]]; then
  git checkout -b "$BRANCH" "$BASE_REF"
else
  git checkout -b "$BRANCH"
fi
echo "$BRANCH"
