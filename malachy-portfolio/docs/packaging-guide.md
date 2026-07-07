# Malachy Portfolio — Build & Packaging Guide

**Version:** 1.1.0  
**Theme Root:** `/home/zubbyik/wordpress_project/malachy-portfolio/`

This document covers how to build, package, and distribute the theme for deployment to shared hosting or other WordPress sites.

---

## Table of Contents

1. [Packaging Scripts Overview](#packaging-scripts-overview)
2. [Method A: `package.sh` (Simple ZIP)](#method-a-packagesh-simple-zip)
3. [Method B: `build.sh` (Build Directory)](#method-b-buildsh-build-directory)
4. [Manual Packaging](#manual-packaging)
5. [Version Bumping](#version-bumping)
6. [Upload & Deploy](#upload--deploy)
7. [Post-Deployment Checklist](#post-deployment-checklist)
8. [Automated CI Packaging](#automated-ci-packaging)

---

## Packaging Scripts Overview

Two scripts ship with the theme:

| Script | Location | Purpose |
|--------|----------|---------|
| `package.sh` | `bin/package.sh` | Quick ZIP for shared hosting upload. Excludes dev files, docs, `.git`. |
| `build.sh` | `bin/build.sh` | Full build into `../build/` directory with ZIP. Excludes more aggressively. |

Both scripts are designed to be run **from the theme root** and produce a ZIP ready for WordPress theme upload.

---

## Method A: `package.sh` (Simple ZIP)

This is the recommended script for routine deployment.

### Usage

```bash
cd /home/zubbyik/wordpress_project/malachy-portfolio
bash bin/package.sh
```

### What It Does

1. Copies the theme directory to `/tmp/malachy-portfolio-v1.0.0/`
2. Removes development-only files (see table below)
3. Creates `malachy-portfolio-v1.0.0.zip` in the parent directory

### Output

```
→ Packaging theme malachy-portfolio-v1.0.0...
  Theme dir: /home/zubbyik/wordpress_project/malachy-portfolio
  Output:    /home/zubbyik/wordpress_project/malachy-portfolio/../malachy-portfolio-v1.0.0.zip
✓ Done: /home/zubbyik/wordpress_project/malachy-portfolio/malachy-portfolio-v1.0.0.zip
  Size: 2.1M
```

### Excluded Files

| Pattern | Reason |
|---------|--------|
| `.gitignore` | Version control |
| `.editorconfig` | IDE config |
| `node_modules/` | Development dependencies |
| `package.json` / `package-lock.json` | Node.js metadata |
| `webpack.config.js` | Build config |
| `tailwind.config.js` | Build config |
| `postcss.config.js` | Build config |
| `tsconfig.json` | TypeScript config |
| `gulpfile.js` | Task runner |
| `.hermes/` | Agent config (development) |
| `docs/` | Documentation (not needed at runtime) |
| `languages/` | Empty or WIP translations |
| `assets/js/src/` | Source JS (pre-compiled) |
| `assets/css/src/` | Source CSS (pre-compiled) |
| `.git/` directories | Version control metadata |

### Output Path

The ZIP lands at:
```
/home/zubbyik/wordpress_project/malachy-portfolio/malachy-portfolio-v{version}.zip
```

---

## Method B: `build.sh` (Build Directory)

Use when you want a clean build directory in addition to the ZIP.

### Usage

```bash
cd /home/zubbyik/wordpress_project/malachy-portfolio
bash bin/build.sh
```

### What It Does

1. Creates `../build/malachy-portfolio/` with a clean copy of the theme
2. Excludes more files than `package.sh` (also excludes `bin/`, `README.md`, source maps)
3. Zips the result to `../build/malachy-portfolio.zip`

### Output

```
=== Malachy Portfolio Build Script ===
Theme dir: /home/zubbyik/wordpress_project/malachy-portfolio
Output:    /home/zubbyik/wordpress_project/../build/malachy-portfolio.zip

Copying theme files...
Creating zip...

=== Build Complete ===
-rw-r--r--  1 user user  2.1M  Jul  7 12:00 /home/zubbyik/wordpress_project/../build/malachy-portfolio.zip
```

### Differences from `package.sh`

| Aspect | `package.sh` | `build.sh` |
|--------|-------------|------------|
| Output location | Theme root | `../build/` directory |
| Version in filename | Yes (`v{version}`) | No |
| Excludes `bin/` | No | Yes |
| Excludes `README.md` | No | Yes |
| Excludes `*.map` | No | Yes |
| Uses `rsync` | No | Yes |

---

## Manual Packaging

If you need full control over what goes in the ZIP:

```bash
cd /home/zubbyik/wordpress_project

# Create a temporary directory
mkdir -p /tmp/malachy-portfolio-pkg

# Copy theme, excluding dev files
rsync -a \
  --exclude='node_modules/' \
  --exclude='.git/' \
  --exclude='.gitignore' \
  --exclude='docs/' \
  --exclude='.hermes/' \
  --exclude='bin/' \
  malachy-portfolio/ /tmp/malachy-portfolio-pkg/malachy-portfolio/

# ZIP it
cd /tmp/malachy-portfolio-pkg
zip -r /home/zubbyik/wordpress_project/malachy-portfolio-manual.zip malachy-portfolio/

# Clean up
rm -rf /tmp/malachy-portfolio-pkg
```

---

## Version Bumping

When you release a new version:

### 1. Update `style.css`

```css
/*
Theme Name: Malachy Portfolio
Version: 1.1.0          ← Update this
...
*/
```

### 2. Update `functions.php`

```php
define( 'MALACHY_THEME_VERSION', '1.1.0' );  ← Update this
```

This version string controls cache busting for enqueued CSS/JS assets.

### 3. Update docs

Update version references in `docs/` files to match.

### 4. Update `package.sh`

```bash
VERSION="1.1.0"  ← Update this
OUTPUT_NAME="malachy-portfolio-v${VERSION}"
```

### 5. Re-package

```bash
bash bin/package.sh
```

This produces `malachy-portfolio-v1.1.0.zip`.

### Version History

| Version | Date | Notes |
|---------|------|-------|
| 1.0.0 | 2026-07-07 | Initial release, React → WordPress conversion |
| 1.0.9+ | — | Data seeder added (internal) |
| 1.1.0 | — | Current development version |

---

## Upload & Deploy

### Via WP Admin (Shared Hosting)

1. Log into **WP Admin** (`/wp-admin`)
2. Go to **Appearance → Themes → Add New → Upload Theme**
3. Choose the `malachy-portfolio-v{version}.zip` file
4. Click **Install Now**
5. Click **Activate**

### Via cPanel File Manager

1. Upload ZIP to `/wp-content/themes/`
2. Extract the archive (cPanel has an extract function)
3. Go to **WP Admin → Appearance → Themes** and activate

### Via WP-CLI

```bash
# Upload and activate in one command
wp theme install /path/to/malachy-portfolio-v1.1.0.zip --activate
```

### Via Docker Bind Mount (Development)

No packaging needed — the theme directory is already mounted directly in the Docker container. See [staging-development.md](staging-development.md).

---

## Post-Deployment Checklist

After uploading and activating the theme on a new site:

1. **Set the front page:**
   - Go to **Settings → Reading**
   - Set "Your homepage displays" → **Your latest posts**
   - Save

2. **Flush permalinks:**
   - Go to **Settings → Permalinks**
   - Select **Post name**
   - Save

3. **Seed demo content** (if desired):
   ```bash
   docker exec malachy-wp wp --allow-root malachy seed
   ```

4. **Configure contact form email:**
   - **Settings → General** → set the **Administration Email Address**
   - Or configure a proper SMTP plugin (recommended for production)

5. **Verify sections render:**
   - Visit the front page
   - Check all 7 sections load without JS errors

6. **Check mobile rendering:**
   - Resize browser or use DevTools mobile emulation
   - Animations are disabled on mobile — verify graceful degradation

7. **Clear any object cache:**
   ```bash
   wp cache flush
   ```

---

## Automated CI Packaging

For continuous deployment pipelines, `package.sh` can be integrated into CI:

### GitHub Actions Example

```yaml
name: Package Theme

on:
  push:
    tags:
      - 'v*'

jobs:
  package:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Package theme
        run: bash malachy-portfolio/bin/package.sh
      - name: Upload artifact
        uses: actions/upload-artifact@v4
        with:
          name: malachy-portfolio-v${{ github.ref_name }}
          path: malachy-portfolio/malachy-portfolio-v*.zip
```

### Expected ZIP Size

| Build | Size |
|-------|------|
| Minimal (production) | ~1.8–2.1 MB |
| With source maps | ~2.5–3.0 MB |
| With images only | ~500 KB–1 MB |

---

## Verifying the Package

After building, verify the ZIP is valid before uploading:

```bash
# Check integrity
unzip -l malachy-portfolio-v1.1.0.zip | head -20

# Verify no unwanted files
unzip -l malachy-portfolio-v1.1.0.zip | grep -E '(node_modules|\.git|docs/)'

# Check size — should be under 3 MB
ls -lh malachy-portfolio-v1.1.0.zip

# Confirm style.css is at the top level
unzip -l malachy-portfolio-v1.1.0.zip | grep 'style.css'
```
