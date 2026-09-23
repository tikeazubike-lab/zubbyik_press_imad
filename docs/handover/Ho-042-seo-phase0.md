---
type: HANDOVER
project: IMAD Consulting — SEO
title: HO-042 — SEO Phase 0: stale Google index confirmed, server-side checks tasked
date: 2026-09-22
from: Claude (reviewer)
to: MiMo-V2.6-Pro (architect) / MiMo-V2.6-Flash (implementer)
status: TASKED — external findings confirmed, server-side verification needed
priority: HIGH (the stale-index issue actively harms search visibility right now)
---

## 1. What's confirmed from outside the server

A `site:imadconsulting.co.uk` search shows Google's indexed content for
this domain is **unrelated to the actual site** — a "The Stories Book"
page (vintage photography commentary) and a "sample-page" returning
"nothing was found," with a metadata timestamp around December 2023.
The live site (fetched directly) is the correct, current portfolio theme
— this is a stale-index problem, not a live-content problem. Almost
certainly leftover from whatever ran on this domain before the current
theme.

**Also found directly from the live homepage:**
- Title tag: `Reliable – QA Engineer, SysAdmin & IT Support – Portfolio · 2026`
  — "Reliable" reads as a leftover theme name; **"IMAD Consulting" appears
  nowhere in the title**.
- Meta description: literally `Portfolio · 2026` — no value proposition,
  no keywords.
- Genuinely good existing material: the `/offers/` pages
  (`fix-business-emails-going-to-spam`,
  `audit-and-report-on-your-business-email-security-and-deliverability`,
  etc.) are specific and intent-matched — better SEO raw material than
  most consultant sites start with. Four testimonials on the homepage,
  currently carrying no schema markup.

## 2. What needs server access — tasked to you

I can't verify these from outside; they need to be checked directly.
Report each with raw output, not a summary:

```bash
# 1. robots.txt — confirm nothing is accidentally blocking crawlers
curl -s https://imadconsulting.co.uk/robots.txt

# 2. sitemap — does one exist, is it valid, is it referenced in robots.txt
curl -s https://imadconsulting.co.uk/sitemap.xml | head -50
grep -i sitemap <(curl -s https://imadconsulting.co.uk/robots.txt)

# 3. Search Console verification — is this property actually verified?
# Check for a verification meta tag or file:
curl -s https://imadconsulting.co.uk | grep -i "google-site-verification"
curl -s https://imadconsulting.co.uk/google*.html -o /dev/null -w "%{http_code}\n"

# 4. Canonical tags — confirm no accidental cross-domain or wrong-page canonicals
curl -s https://imadconsulting.co.uk | grep -i "rel=\"canonical\""

# 5. Check for a leftover/parked-domain artifact explaining the stale index —
#    look for old theme files, a second WP install, or an .htaccess redirect
#    that might explain why Google ever indexed photography-book content
find ~/wordpress_project -iname "*stories*" -o -iname "*photography*" 2>/dev/null
grep -ri "stories book" ~/wordpress_project -l 2>/dev/null

# 6. Confirm which SEO plugin (if any) is active — determines what's
#    actually controllable (Yoast/RankMath both handle sitemap+meta+schema)
grep -ri "yoast\|rank.math\|seo" ~/wordpress_project/malachy-portfolio/wp-content/plugins/ -l 2>/dev/null | head -5
```

## 3. Once §2 comes back, do these (in order)

1. **If no SEO plugin is active**, install RankMath or Yoast — don't hand-
   roll sitemap/meta generation when a well-maintained plugin does it
   correctly.
2. **If Search Console isn't verified**, verify it (usually a DNS TXT
   record via Cloudflare, or a meta tag/file — whichever the plugin
   supports), submit the sitemap, and use "Request Indexing" on the
   homepage and the `/offers/` pages specifically to force a recrawl
   rather than waiting.
3. **Rewrite the homepage title and meta description** to include "IMAD
   Consulting" and an actual value proposition — this is the cheapest,
   highest-leverage fix available and doesn't depend on anything else in
   this list.
4. **Investigate §2 item 5's result carefully** — if there's a genuine
   explanation for the stale photography content (an old theme never
   fully removed, a stale `.htaccess` rule), that's worth fixing at the
   root rather than just waiting for Google to eventually notice the
   current content is different.

## 4. Not in Phase 0 — later phases, don't start yet

Schema markup, `/offers/` page keyword optimization, blog content
strategy, Google Business Profile, analytics setup. These come after
Phase 0 confirms the basics (crawlable, indexed correctly, verified in
Search Console) are actually in place — no point optimizing content for
search engines that currently have the wrong content indexed.

## 5. Status

Tasked. Report §2's raw output, then proceed through §3 in order.
