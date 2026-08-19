#!/usr/bin/env bash
#
# Reproducible build for the Red Hermanos plugin.
# Produces red-hermanos.zip whose first level is red-hermanos/red-hermanos.php,
# ready to upload via Plugins → Add New → Upload Plugin.
#
set -euo pipefail

PLUGIN="red-hermanos"
cd "$(dirname "$0")"

rm -f "${PLUGIN}.zip"

# Package the plugin folder, excluding dev files.
zip -r "${PLUGIN}.zip" "${PLUGIN}" \
  -x "*/.git/*" "*/.github/*" "*/node_modules/*" \
     "*/.DS_Store" "*/build.sh" "*/tests/*"

echo "Creado ${PLUGIN}.zip"
unzip -l "${PLUGIN}.zip" | head -20
