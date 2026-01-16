#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="ajax-snippets"
ZIP_NAME="${PLUGIN_SLUG}.zip"

rm -rf build
mkdir -p build

rsync -av \
  --exclude '.git' \
  --exclude '.github' \
  --exclude '.gitignore' \
  --exclude 'build' \
  --exclude '*.zip' \
  --exclude 'build-zip.sh' \
  --exclude 'composer.json' \
  --exclude 'composer.lock' \
  --exclude 'node_modules' \
  ./ "build/${PLUGIN_SLUG}/"

(cd build && zip -r "../${ZIP_NAME}" "${PLUGIN_SLUG}")

echo "Created ${ZIP_NAME}"
