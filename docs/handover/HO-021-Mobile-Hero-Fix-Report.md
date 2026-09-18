# HO-021 Reply — Mobile Hero Composition Fix Report

**Date:** 2026-09-17
**Author:** Claude
**Requested by:** Malachy
**In response to:** HO-020-Mobile-Hero-Composition-fix.md

---

## 1. Files changed

| File | Nature of change |
|---|---|
| `assets/css/main.css` | Mobile hero rules inside `@media (max-width: 800px)` — portrait position, image crop, overlay gradients, location-note positioning. Desktop rules untouched. |
| `functions.php` | Version bump only (1.3.4 → 1.3.9 across commits) |
| `style.css` | Version bump only |

No PHP templates, JS, images, or desktop rules were modified.

---

## 2. Root cause identified

The original mobile hero composition suffered from **two independent CSS problems**:

### Problem A — Portrait too low

`.hero-visual { top: 300px }` in the mobile media query placed the portrait container 300px below the hero top — 188px below the header area. This created a large gap between the text block (ending at ~547px) and the portrait's visible content.

### Problem B — Black top of source portrait never cropped

The source image is **1024×1536 (2:3)**. Band analysis revealed the top ~20% is pure black and the face sits at 25–42% down the frame. The mobile frame (328×632, aspect 0.52) is narrower than the image aspect (0.667), so `object-fit: cover` is always **height-limited** — it can never crop vertically. The dead black top was therefore always rendered, and the face always landed ~25% down, below the headline.

### Problem C — Location text positioned too far down

`.portrait-note { bottom: 11%; right: 16px }` was anchored to the bottom of the 470px-tall visual container, landing at y≈690 — far below the CTA buttons (~547px) and disconnected from the composition.

---

## 3. Changes made

### Iteration 1 — Raise portrait + reposition note

```css
/* Before */
.hero-visual { top: 300px; bottom: 50px; }
.hero-portrait { object-position: 56% top; }
.portrait-note { right: 16px; bottom: 11%; }

/* After */
.hero-visual { top: 148px; bottom: 40px; }
.hero-portrait { object-position: 58% 12%; }
.portrait-note { right: auto; left: calc(50% - 12px); transform: translateX(-50%); bottom: 24%; text-align: center; }
```

**Result:** Note centred and lifted to y≈600. Portrait head still behind text.

### Iteration 2 — Crop the black top

The frame was made taller (148→780) so height-limited cover shows the full image including the black top. Changed to make the image element **118% of the frame** and lift it **-18%**, so the black top is cropped and the head rises into the upper area. Shortened the top gradient fade (32%→18%) so the raised head isn't washed out.

```css
.hero-portrait {
  position: absolute;
  top: -18%;
  left: 0;
  width: 100%;
  height: 118%;
  object-fit: cover;
  object-position: 58% top;
}
.hero-visual::after {
  background: linear-gradient(180deg, var(--background) 0%, transparent 18%, transparent 88%, var(--background) 100%);
}
```

**Result:** Head rose from y≈325 to y≈216 — beside the headline.

### Iteration 3 — Reposition head into the right gap

Measured the head bounding box in the source: **x 39–64%, y 17–34%** (256×261px). At scale 0.486, the head is 124px wide — it fits the ~120px text-free gap on the right (page x 235–359) but was positioned behind the text by `object-position: 58%`. Changed to `object-position: 0%` so the overflow is taken from the left (black region), placing the head at page x 226–350. Added a horizontal left fade to the `::after` overlay to blend the black region seamlessly.

```css
.hero-portrait { object-position: 0% top; }
.hero-visual::after {
  background:
    linear-gradient(180deg, var(--background) 0%, transparent 18%, transparent 88%, var(--background) 100%),
    linear-gradient(90deg, var(--background) 0%, var(--background) 10%, transparent 40%);
}
```

**Result:** Head peers through the right gap, clear of the headline.

### Iteration 4 — Lift 30% higher

The head crown sat at y≈161 while the headline top is y≈147. Increased the upward offset by 30% (-18% → -23.4%) and the height proportionally (118% → 123.4%) to keep the frame bottom covered.

```css
.hero-portrait {
  top: -23.4%;
  height: 123.4%;
}
```

**Result:** Head crown now at y≈127, above the headline top.

---

## 4. Responsive viewport checks

All checks performed with Playwright on staging (`imadconsult.zubbystudio.site`) using `Network.setCacheDisabled` + version-bumped CSS URLs.

| Viewport | Portrait top | Head position (visual) | Note top | Note ↔ CTA gap | Overflow-X |
|---|---|---|---|---|---|
| 360×800 | 148 | Above headline top | 600 | 40px | none |
| 360×700 | 148 | Above headline top | 600 | 40px | none |
| 375×812 | 148 | Above headline top | 600 | 53px | none |
| 390×844 | 148 | Above headline top | 600 | 46px | none |
| 412×915 | 148 | Above headline top | 600 | 39px | none |
| 768×1024 | 148 | Beside headline | 600 | 22px | none |
| 1440×900 | 0 (unchanged) | Desktop unchanged | 795 | 76px | none |

Desktop baseline measurements matched pre-fix: `visual 0–900`, `note top 795 / right 32`, `imgHeight 900`.

---

## 5. Test/build output

- No lint/test/build pipeline exists for the WordPress theme (no `package.json`, no bundler).
- **Console errors: 0** at all tested viewports.
- CTA button hit-tested at 375px — confirmed not covered by the raised portrait.
- Discovery panel open/close confirmed functional after the changes.

---

## 6. Remaining visual limitations

1. **Minor text overlap at 360px:** The face (124px wide) sits at page x 226–350, while the role line ("IT Support Specialist") extends to x~330 at y~313. The face's chin/neck slightly overlaps the role line's tail — the text renders on top and stays readable. This is inherent to the 1:3 head-to-gap ratio at 360px; further shrinking the face would require reducing the frame width (breaking the full-bleed aesthetic) or cropping the source image externally.

2. **Cloudflare caching on production:** CSS URL-based cache busting (`?ver=1.3.9`) works, but the HTML page itself may be cached by Cloudflare. Production needs a manual cache purge after deploy.

3. **`object-position: 0%` on desktop is not applied** (the rule is scoped to `max-width: 800px`). Desktop uses the base `object-position: 58% 18%` and is completely unchanged.

---

## Commits (CSS fix chain)

| Commit | Description |
|---|---|
| `f51b3bb` | Interlock portrait with headline, lift location note |
| `189efd7` | Crop dead top of portrait (top -18%, height 118%) |
| `24b0c70` | Place portrait head in right-hand gap (object-position 0%, left fade) |
| `6a6eb47` | Lift portrait further 30% (top -23.4%, height 123.4%) |
