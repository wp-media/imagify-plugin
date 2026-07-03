#!/usr/bin/env bash
# Ephemeral QA environments for TestRail runs (one nginx + one apache WordPress).
#
#   bin/qa-env.sh up       build imagify.zip, start both envs, install WP + plugin,
#                          seed the API key, write .ai/settings.local.json, snapshot DBs
#   bin/qa-env.sh reset [nginx|apache|all]   restore DB(s) to the post-setup snapshot
#   bin/qa-env.sh status   reachability check for both envs
#   bin/qa-env.sh down     stop and DELETE both envs completely (volumes included)
#
# Env vars: IMAGIFY_TESTS_API_KEY (optional — seeded into imagify_settings when set),
#           QA_NGINX_PORT (default 8801), QA_APACHE_PORT (default 8802).
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="$REPO_ROOT/Tests/e2e/qa-env/docker-compose.yml"
SNAP_DIR="$REPO_ROOT/Tests/e2e/qa-env/snapshots"
CONFIG_FILE="$REPO_ROOT/.ai/settings.local.json"

NGINX_PORT="${QA_NGINX_PORT:-8801}"
APACHE_PORT="${QA_APACHE_PORT:-8802}"
ADMIN_USER="admin"
ADMIN_PASS="password"

dc() { docker compose -f "$COMPOSE_FILE" "$@"; }
wp_nginx()  { dc run --rm --no-deps cli-nginx  wp "$@"; }
wp_apache() { dc run --rm --no-deps cli-apache wp "$@"; }

require_docker() {
  command -v docker >/dev/null 2>&1 || { echo "ERROR: docker is not installed — see Tests/e2e/qa-env/README" >&2; exit 1; }
  docker info >/dev/null 2>&1 || { echo "ERROR: docker daemon is not running" >&2; exit 1; }
}

wait_http() { # url, tries
  local url="$1" tries="${2:-60}"
  for _ in $(seq 1 "$tries"); do
    if curl -s -o /dev/null -w '%{http_code}' "$url" | grep -qE '^(200|30[0-9])$'; then return 0; fi
    sleep 2
  done
  echo "ERROR: $url did not become reachable" >&2
  return 1
}

install_wp() { # wp_fn, url, label
  local wp_fn="$1" url="$2" label="$3"
  if ! $wp_fn core is-installed >/dev/null 2>&1; then
    echo "-- [$label] installing WordPress"
    $wp_fn core install --url="$url" --title="Imagify QA ($label)" \
      --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" \
      --admin_email="qa@example.com" --skip-email
  fi
  echo "-- [$label] installing imagify.zip"
  $wp_fn plugin install /packages/imagify.zip --activate --force
  $wp_fn rewrite structure '/%postname%/' --hard || true
  if [ -n "${IMAGIFY_TESTS_API_KEY:-}" ]; then
    echo "-- [$label] seeding Imagify API key"
    $wp_fn option patch update imagify_settings api_key "$IMAGIFY_TESTS_API_KEY" 2>/dev/null \
      || $wp_fn option update imagify_settings "{\"api_key\":\"$IMAGIFY_TESTS_API_KEY\"}" --format=json
  fi
}

