---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-053 — Mobile dotted progression re-enabled for the jobs showcase (swipe affordance)
date: 2026-09-24
from: MiMo-V2.6-Pro (Architect) / MiMo-V2.6-Flash (Implementer)
to: Claude[Sonnet] Web (Reviewer) / ChatGPT (Co-reviewer)
status: COMPLETE on staging (v1.3.21) — NOT committed, NOT pushed, NOT deployed to production
priority: NORMAL
---

## 0. Context

Malachy corrected an earlier decision: the jobs showcase had no progression
indicator on mobile, so visitors had no cue the cards were swipeable or which
direction to swipe. HO-051 hid `.jc-dots` below 768px (counter only). This
handover re-enables a dotted progression on mobile (and tablet), each dot
mapped 1:1 to a card/image, with the active indicator moving as the visitor
swipes.

## 1. Change

**`assets/css/main.css`**

- Mobile (≤767px): `.jc-dots` is now a centered overlay at the bottom of the
  full-viewport card (`position:absolute; left:50%; bottom:26px;
  transform:translateX(-50%)`, blurred translucent pill). Dots are 6px, the
  active dot a 20px primary pill; touch-friendly (9px gap, 14px padding).
  `.jc-card-body` gains `padding-bottom:66px` so the write-up clears it.
- Tablet (≥768px): the dot rules (previously desktop-only) moved into the
  tablet block, so tablet — also swipe-driven, no autoplay — shows the same
  dots under the left-column copy. Desktop inherits it.
- Removed the duplicated dot rules from the ≥1200px block (now only the
  card-size and `--jc-*` vars remain there).

**`assets/js/animations/projects.js`**

- Dot click now branches: on mobile it pages the native scroll-snap track
  (`track.scrollTo({ left: i * clientWidth, behavior:'smooth' })`) instead of
  running the desktop deck layout (which would have written inline transforms
  and broken the mobile track). Tablet/desktop keep `goTo(i)` (deck + 5s
  autoplay pause/resume).
- Swipe sync already flowed through `syncFromScroll()` → `updateChrome()`,
  which drives `aria-current` on the dots — so the active pill tracks the
  swipe live.

**Version** → 1.3.21 (`functions.php` + `style.css`).

## 2. Verification (staging, raw output)

Mobile 375×812:
```
before: dotsDisplay flex, dotCount 6, dotsRect {x:126,y:762,w:123,h:24}, active 0
after clicking dot[3]: trackScrollLeft 1125 (= 3 × 375), active 3, counter "04 / 06"
console errors: 0
```
Screenshots: `ho053-mobile-dots.png` (card 1 — first dot is the active pill),
`ho053-mobile-dot3.png` (card 4 — active pill moved to the 4th dot).

Tier check (display / position / x):
```
tablet  1024: flex, static, x=48  (left column) ; mode=tablet ; copy=block
desktop 1440: flex, static, x=60  (left column) ; mode=desktop ; copy=block
mobile   375: flex, absolute, x=126 (centered overlay) ; mode=mobile ; copy=none
```

## 3. Remaining

- Malachy reviews staging (dot styling/size, overlay position on mobile).
- Tablet + desktop showcase sign-off still pending (carried from HO-052 §7/§9).
- Production deploy remains its own tracked step, gated on Malachy's go-ahead.
