#!/usr/bin/env bash
#
# Generates a distribution ZIP identical to what the deploy-tag.yml CI sends to SVN.
# Usage : ./bin/build-zip.sh [zip-name]
# Example: ./bin/build-zip.sh imagify-1.9.9.zip

set -euo pipefail

PLUGIN_SLUG="imagify"
ZIP_NAME="${1:-${PLUGIN_SLUG}.zip}"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DISTIGNORE="${REPO_ROOT}/.distignore"
TMP_DIR="$(mktemp -d)"
BUILD_DIR="${TMP_DIR}/${PLUGIN_SLUG}"

echo "==> Temporary build directory: ${BUILD_DIR}"

# ── 1. Install Strauss (mirrors CI) ──────────────────────────────────────────
echo "==> Checking Strauss..."
if [ ! -f "${REPO_ROOT}/bin/strauss.phar" ]; then
  echo "    Downloading strauss.phar..."
  curl -o "${REPO_ROOT}/bin/strauss.phar" -L -C - \
    https://github.com/BrianHenryIE/strauss/releases/latest/download/strauss.phar
fi

# ── 2. Composer install without dev dependencies (mirrors CI) ────────────────
#
# composer.lock is gitignored, so a fresh CI checkout has none and `composer
# install` resolves the newest versions allowed by composer.json. Locally the
# lock exists and pins whatever was last resolved, which silently produces a
# zip with a DIFFERENT dependency set than the release. Set it aside so this
# genuinely mirrors CI, and put it back afterwards.
LOCK="${REPO_ROOT}/composer.lock"
LOCK_BACKUP="${REPO_ROOT}/composer.lock.build-zip-backup"

restore_lock() {
  if [ -f "${LOCK_BACKUP}" ]; then
    mv -f "${LOCK_BACKUP}" "${LOCK}"
    echo "==> Restored your composer.lock."
  fi
}
# Runs on success, failure and interrupt, so a broken build never leaves the
# developer without their lock file.
trap restore_lock EXIT

if [ -f "${LOCK}" ]; then
  echo "==> Setting composer.lock aside so dependencies resolve fresh (as CI does)..."
  mv -f "${LOCK}" "${LOCK_BACKUP}"
fi

echo "==> composer install -o --no-dev..."
composer install -o --no-dev -d "${REPO_ROOT}"

# ── 3. Build JS/CSS assets ───────────────────────────────────────────────────
echo "==> npm install && npm run build..."
npm install --prefix "${REPO_ROOT}"
npm run build --prefix "${REPO_ROOT}"

# ── 4. Copy the repo into the temporary directory ────────────────────────────
echo "==> Copying files..."
rsync -a --no-owner --no-group "${REPO_ROOT}/" "${BUILD_DIR}/"

# ── 5. Apply .distignore exclusions ──────────────────────────────────────────
echo "==> Applying .distignore..."
while IFS= read -r line || [ -n "$line" ]; do
  # Skip empty lines and comments
  [[ -z "$line" || "$line" == \#* ]] && continue
  # Strip leading slash if present (relative path)
  path="${line#/}"
  target="${BUILD_DIR}/${path}"
  if [ -e "$target" ] || [ -L "$target" ]; then
    rm -rf "$target"
    echo "    Removed: ${path}"
  fi
done < "${DISTIGNORE}"

# ── 6. Create the ZIP ────────────────────────────────────────────────────────
mkdir -p "${REPO_ROOT}/generatedpackages"
OUTPUT="${REPO_ROOT}/generatedpackages/${ZIP_NAME}"
echo "==> Creating ZIP: ${OUTPUT}"
# `zip -r` UPDATES an existing archive rather than replacing it, so without this
# every build inherits the contents of every previous build - including files
# since removed from the plugin or newly excluded by .distignore.
rm -f "${OUTPUT}"
(cd "${TMP_DIR}" && zip -r "${OUTPUT}" "${PLUGIN_SLUG}" -x "*.DS_Store")

# ── 7. Cleanup ───────────────────────────────────────────────────────────────
rm -rf "${TMP_DIR}"

echo ""
echo "✓ ZIP generated: ${OUTPUT}"
echo "  Size: $(du -sh "${OUTPUT}" | cut -f1)"

# ── 8. Restore dev dependencies ──────────────────────────────────────────────
# Put the original lock back first, so the restore reinstalls exactly what the
# developer had rather than the freshly resolved set.
restore_lock
trap - EXIT

echo "==> Restoring dev dependencies (composer install)..."
composer install -d "${REPO_ROOT}" --quiet
echo "✓ Dev dependencies restored."
