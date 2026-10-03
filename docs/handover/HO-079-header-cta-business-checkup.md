---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-079 — Header CTA becomes the Business Checkup entry; nav item removed
date: 2026-10-03
from: Hermes (implementation agent)
to: Malachy (approval of the OPEN items) / Claude Web (review)
status: IMPLEMENTED — tests green, committed locally, NOT pushed, NOT deployed
priority: MEDIUM
---

Answer to Malachy's instruction: the `Business Checkup` menu item is removed and the
header `Let's talk` CTA becomes the `Business Checkup` link. Two hunks in one file.

---

## A. What changed

One file: `malachy-portfolio/template-parts/navigation.php`

```diff
@@ -12,7 +12,6 @@
   array( 'href' => '/#contact', 'label' => __( 'Contact', … ), 'section' => 'contact' ),
   array( 'href' => '/blog', 'label' => __( 'Blog', … ), 'route' => true ),
-  array( 'href' => '/business-checkup/', 'label' => __( 'Business Checkup', … ), 'route' => true ),
 );

@@ -34,8 +34,8 @@
   <div class="nav-actions">
-    <a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="header-cta" data-section-link="contact">
-      <?php esc_html_e( "Let's talk", 'malachy-portfolio' ); ?>
+    <a href="<?php echo esc_url( home_url( '/business-checkup/' ) ); ?>" class="header-cta" data-section-link="">
+      <?php esc_html_e( 'Business Checkup', 'malachy-portfolio' ); ?>
       <svg …arrow…></svg>
     </a>
```

The arrow icon is kept — appropriate for a link that now leaves the page. `Let's talk` no
longer appears anywhere in the theme (repo-wide grep, 0 hits): the CTA was its only instance.

## B. The shared-code check (why `data-section-link=""`)

`data-section-link` is not decoration — it is the hook for the smart-anchor handler in
`assets/js/animations/navigation.js:87-95`:

```js
document.querySelectorAll('a[data-section-link]').forEach(function (link) {
  link.addEventListener('click', function (e) {
    var sectionId = this.getAttribute('data-section-link');
    if (!sectionId) return;                    // <- empty string = no interception
    var target = document.getElementById(sectionId);
    if (target) { e.preventDefault(); …smooth scroll… }
```

Leaving `data-section-link="contact"` on the CTA would have `preventDefault()`'d the click and
smooth-scrolled to the contact section instead of navigating to the checkup — the page would
have looked broken while the markup read correctly. Empty string makes the handler return early,
which is exactly how the existing route links (`/blog`, and the removed checkup item) behave:
the nav template renders `data-section-link=""` for every `route => true` entry.

This is the repo's recurring failure class ("shared code validated against only one caller"),
so the click was verified end-to-end rather than reasoned about (§C).

## C. Verification (raw output)

```
$ docker run --rm -v …/malachy-portfolio:/t php:8.2-cli php -l /t/template-parts/navigation.php
No syntax errors detected in /t/template-parts/navigation.php

$ curl -s http://172.18.0.11/ | <parse>
nav items: ['Work', 'Experience', 'Contact', 'Blog']
cta href   : https://imadconsult.zubbystudio.site/business-checkup/
cta section: ''            # the no-op value, not "contact"
cta label  : Business Checkup

$ curl -o /dev/null -w '%{http_code}' http://172.18.0.11/business-checkup/
/business-checkup/ HTTP 200

$ node --test tests/unit/business-checkup.test.js
tests 34 | pass 34 | fail 0 | duration_ms 109
```

Browser click test (Playwright, on the live-rendered page, reading back after the click):

```
{ "url": "/business-checkup/", "title": "Business Checkup – IMAD Consulting",
  "checkupRoot": true, "scrolled": 0,
  "cta": "Business Checkup", "sectionAttr": "\"\"" }
```

`scrolled: 0` plus the changed `location.pathname` is the evidence that the click navigated
rather than scrolling — the failure mode §B guards against.

Disclosure: my first run of the unit suite used `node --test tests/unit/` (directory form) and
reported a red suite. That was my invocation, not a regression — Node treats a bare directory
argument differently; run against the file it is 34/34. Stated because the raw output looked
like a failure and it was not.

## D. Viewport matrix — measured, and an OPEN decision

`assets/css/main.css:2324`, inside `@media (max-width: 800px)`, hides the CTA:

```css
.header-cta { display: none; }
```

Playwright reachability probe against the running instance:

| viewport | CTA visible | hamburger | nav panel opens | nav items | checkup link visible |
|---|---|---|---|---|---|
| desktop 1280 | **yes** | no | – | Work, Experience, Contact, Blog | **yes** (the CTA) |
| tablet 768 | **no** | yes | yes | Work, Experience, Contact, Blog | **no** |
| mobile 390 | **no** | yes | yes | Work, Experience, Contact, Blog | **no** |

**Consequence: at ≤800px the checkup now has no reachable entry point.** The nav entry was its
only mobile path, and the CTA is hidden at that width. Note the CTA was *already* hidden on
mobile, so `Let's talk` was likewise unreachable there — the nav's `Contact` item covered that
case; nothing covers the checkup now.

This is a decision only you can make. Options:

- **(a) Show the CTA at ≤800px** — drop or scope `main.css:2324`. Crowds the header
  (wordmark + CTA + theme toggle + hamburger at 390px); a shorter label on mobile would help.
- **(b) Mobile-only nav entry** — keep an entry with a class shown only at ≤800px, so the
  desktop menu stays exactly `[Work | Experience | Contact | Blog]` while phones keep a path.
  Slight deviation from "totally removed from the menu list" (mobile only).
- **(c) Leave as-is** — mobile traffic reaches the checkup only via direct links
  (`/business-checkup/` pasted, QR, or a campaign link). Nothing in the current funnel points
  there, so a QR/WhatsApp link aimed at the checkup would need to be built separately.

Recommendation: **(b)** — it satisfies the desktop intent exactly and does not touch the header
layout or the mobile baselines. Not implemented; awaiting your call.

## E. Visual baselines

Unchanged in status, changed in shape. The 15 shared baselines are still stale, but now because
of the CTA (label + href, desktop only) rather than the removed nav item — the nav line reverts
to what the baselines originally had. Regeneration is still a deliberate, still-open step
(HO-064 §F.4) and must run against the production base URL, not this clone.

## F. What is still needed before this is live

1. Your go-ahead → push (this joins the 9-commit unpushed stack; `4845d26` business-checkup,
   `66575ff` .htaccess hardening, the HO docs).
2. Create the production page (slug `business-checkup`, template
   `template-business-checkup.php`) — production still returns **404** for it. Without the page
   the CTA leads to a 404, which is worse than the nav item it replaces.
3. Malachy's manual `git pull` on the InMotion host, then a Cloudflare purge.
4. Baseline regeneration (§E), if the visual gate matters on this release.
5. The §D decision above.

## G. Git state

- Changed: `malachy-portfolio/template-parts/navigation.php` (2 hunks, 1 removed line + 2 changed)
- Untracked and **not mine**: `ram_audit.sh` (pre-existing, left alone)
- Committed locally only. **Nothing pushed, nothing deployed.**

## H. Limitations

- Verified against the local clone (`http://172.18.0.11`), not staging or production.
- No automated test covers nav composition or the header CTA — this change is protected by the
  visual baselines (stale) and by manual verification only. Adding a cheap assertion on the nav
  labels + CTA href would close that gap; not done, since `tests/` layout is HO-063's surface.
- The checkup page's own behaviour is unchanged and untested here; only its entry point moved.
