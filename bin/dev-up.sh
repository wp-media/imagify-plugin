#!/usr/bin/env bash
# Boot a local WordPress dev environment for Imagify.
#
# Requirements: Docker (Rancher Desktop / Docker Desktop), Node.js, Composer.
#
# Usage:
#   bin/dev-up.sh           # boot + activate plugin + seed minimal data
#   bin/dev-up.sh --no-seed # boot + activate plugin only
#   bin/dev-up.sh --reset   # destroy existing env first, then boot fresh
#
# After it finishes:
#   Site:    http://localhost:8888
#   Admin:   http://localhost:8888/wp-admin  (admin / password)
#   Tests:   http://localhost:8889 (used by phpunit; ignore for manual testing)
#
# Port 8888 already used (for example by another wp-env)? Pick other ports:
#   WP_ENV_PORT=8950 WP_ENV_TESTS_PORT=8951 bin/dev-up.sh

set -euo pipefail

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
cd "$ROOT_DIR"

# wp-env reads WP_ENV_PORT itself, we only need it to print the right URLs.
WP_ENV_PORT="${WP_ENV_PORT:-8888}"

SEED=1
RESET=0
for arg in "$@"; do
	case "$arg" in
		--no-seed) SEED=0 ;;
		--reset)   RESET=1 ;;
		-h|--help)
			sed -n '2,17p' "$0"
			exit 0
			;;
		*) echo "Unknown arg: $arg" >&2; exit 1 ;;
	esac
done

# --- Pre-flight checks ---
command -v docker    >/dev/null || { echo "❌ docker not found"; exit 1; }
command -v node      >/dev/null || { echo "❌ node not found"; exit 1; }
command -v composer  >/dev/null || { echo "❌ composer not found"; exit 1; }
docker info >/dev/null 2>&1    || { echo "❌ Docker daemon not reachable (start Rancher Desktop / Docker Desktop)"; exit 1; }

# --- Composer dependencies ---
echo "▶ Installing composer dependencies..."
composer install --no-interaction --quiet

# --- wp-env ---
if [[ "$RESET" == "1" ]]; then
	echo "▶ Destroying existing wp-env..."
	npx --yes @wordpress/env destroy --quiet || true
fi

if command -v lsof >/dev/null && lsof -nP -iTCP:"$WP_ENV_PORT" -sTCP:LISTEN >/dev/null 2>&1; then
	echo "⚠ Port $WP_ENV_PORT is already in use. If Imagify's own wp-env is already running, ignore this."
	echo "  If another project's wp-env holds it, run again with other ports:"
	echo "    WP_ENV_PORT=8950 WP_ENV_TESTS_PORT=8951 bin/dev-up.sh"
fi

echo "▶ Starting wp-env (this may take a few minutes on first run)..."
npx --yes @wordpress/env start

# --- Activate plugin ---
echo "▶ Activating Imagify plugin..."
npx --yes @wordpress/env run cli wp plugin activate imagify

# --- Seed data ---
if [[ "$SEED" == "1" ]]; then
	echo "▶ Seeding demo content..."
	"$ROOT_DIR/bin/dev-seed.sh"
fi

cat <<EOF

✅ Imagify dev environment is up.

   Site:    http://localhost:${WP_ENV_PORT}
   Admin:   http://localhost:${WP_ENV_PORT}/wp-admin
   Login:   admin / password

   Tail logs:           npx @wordpress/env run cli "cat wp-content/debug.log"
   Run WP-CLI:          npx @wordpress/env run cli wp <command>
   Stop:                npx @wordpress/env stop
   Destroy (full wipe): npx @wordpress/env destroy

EOF
