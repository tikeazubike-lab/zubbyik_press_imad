---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-058 — Live exposure: the repo checkout sits inside the web root; docker-compose.yml with DB password literals, the handovers, and open directory indexes were publicly readable
date: 2026-09-26
from: Hermes (orchestrator)
to: Malachy (pull, rotate, decide) / Claude[Sonnet] Web (Reviewer)
status: FIX PREPARED, NOT COMMITTED — production is STILL EXPOSED as of this document; nothing has been pushed or deployed
priority: HIGH
---

## 0. Why this document exists

Malachy corrected the production repository location in session. Chasing that
correction turned up a live exposure that is independent of the correction and
predates it. This document records the raw evidence, the severity as measured
(not assumed), the prepared fix, and what only Malachy can do.

**Deployment status: NONE.** The fix is staged in the working tree only. No
commit, no push, no server change.

---

## 1. The correction that prompted this — repository location

The production checkout is **not** at `wp-content/themes/malachy-portfolio/`. It is:

```
/home/imadco5/public_html/wp-content/themes/zubbyik_press_imad/        <- git checkout (repo root)
/home/imadco5/public_html/wp-content/themes/zubbyik_press_imad/malachy-portfolio/   <- the live theme
```

i.e. the git checkout is a **sibling directory inside `themes/`**, and the theme
WordPress actually serves is a **nested subdirectory** of it.

Evidence (HTTP, no server access needed) — `style.css` is the definitive test:

| URL | Status |
|-----|--------|
| `/wp-content/themes/zubbyik_press_imad/malachy-portfolio/style.css` | **200** |
| `/wp-content/themes/zubbyik_press_imad/style.css` | 404 |
| `/wp-content/themes/malachy-portfolio/style.css` | 404 |

Every asset URL in the live HTML resolves under
`/wp-content/themes/zubbyik_press_imad/malachy-portfolio/...`.

This supersedes the `themes/malachy-portfolio/...` path assumption used earlier
in this session and in earlier documents. HO-057 §59-61 reached the same
correction independently. **Any handover or instruction that names
`/wp-content/themes/malachy-portfolio/` is wrong.**

### 1.1 Production is otherwise current

- Live `?ver=` is **1.3.21** (= theme v1.3.21, local HEAD).
- Production's `docker-compose.yml` is byte-identical to local HEAD —
  md5 `1bb13610a0d8c758c04650f75683184b` for both. So the pull is complete,
  not partial.
- The `f2a2981` image fix is live and verified **by content, not status code**:
  `work-specforge-tooling-800.webp` → `Content-Type: image/webp`, 31 756 bytes,
  magic bytes `RIFF....WEBP`. (A status-code-only check would have been unsafe
  here — see §3.3.)
- `meta name="description"` is the curated IMAD Consulting text; the
  `YOUR_VERIFICATION_TOKEN` placeholder is gone.

---

## 2. Raw evidence of the exposure

Base URL for every path below: `https://imadconsulting.co.uk/wp-content/themes/zubbyik_press_imad`

### 2.1 Credential disclosure — `docker-compose.yml`

```
GET /docker-compose.yml   ->  200, 2991 bytes
  line 14: WORDPRESS_DB_PASSWORD:  <- value served, 48 hex chars
  line 45: MYSQL_PASSWORD:         <- value served, 48 hex chars
  line 46: MYSQL_ROOT_PASSWORD:    <- value served, 48 hex chars
```

Values are deliberately **not** reproduced in this document (they are in
tracked `docker-compose.yml:14/45/46`, which is already the subject of HO-056).
The point is that the file is served over the public internet.

### 2.2 Open directory indexes (Apache `Options Indexes` is on)

Confirmed by response title, not by a loose `<a href>` grep:

| Path | Title returned |
|------|----------------|
| `/` (repo root) | `Index of /wp-content/themes/zubbyik_press_imad` |
| `/docs/` | `Index of /wp-content/themes/zubbyik_press_imad/docs` |
| `/docs/handover/` | `Index of /wp-content/themes/zubbyik_press_imad/docs/handover` |
| `/.agent/` | `Index of /wp-content/themes/zubbyik_press_imad/.agent` |
| `/.hermes/` | `Index of /wp-content/themes/zubbyik_press_imad/.hermes` |

Also listable: `/tests/`, `/utility/`, `/imad-automation/`, `/assets/` (all 200).

### 2.3 Plain-text reads of non-public files (all HTTP 200)

`/AGENTS.md`, `/package.json`, `/opencode.json`, `/error_log`, `/vim`,
`/eed-preflight.php`, `/docs/handover/HO-054-hermes-orchestrator-discovery.md`,
`/docs/handover/HO-056-response-to-ho055-review.md`, `/.agent/investigation.md`.

`/error_log` leaks absolute server paths and internal failure detail, e.g.:

```
PHP Fatal error: Uncaught ArgumentCountError: Too few arguments to function
Malachy_Seeder::seed(), 0 passed in
/home/imadco5/public_html/wp-content/themes/zubbyik_press_imad/malachy-portfolio/inc/data-seeder.php
on line 104 and exactly 2 expected ... data-seeder.php:58
```

`/opencode.json` (533 B) discloses the agent/model configuration but contains no
secrets — reviewed in full. `/malachy-portfolio/functions.php` returns **0 bytes**
(PHP executes, no source disclosure). Good.

### 2.4 What is already protected

