#!/usr/bin/env bash
# Run the Playwright E2E suite against the local wp-env.
#
# Usage:
#   bin/test-e2e.sh              # run all tests
#   bin/test-e2e.sh --headed     # run with browser visible
#   bin/test-e2e.sh --ui         # open Playwright UI mode
#   bin/test-e2e.sh specs/smoke  # run a single spec file or pattern
#
# API key:
#   Create .env.local at the repo root with:
#     IMAGIFY_TESTS_API_KEY=your-key-here
#   That file is gitignored and sourced automatically.
#
# First run:
#   Make sure wp-env is running first:  bin/dev-up.sh

set -euo pipefail

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
cd "$ROOT_DIR"

# Load local secrets if present.
if [[ -f "$ROOT_DIR/.env.local" ]]; then
	set -o allexport
	# shellcheck disable=SC1091
	source "$ROOT_DIR/.env.local"
	set +o allexport
fi

# Verify wp-env is reachable.
if ! curl -sf http://localhost:8888 >/dev/null 2>&1; then
	echo "❌ wp-env doesn't seem to be running on http://localhost:8888"
	echo "   Run: bin/dev-up.sh"
	exit 1
fi

cd "$ROOT_DIR/Tests/e2e"

# Install deps if node_modules is missing.
if [[ ! -d node_modules ]]; then
	echo "▶ Installing Playwright dependencies..."
	npm install
	npx playwright install --with-deps chromium
fi

exec npx playwright test "$@"
