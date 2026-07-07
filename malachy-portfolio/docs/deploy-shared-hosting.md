# Deploy Malachy Portfolio to Shared Hosting

Step-by-step guide to deploying the theme on shared hosting (cPanel, DirectAdmin, etc.).

---

## Prerequisites

- A shared hosting account with **PHP 8.0+** and **MySQL 8.0+ / MariaDB 10.4+**
- A WordPress installation (clean or existing)
- FTP/SFTP credentials or cPanel access

---

## Method 1: WP Admin Upload (Easiest)

### 1. Package the Theme

From the theme root, run the packaging script:

```bash
cd /home/zubbyik/wordpress_project/malachy-portfolio
bash bin/package.sh
```

This creates `../malachy-portfolio-v1.0.0.zip` — a clean zip with dev files stripped (`.git`, `node_modules`, `docs`, config files, source maps).

### 2. Upload via WP Admin

1. Log into the target WordPress admin dashboard.
2. Go to **Appearance → Themes → Add New → Upload Theme**.
3. Choose `malachy-portfolio-v1.0.0.zip`.
4. Click **Install Now**, then **Activate**.

---

## Method 2: cPanel File Manager

### 1. Package the Theme

```bash
bash /home/zubbyik/wordpress_project/malachy-portfolio/bin/package.sh
```

### 2. Upload via cPanel

1. Log into cPanel.
2. Open **File Manager** and navigate to `public_html/wp-content/themes/`.
3. Upload the `.zip` file.
4. Right-click → **Extract**.
5. Verify the folder is named `malachy-portfolio/`.

### 3. Activate

Go to **Appearance → Themes** in WP Admin and activate **Malachy Portfolio**.

---

## Method 3: FTP/SFTP

### 1. Prepare the Files

```bash
# Run the build script for a clean copy
bash /home/zubbyik/wordpress_project/malachy-portfolio/bin/build.sh
```

The built theme is at `../build/malachy-portfolio/` (the `build/` directory is one level above the theme root).

### 2. Upload via FTP

1. Connect to your host with an FTP client (FileZilla, Cyberduck, etc.).
2. Navigate to `/public_html/wp-content/themes/`.
3. Upload the entire `malachy-portfolio/` folder.

### 3. Activate

Activate via WP Admin → Appearance → Themes.

---

## Post-Deployment Steps

### 1. Set the Front Page

- **Settings → Reading → Your homepage displays → Your latest posts** (ensures `front-page.php` is used).
- Save changes.

### 2. Flush Permalinks

- **Settings → Permalinks → Post name → Save Changes**.
- This flushes rewrite rules for CPTs and taxonomies.

### 3. Seed Content (Optional)

If you have WP-CLI access on the host:

```bash
wp malachy seed
```

Otherwise, add content manually via WP Admin dashboard.

### 4. Configure Theme Settings

Go to **Portfolio** in the admin sidebar and set:
- **Portrait Image URL** — Upload a new portrait via Media Library or keep the default.
- **Hero Subtitle** — Update the tagline (default: `Portfolio · 2026`).
- **Resume/CV Download URL** — Upload a PDF and paste its URL.

### 5. Test the Contact Form

Fill out the contact form on the live site and verify the email arrives. If it doesn't:

- Check that `wp_mail()` works on your host (some shared hosts block it).
- Install an SMTP plugin (WP Mail SMTP, Post SMTP, FluentSMTP).
- Configure the recipient in **Settings → General → Administration Email Address**.

---

## Migrating from Another Host

### Export Content

On the **old host**:

1. **Tools → Export → All content** → Download the XML file.
2. **Settings → Permalinks** — note your permalink structure.

### Import Content

On the **new host**:

1. Install and activate the Malachy Portfolio theme.
2. **Tools → Import → WordPress** → install the WordPress Importer plugin.
3. Upload the XML file, assign authors, and check "Download and import file attachments".
4. Set permalinks to **Post name**.
5. Re-seed or re-enter CPT content (Projects, Skills, Experience) manually or via WP-CLI.

---

## Performance Checklist

| Task | Details |
|---|---|
| Image optimisation | Compress portrait and project images before upload. WebP variants are already provided. |
| Caching | Install a caching plugin (WP Rocket, W3 Total Cache, or LiteSpeed Cache if on LiteSpeed). |
| CDN | Optional — serve Google Fonts locally or via a CDN for reduced latency. |
| Minification | CSS is already a single file. The main.css can be minified with a caching plugin. |
| PHP version | Ensure the host runs PHP 8.0+ for optimal performance. |

---

## Troubleshooting

| Symptom | Likely Fix |
|---|---|
| Theme not showing in list | Theme folder name must be `malachy-portfolio` (all lowercase, exact). |
| White page after activation | PHP 8.0+ required. Check error log, try switching to a default theme first. |
| 404 on project/skill URLs | Go to **Settings → Permalinks → Post name → Save** to flush rewrites. |
| GSAP animations not working | GSAP is served from the bundled vendor files. Check browser console for 404s on `gsap.min.js`. |
| Contact form submits but no email | Install an SMTP plugin. Shared hosts commonly block `wp_mail()`. |
| "Failed to load" font icons | Google Fonts may be blocked by country. Download fonts locally and update `functions.php`. |

---

## Updating the Theme

The theme is not distributed via the WordPress.org repo. To update:

1. Re-package with `bash bin/package.sh`.
2. Upload the new zip via **WP Admin → Appearance → Themes** (the old version is auto-replaced).
3. Or manually overwrite the theme folder via FTP/cPanel.
