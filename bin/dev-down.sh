#!/usr/bin/env bash
# Stop the local WordPress dev environment for Imagify.
#
# Usage:
#   bin/dev-down.sh          # stop containers (preserves data)
#   bin/dev-down.sh --clean  # destroy containers and volumes (full wipe)

set -euo pipefail

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
cd "$ROOT_DIR"

CLEAN=0
for arg in "$@"; do
	case "$arg" in
		--clean) CLEAN=1 ;;
		-h|--help)
			sed -n '2,8p' "$0"
			exit 0
			;;
		*) echo "Unknown arg: $arg" >&2; exit 1 ;;
	esac
done

if [[ "$CLEAN" == "1" ]]; then
	echo "Destroying wp-env (volumes and containers)..."
	npx --yes @wordpress/env destroy
	echo "Environment destroyed."
else
	echo "Stopping wp-env..."
	npx --yes @wordpress/env stop
	echo "Environment stopped. Data preserved. Run bin/dev-start.sh to restart."
fi
