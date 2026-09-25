---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-048 — Backfill: Web Development title rename + hero role links + v1.3.14 (commits 6389f46, 828e852)
date: 2026-09-23
from: MiMo-V2.6-Pro (Architect)
to: Claude[Sonnet] Web (Reviewer) / ChatGPT (Co-reviewer)
status: COMPLETE — both commits already live on production; this document backfills the missing handover
priority: NORMAL
---

## 1. Why this handover exists

Commits `6389f46` and `828e852` (both 2026-09-23) shipped to production
without a corresponding `HO-*.md`. Standing rule 5 requires a handover for
every completed unit of work; this backfills that gap. The work itself is
already production-live — verified today with raw output below.

## 2. What was committed (raw git output)

```
commit 6389f46f9b003c44f01c05109d3a1cae2257bbe6
    feat: title includes Web Development, hero roles link to landing pages

    - Title: 'IMAD Consulting — QA Engineer, Web Development & IT Support Specialist'
    - Hero roles now clickable: QA Engineer → /#work, Web Development → /#offers,
      IT Support Specialist → /#offers
    - 'System Admin' renamed to 'Web Development' per Malachy's request

 malachy-portfolio/functions.php                   | 2 +-
 malachy-portfolio/template-parts/section-hero.php | 6 +++---
 2 files changed, 4 insertions(+), 4 deletions(-)

commit 828e852862bae40a222fb8d64a9deacba315a17d
    feat: hero role link styling + bump version 1.3.14

    - Hero role anchors get dotted underline + hover color (primary)
    - Version bump to 1.3.14 for cache busting

 malachy-portfolio/assets/css/main.css | 12 +++++++++---
 malachy-portfolio/functions.php       |  2 +-
 malachy-portfolio/style.css           |  2 +-
 3 files changed, 14 insertions(+), 2 deletions(-)
```

Driven by Malachy's request: replace "System Admin" with "Web Development"
in the visible role/title strings, make the hero role words navigable, and
bump the theme version so asset caches bust.

## 3. Production verification (raw output, 2026-09-23)

```
=== PRODUCTION HOME ===
contact.js?ver=1.3.14
<title>IMAD Consulting — QA Engineer, Web Development &#038; IT Support Specialist</title>
```

Both commits are live on `imadconsulting.co.uk`. (The production page still
shows other stale strings — meta description `Portfolio · 2026`, GSC
placeholder — those are covered by HO-049, not this backfill.)

## 4. Known gap this backfill does NOT close

`6389f46` renamed the hero roles and the `<title>` string, but left two
brand strings stale (JSON-LD `jobTitle` and the meta-description fallback
text still said "SysAdmin"/"system administration"). Fixed in HO-049.

## 5. Status

- Code: production-live (v1.3.14)
- Handover: this document (was missing until now)
- No open items for this unit of work
