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
(cd "${TMP_DIR}" && zip -r "${OUTPUT}" "${PLUGIN_SLUG}" -x "*.DS_Store")

# ── 7. Cleanup ───────────────────────────────────────────────────────────────
rm -rf "${TMP_DIR}"

echo ""
echo "✓ ZIP generated: ${OUTPUT}"
echo "  Size: $(du -sh "${OUTPUT}" | cut -f1)"

# ── 8. Restore dev dependencies ──────────────────────────────────────────────
echo "==> Restoring dev dependencies (composer install)..."
composer install -d "${REPO_ROOT}" --quiet
echo "✓ Dev dependencies restored."