| Path | Status | Note |
|------|--------|------|
| `/.env` | 406 | blocked, not exposed |
| `/.git/config`, `/.git/HEAD`, `/.gitignore` | 406 | blocked |
| `/.htaccess` | 403 | blocked |
| `/wp-config.php` (WP root) | 403 | blocked |
| `/malachy-portfolio/` (dir request) | 500 | WP `index.php` fatals; no listing, no source |

So the leak is not the `.git` directory or `.env` — it is the compose file, the
docs, the logs, and the indexes.

---

## 3. Severity, as measured rather than assumed

### 3.1 The exposed credentials are the docker stack's, not production's

The served compose file describes the container stack
(`wordpress:6.7-php8.2-apache`, `mysql:8.0`, `imad-automation`, containers
`malachy-wp` / `malachy-db`, network `traefik-public`). The production site is
InMotion shared hosting with its own DB whose credentials live in
`wp-config.php` — which is 403-blocked. So these are the **docker/staging stack's
MySQL credentials**, disclosed.

### 3.2 Not directly internet-exploitable

Port probes from this machine (TCP connect, 6s timeout):

```
api.imadconsulting.co.uk (185.216.177.250)      5432 closed  3306 closed  6379 closed
imadconsulting.co.uk (Cloudflare-fronted)       5432 closed  3306 closed  6379 closed
```

So the disclosed MySQL credentials cannot be used from the public internet
against those hosts as currently configured. **No credential was exercised** —
the values were never used to attempt a connection.

### 3.3 Why status codes alone would have been unsafe here

The repo-root `.htaccess` ends with a WordPress rule that rewrites any
non-existent path to `/index.php`. That is exactly the shape of a rule that turns
missing files into `200 OK` soft-404s. Verified as *not* happening here — a bogus
path returns a genuine `404` with the site's 404 page — but the image check in
§1.1 was therefore re-run against content and magic bytes rather than status.

---

## 4. The prepared fix

`.htaccess` (tracked, repo root) gains a block that denies everything under the
checkout except the theme subdirectory:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{REQUEST_URI} !^/wp-content/themes/zubbyik_press_imad/malachy-portfolio/
  RewriteRule ^ - [F,L]
</IfModule>
```

Reasoning for this shape:

- It closes §2.1, §2.2 and §2.3 with **one** rule — including the repo-root
  index, which a per-path deny list would have missed.
- It uses **mod_rewrite only**, no `Options -Indexes` / `IndexIgnore`. Those live
  under a different `AllowOverride` class; if `AllowOverride Options` is off, they
  would 500 the whole theme directory. Rewrite is already active on this host
  (the WP block in the same file proves it).
- Every live theme asset was verified to resolve under `malachy-portfolio/`, so
  nothing legitimate is denied.
- Reversible: `git checkout .htaccess`.

### 4.1 Test after pulling (30 seconds, in this order)

```bash
B=https://imadconsulting.co.uk/wp-content/themes/zubbyik_press_imad
curl -s -o /dev/null -w '%{http_code}\n' "$B/docker-compose.yml"        # want 403 (was 200)
curl -s -o /dev/null -w '%{http_code}\n' "$B/docs/handover/"            # want 403 (was 200)
curl -s -o /dev/null -w '%{http_code}\n' https://imadconsulting.co.uk/  # want 200 (site up)
curl -s -o /dev/null -w '%{http_code}\n' \
  "$B/malachy-portfolio/assets/css/main.css"                            # want 200 (theme served)
```

If the fourth command stops returning 200, revert immediately with
`git checkout .htaccess` — the rule is denying too much.

---

## 5. What only Malachy can do

1. **Pull** — the fix ships with the normal pull (no server-side edit needed).
2. **Test** with §4.1, in that order, and revert if the theme 404s.
3. **Rotate the three DB passwords** from the docker stack's compose
   (`WORDPRESS_DB_PASSWORD`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`) and update
   `docker-compose.yml` to read them from an untracked `.env` instead of
   literals. Blocking the URL stops *further* disclosure; the values are already
   public, so rotation is the only actual remedy. This is the same finding
   HO-056 sharpened — now with a live URL attached.
4. **Optional, instant, edge-side**: a Cloudflare WAF rule denying
   `/wp-content/themes/zubbyik_press_imad/*` except `malachy-portfolio/*` blocks
   this in seconds without touching the origin, and survives any `.htaccess`
   mistake.
5. **Consider the structural fix** — move the checkout out of the web root
   (e.g. `/home/imadco5/zubbyik_press_imad`) and keep only the theme inside
   `wp-content/themes/`. That removes this entire class of problem instead of
   patching it. It is a bigger change and Malachy's call.

---

## 6. Open questions for Malachy

1. `/eed-preflight.php` (242 bytes, dated 2026-09-26 08:32) exists at the
   production checkout root but **not** in the repo. What is it — a throwaway
   probe, or a tool that should be committed? It is currently also publicly
   readable (and the new rule blocks it).
2. `/malachy-portfolio_bkp/` (dated 2026-09-03) exists on production only, and
   returns 500, not a listing. Leftover from the pre-repo deploy? Safe to delete?
3. HO-057 and `docs/ops/project-reseed-preflight.php` are in this working tree
   **uncommitted** (status DRAFT, "NOT pushed"). They document the same
   production path §1 confirms. Commit them on the same go-ahead as this fix, or
   separately?

---

## 7. Files touched

- `.htaccess` — hardening block appended (§4). Modified, **not committed**.
- `docs/handover/HO-058-...md` — this document. New, **not committed**.
- No application code touched. No theme file touched. Nothing deployed.
