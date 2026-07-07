#!/bin/bash
# Malachy Portfolio — Build & Package Script
# Run from the theme root directory.
# Usage: bash bin/build.sh

set -e

THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
OUTPUT_DIR="$THEME_DIR/../build"
ZIP_NAME="malachy-portfolio.zip"

echo "=== Malachy Portfolio Build Script ==="
echo "Theme dir: $THEME_DIR"
echo "Output:    $OUTPUT_DIR/$ZIP_NAME"
echo ""

# Step 1: Create clean build directory
rm -rf "$OUTPUT_DIR"
mkdir -p "$OUTPUT_DIR"

# Step 2: Copy theme files (excluding dev files)
echo "Copying theme files..."
rsync -a \
  --exclude='node_modules/' \
  --exclude='.git/' \
  --exclude='.gitignore' \
  --exclude='.env' \
  --exclude='docker-compose.yml' \
  --exclude='README.md' \
  --exclude='bin/' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='*.map' \
  "$THEME_DIR/" "$OUTPUT_DIR/malachy-portfolio/"

# Step 3: Create zip
echo "Creating zip..."
cd "$OUTPUT_DIR"
zip -r "$ZIP_NAME" malachy-portfolio/ -x "*/node_modules/*" "*.git*" > /dev/null 2>&1

echo ""
echo "=== Build Complete ==="
ls -lh "$OUTPUT_DIR/$ZIP_NAME"
echo "Zip ready at: $OUTPUT_DIR/$ZIP_NAME"
