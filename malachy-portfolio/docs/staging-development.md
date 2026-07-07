# Malachy Portfolio — Staging & Development (Docker Compose)

**Version:** 1.1.0  
**Theme Root:** `/home/zubbyik/wordpress_project/malachy-portfolio/`  
**Domain:** https://imadconsult.zubbystudio.shop

This document describes the local Docker-based development and staging workflow for the Malachy Portfolio WordPress theme.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Prerequisites](#prerequisites)
3. [Starting the Stack](#starting-the-stack)
4. [Stopping the Stack](#stopping-the-stack)
5. [Theme File Workflow](#theme-file-workflow)
6. [WP-CLI & Theme Development](#wp-cli--theme-development)
7. [Database Operations](#database-operations)
8. [Traefik & HTTPS](#traefik--https)
9. [Viewing Logs](#viewing-logs)
10. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│  docker-compose.yml  (project root)                  │
│                                                       │
│  malachy-wp (wordpress:6.7-php8.2-apache)             │
│  ├── ./wp-data/          →  /var/www/html              │
│  ├── ./malachy-portfolio/ →  /var/www/html/wp-content │
│  │                           /themes/malachy-portfolio/│
│  └── environment: DB_HOST, DB_USER, etc.              │
│                                                       │
│  malachy-db (mysql:8.0)                               │
│  ├── ./db-data/          →  /var/lib/mysql             │
│  └── database: malachy_portfolio                      │
│                                                       │
│  network: openagile_openagile_network (traefik-public) │
│  (external — Traefik reverse proxy provides HTTPS)     │
└─────────────────────────────────────────────────────┘
```

### Key Points

- **Theme is a bind-mount** — changes to local theme files appear immediately in the container. No rebuild needed.
- **Database persists** in `./db-data/` (host volume).
- **Traefik** handles SSL termination and domain routing.
- **WP Debug is ON** — `WP_DEBUG` and `WP_DEBUG_LOG` are enabled by default.

---

## Prerequisites

- Docker Engine 24+ and Docker Compose v2
- A Traefik reverse proxy running on the `openagile_openagile_network` (or adjust the network name)
- DNS record pointing `imadconsult.zubbystudio.shop` → host IP
- Git (for version control of theme files)

---

## Starting the Stack

```bash
cd /home/zubbyik/wordpress_project

# Start both services in detached mode
docker compose up -d

# Verify both containers are running
docker compose ps

# Check WordPress is responding
curl -I https://imadconsult.zubbystudio.shop
```

Expected output:
```
NAME                IMAGE                         STATUS   PORTS
malachy-wp          wordpress:6.7-php8.2-apache   Up       80/tcp
malachy-db          mysql:8.0                     Up       3306/tcp
```

### First-Time Startup

On the initial `docker compose up -d`, WordPress will:

1. Connect to MySQL and create tables
2. Apply the configuration from `WORDPRESS_CONFIG_EXTRA` (domain, debug settings)
3. The theme is already mounted — activate it via WP-CLI or the admin UI

---

## Stopping the Stack

```bash
# Stop containers (data preserved)
docker compose down

# Stop + remove volumes (DESTROYS DATABASE)
docker compose down -v

# Stop + rebuild from scratch
docker compose down -v && docker compose up -d
```

> Use `docker compose down -v` only when you want a completely fresh WordPress install. All posts, CPTs, and settings will be lost.

---

## Theme File Workflow

### How Mounting Works

The `docker-compose.yml` mounts the theme directory:

```yaml
volumes:
  - ./malachy-portfolio:/var/www/html/wp-content/themes/malachy-portfolio
```

Any edit to files under `./malachy-portfolio/` is **immediately reflected** in the running WordPress instance. No restart, no rebuild.

### Recommended Development Loop

```bash
# 1. Edit a theme file
vim malachy-portfolio/template-parts/section-hero.php

# 2. Reload the browser — changes are live instantly
# (Hard refresh: Ctrl+F5 or Cmd+Shift+R)

# 3. Check for PHP errors
docker compose logs malachy-wp --tail=20

# 4. Check for JS errors in browser DevTools Console
```

### CSS/JS Changes

- CSS edits in `assets/css/main.css` are live immediately (no build step).
- JS edits in `assets/js/animations/*.js` require a hard browser refresh to bust cache.
- GSAP vendor files should not be edited — they are CDN-downloaded copies.

### Adding a New Section

```bash
# 1. Create the template part
touch malachy-portfolio/template-parts/section-new.php

# 2. Add to front-page.php
#    Insert: get_template_part( 'template-parts/section-new' );

# 3. Create animation module
touch malachy-portfolio/assets/js/animations/new.js

# 4. Enqueue in functions.php (under is_front_page() block)
#    wp_enqueue_script('malachy-anim-new', ...);

# 5. Add nav link in template-parts/navigation.php
```

---

## WP-CLI & Theme Development

WP-CLI is available inside the WordPress container.

### Common WP-CLI Commands

```bash
# Open an interactive WP-CLI shell
docker exec -it malachy-wp wp --allow-root shell

# Run a single WP-CLI command
docker exec malachy-wp wp --allow-root plugin list
docker exec malachy-wp wp --allow-root theme list

# Seed CPTs with demo content
docker exec malachy-wp wp --allow-root malachy seed

# Flush permalinks (after CPT registration changes)
docker exec malachy-wp wp --allow-root rewrite flush

# List users
docker exec malachy-wp wp --allow-root user list

# Search-replace URLs (after domain change)
docker exec malachy-wp wp --allow-root search-replace 'old.domain' 'new.domain'

# Update WordPress core
docker exec malachy-wp wp --allow-root core update
```

### Interactive Shell

```bash
docker exec -it malachy-wp wp --allow-root shell
```

This opens a PHP interactive shell with WordPress fully bootstrapped. Useful for:

- Testing functions before writing them to files
- Inspecting post meta, options, or user data
- Debugging queries

---

## Database Operations

### Backup the Database

```bash
docker exec malachy-db mysqldump \
  -u wordpress \
  -pwordpress_pass \
  malachy_portfolio \
  > /home/zubbyik/wordpress_project/backups/malachy-$(date +%Y%m%d-%H%M%S).sql
```

### Restore a Backup

```bash
cat /home/zubbyik/wordpress_project/backups/malachy-20260707-120000.sql \
  | docker exec -i malachy-db mysql -u wordpress -pwordpress_pass malachy_portfolio
```

### Reset WordPress (Factory Fresh)

```bash
docker compose down -v           # Wipes db-data volume
docker compose up -d             # WordPress runs setup wizard
```

### Access MySQL Client

```bash
docker exec -it malachy-db mysql -u wordpress -pwordpress_pass malachy_portfolio
```

---

## Traefik & HTTPS

The stack relies on an **external Traefik** network for HTTPS termination.

### Traefik Labels (in docker-compose.yml)

```yaml
labels:
  - "traefik.enable=true"
  - "traefik.http.routers.malachy-wp.rule=Host(`imadconsult.zubbystudio.shop`)"
  - "traefik.http.routers.malachy-wp.entrypoints=websecure"
  - "traefik.http.routers.malachy-wp.tls.certresolver=cloudflare"
  - "traefik.http.services.malachy-wp.loadbalancer.server.port=80"
```

### If You Change the Domain

1. Update `traefik.http.routers.malachy-wp.rule` to `Host(\`newdomain.com\`)`
2. Update `WORDPRESS_CONFIG_EXTRA` environment variables:
   ```
   define('WP_HOME', 'https://newdomain.com');
   define('WP_SITEURL', 'https://newdomain.com');
   ```
3. Run WP-CLI search-replace to update URLs in the database:
   ```bash
   docker exec malachy-wp wp --allow-root search-replace \
     'imadconsult.zubbystudio.shop' 'newdomain.com'
   ```
4. Restart the stack: `docker compose restart`

---

## Viewing Logs

```bash
# Follow WordPress logs
docker compose logs -f malachy-wp

# Follow database logs
docker compose logs -f malachy-db

# Last 50 lines with timestamps
docker compose logs --tail=50 -t malachy-wp

# PHP error log (inside container)
docker exec malachy-wp tail -f /var/www/html/wp-content/debug.log

# Apache access log
docker exec malachy-wp tail -f /var/log/apache2/access.log

# Apache error log
docker exec malachy-wp tail -f /var/log/apache2/error.log
```

### Debug Log

`WP_DEBUG_LOG` is enabled, writing to `/var/www/html/wp-content/debug.log`. Since the `wp-data` volume is mounted, this log is accessible from the host:

```bash
tail -f /home/zubbyik/wordpress_project/wp-data/wp-content/debug.log
```

---

## Troubleshooting

| Problem | Check | Fix |
|---------|-------|-----|
| Container won't start | `docker compose logs malachy-wp` | Check database connection credentials in `.env` or docker-compose.yml |
| Database connection refused | `docker compose logs malachy-db` | MySQL takes ~30s to initialize on first run; wait and retry |
| Theme not appearing | `docker exec malachy-wp wp --allow-root theme list` | Verify mount path; check file permissions (`chmod 644` on files) |
| "sendmail" warning in logs | No MTA in container | Ignore in development; configure sendmail/postfix in production |
| 404 on front page | Permalinks not set | `docker exec malachy-wp wp --allow-root rewrite flush` |
| Permission denied on theme files | Host-side ownership mismatch | `chown -R 33:33 malachy-portfolio/` or use ACL |
| HTTPS not working | Traefik / DNS | Verify Traefik is running on the external network; check DNS propagation |
| Changes not reflected in browser | Cache | Hard refresh (Ctrl+F5); check that file was saved on host |
| "Error establishing database connection" | DB container unhealthy | `docker compose restart malachy-db`; wait 10s; restart malachy-wp |
