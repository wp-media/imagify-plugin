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
#
# Custom port:
#   If wp-env runs on another port (WP_ENV_PORT=8950 bin/dev-up.sh), run the
#   suite with the same WP_ENV_PORT. IMAGIFY_BASE_URL, when set, wins over it.

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

# Playwright reads IMAGIFY_BASE_URL (Tests/e2e/playwright.config.ts). Derive it
# from WP_ENV_PORT so the check below and the suite target the same site.
WP_ENV_PORT="${WP_ENV_PORT:-8888}"
export IMAGIFY_BASE_URL="${IMAGIFY_BASE_URL:-http://localhost:${WP_ENV_PORT}}"

# Verify wp-env is reachable.
if ! curl -sf "$IMAGIFY_BASE_URL" >/dev/null 2>&1; then
	echo "❌ wp-env doesn't seem to be running on $IMAGIFY_BASE_URL"
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