cmd_up() {
  require_docker

  # Preserve an existing Imagify key when the env var isn't set (must happen BEFORE
  # install_wp seeds it into the fresh sites).
  if [ -z "${IMAGIFY_TESTS_API_KEY:-}" ] && [ -f "$CONFIG_FILE" ]; then
    IMAGIFY_TESTS_API_KEY=$(python3 -c "import json;print(json.load(open('$CONFIG_FILE')).get('imagify',{}).get('api_key',''))" 2>/dev/null || echo '')
  fi

  echo "== Building imagify.zip"
  ZIP=$(ls "$REPO_ROOT"/generatedpackages/*.zip 2>/dev/null | head -1 || true)
  if [ -z "$ZIP" ] || [ -n "$(find "$REPO_ROOT" -name '*.php' -newer "$ZIP" -not -path '*/vendor/*' -not -path '*/node_modules/*' -print -quit 2>/dev/null)" ]; then
    bash "$REPO_ROOT/bin/build-zip.sh"
    ZIP=$(ls "$REPO_ROOT"/generatedpackages/*.zip | head -1)
  fi
  # the compose mount expects the canonical name
  cp -f "$ZIP" "$REPO_ROOT/generatedpackages/imagify.zip" 2>/dev/null || true

  echo "== Starting containers (nginx :$NGINX_PORT, apache :$APACHE_PORT)"
  dc up -d --wait db-nginx db-apache php-nginx web-nginx wp-apache

  wait_http "http://localhost:$NGINX_PORT/"  || true
  wait_http "http://localhost:$APACHE_PORT/" || true

  install_wp wp_nginx  "http://localhost:$NGINX_PORT"  nginx
  install_wp wp_apache "http://localhost:$APACHE_PORT" apache

  echo "== Writing $CONFIG_FILE"
  mkdir -p "$(dirname "$CONFIG_FILE")"
  EXISTING_TESTRAIL='{}'
  [ -f "$CONFIG_FILE" ] && EXISTING_TESTRAIL=$(python3 -c "import json;print(json.dumps(json.load(open('$CONFIG_FILE')).get('testrail',{})))" 2>/dev/null || echo '{}')
  python3 - "$CONFIG_FILE" <<PYEOF
import json, sys
config = {
    "generated_by": "bin/qa-env.sh",
    "environments": {
        "nginx": {
            "url": "http://localhost:$NGINX_PORT",
            "username": "$ADMIN_USER", "password": "$ADMIN_PASS",
            "wp_cli": "docker compose -f Tests/e2e/qa-env/docker-compose.yml run --rm --no-deps cli-nginx wp",
        },
        "apache": {
            "url": "http://localhost:$APACHE_PORT",
            "username": "$ADMIN_USER", "password": "$ADMIN_PASS",
            "wp_cli": "docker compose -f Tests/e2e/qa-env/docker-compose.yml run --rm --no-deps cli-apache wp",
        },
    },
    "imagify": {"api_key": "${IMAGIFY_TESTS_API_KEY:-}"},
    "testrail": json.loads('''$EXISTING_TESTRAIL'''),
}
json.dump(config, open(sys.argv[1], "w"), indent=2)
PYEOF

  echo "== Snapshotting databases"
  mkdir -p "$SNAP_DIR"
  wp_nginx  db export - > "$SNAP_DIR/nginx.sql"
  wp_apache db export - > "$SNAP_DIR/apache.sql"

  echo "== QA environments ready"
  echo "   nginx : http://localhost:$NGINX_PORT  ($ADMIN_USER/$ADMIN_PASS)"
  echo "   apache: http://localhost:$APACHE_PORT ($ADMIN_USER/$ADMIN_PASS)"
}

cmd_reset() {
  require_docker
  local target="${1:-all}"
  if [ "$target" = nginx ] || [ "$target" = all ]; then
    [ -f "$SNAP_DIR/nginx.sql" ] || { echo "ERROR: no nginx snapshot — run 'up' first" >&2; exit 1; }
    echo "== Restoring nginx DB snapshot"
    wp_nginx db import - < "$SNAP_DIR/nginx.sql"
  fi
  if [ "$target" = apache ] || [ "$target" = all ]; then
    [ -f "$SNAP_DIR/apache.sql" ] || { echo "ERROR: no apache snapshot — run 'up' first" >&2; exit 1; }
    echo "== Restoring apache DB snapshot"
    wp_apache db import - < "$SNAP_DIR/apache.sql"
  fi
}

cmd_status() {
  require_docker
  for pair in "nginx:$NGINX_PORT" "apache:$APACHE_PORT"; do
    name="${pair%%:*}"; port="${pair##*:}"
    code=$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:$port/wp-login.php" || echo 000)
    echo "$name (http://localhost:$port): HTTP $code"
  done
}

cmd_down() {
  require_docker
  echo "== Deleting QA environments (containers + volumes)"
  dc down -v --remove-orphans
  rm -rf "$SNAP_DIR"
  echo "== Done. ($CONFIG_FILE left in place — delete it manually if it points at these envs)"
}

case "${1:-}" in
  up)     cmd_up ;;
  reset)  cmd_reset "${2:-all}" ;;
  status) cmd_status ;;
  down)   cmd_down ;;
  *)      grep '^#' "$0" | head -12 | sed 's/^# \{0,1\}//'; exit 1 ;;
esac
