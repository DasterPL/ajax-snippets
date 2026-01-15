#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="ajax-snippets"
VERSION="${1:-dev}"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

rm -rf build
mkdir -p build

rsync -av \
  --exclude '.git' \
  --exclude '.github' \
  --exclude 'build' \
  --exclude '*.zip' \
  --exclude 'node_modules' \
  ./ "build/${PLUGIN_SLUG}/"

(cd build && zip -r "../${ZIP_NAME}" "${PLUGIN_SLUG}")

echo "Created ${ZIP_NAME}"
