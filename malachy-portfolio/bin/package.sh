#!/bin/bash
# =========================================================================
# Package Malachy Portfolio Theme for Shared Hosting
# =========================================================================
# Run: bash bin/package.sh
# Output: ../malachy-portfolio-v1.0.0.zip  (relative to script location)
# =========================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
VERSION="1.0.0"
OUTPUT_NAME="malachy-portfolio-v${VERSION}"
TEMP_DIR="/tmp/${OUTPUT_NAME}"

echo "→ Packaging theme ${OUTPUT_NAME}..."
echo "  Theme dir: ${THEME_DIR}"
echo "  Output:    ${THEME_DIR}/${OUTPUT_NAME}.zip"

# Clean any previous temp/zip
rm -rf "${TEMP_DIR}" "${THEME_DIR}/${OUTPUT_NAME}.zip" 2>/dev/null

# Copy theme to temp
mkdir -p "${TEMP_DIR}"
cp -r "${THEME_DIR}" "${TEMP_DIR}/malachy-portfolio"

# Remove development-only files
cd "${TEMP_DIR}/malachy-portfolio"
rm -rf \
  .gitignore \
  .editorconfig \
  node_modules \
  package.json \
  package-lock.json \
  webpack.config.js \
  tailwind.config.js \
  postcss.config.js \
  tsconfig.json \
  gulpfile.js \
  .hermes \
  docs \
  languages \
  assets/js/src \
  assets/css/src \
  2>/dev/null

# Remove any .git directories
find . -name '.git' -type d -exec rm -rf {} + 2>/dev/null || true

# Create zip
cd "${TEMP_DIR}"
zip -r "${THEME_DIR}/${OUTPUT_NAME}.zip" "malachy-portfolio" -x "*.git*" "node_modules/*" >/dev/null

# Clean up temp
rm -rf "${TEMP_DIR}"

echo "✓ Done: ${THEME_DIR}/${OUTPUT_NAME}.zip"
echo "  Size: $(du -h "${THEME_DIR}/${OUTPUT_NAME}.zip" | cut -f1)"
echo ""
echo "→ Upload via cPanel File Manager or WP Admin → Appearance → Themes → Add New → Upload Theme"
