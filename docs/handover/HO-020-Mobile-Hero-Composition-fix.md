## MOBILE HERO COMPOSITION FIX — PRECISE RESPONSIVE ADJUSTMENT

Review the current mobile Hero section against the attached screenshot/reference before making any changes.

### OBJECTIVE

The mobile Hero currently feels vertically disconnected: the text composition sits considerably higher than the portrait, while the portrait begins too far down the page. The intended composition is that the portrait and typography feel like **two complementary elements in a close embrace — almost a yin-yang relationship**.

Do NOT redesign the Hero. Do NOT change the visual identity, typography, colours, copy, navigation, buttons, or desktop composition unless the existing responsive implementation requires a minimal shared change.

The task is specifically to correct the **mobile spatial composition and vertical alignment**.

### 1. PORTRAIT — MOVE THE VISUAL MASS UP

The man's portrait currently begins too low vertically.

On mobile, reposition the portrait upward so that its upper visual mass/head occupies the previously unused space on the **upper-right side of the Hero**, immediately below/around the navigation area.

The intended visual effect is:

* The text occupies the left/central portion.
* The portrait rises behind/alongside the text composition.
* The man's head should visually **peek through the upper-right negative space beneath the menu/header area**.
* The portrait and typography should feel intentionally interlocked rather than like two independent stacked sections.
* Preserve the existing image scale, cropping character, transparency/blending treatment, and visual style unless a small adjustment is required to achieve the composition.
* Do not simply translate the entire image upward by an arbitrary number of pixels. Determine which positioning/container/anchor is currently causing the excessive vertical offset and correct the underlying responsive layout.

Think in terms of **composition first, CSS values second**.

### 2. TEXT + PORTRAIT RELATIONSHIP

The final mobile composition should communicate:

```text
┌─────────────────────────────┐
│ ME.                 MENU    │
│                             │
│ Portfolio · 2026            │
│                             │
│ Hi, I'm          [HEAD]     │
│ Malachy.        [PORTRAIT]  │
│                  [PORTRAIT] │
│ QA Engineer...   [PORTRAIT] │
│                             │
│ description...              │
│                             │
│ [buttons / CTA area]        │
│                             │
│     Based in Manchester     │
│     Working everywhere      │
└─────────────────────────────┘
```

This is only a conceptual representation. Do NOT reproduce this literally.

The important principle is that the portrait should **participate in the hero composition from much earlier vertically**, rather than appearing as a separate image pushed toward the bottom.

### 3. "BASED IN MANCHESTER / WORKING EVERYWHERE"

The current:

**Based in Manchester**
**Working everywhere**

content is positioned much too far down on mobile.

On mobile specifically:

* Bring this content substantially upward.
* Centre it horizontally within the available Hero composition.
* It should feel like a deliberate **closing/location statement for the Hero**, not content that has been accidentally pushed below the main composition.
* Ensure it remains visually balanced relative to the portrait, headline, description and CTA buttons.
* Do not use excessive fixed margins or arbitrary absolute positioning merely to make the screenshot look correct at one viewport width.

### 4. RESPONSIVE BEHAVIOUR

This is a responsive-layout correction, not a screenshot-specific hack.

Before changing anything:

1. Inspect the Hero component and all related CSS/Tailwind classes.
2. Identify the actual layout mechanism controlling:

   * portrait positioning
   * Hero content vertical spacing
   * mobile height/min-height
   * location text positioning
   * CTA positioning
   * image/object positioning
3. Determine whether the current problem comes from:

   * flex/grid alignment
   * excessive margins/padding
   * absolute positioning
   * image container height
   * object-position
   * transform/translate
   * breakpoint-specific styles
   * viewport-height calculations
   * stacking order/z-index
4. Correct the underlying responsive rules.

Do not introduce magic numbers without understanding what they control.

### 5. DESKTOP MUST NOT REGRESS

The screenshot demonstrates a **mobile-specific problem**.

Preserve the existing desktop/tablet composition unless inspection shows that the same underlying rule is responsible.

After implementation, test at multiple viewport sizes, at minimum:

* 375 × 812
* 390 × 844
* 412 × 915
* 768 × 1024
* desktop width

The composition should remain intentional across these sizes rather than being tuned exclusively for one screenshot.

### 6. VISUAL ACCEPTANCE CRITERIA

The mobile Hero is acceptable only when:

* The portrait is visibly higher than it is currently.
* The man's head occupies the upper-right negative space beneath/around the navigation area.
* The portrait and headline feel visually connected rather than vertically separated.
* The Hero reads as one integrated composition / "yin-yang" embrace.
* The portrait does not obscure important text, navigation or CTAs.
* "Based in Manchester / Working everywhere" is substantially higher than its current position and horizontally centred on mobile.
* No unnecessary horizontal overflow is introduced.
* No clipping of important content occurs.
* CTA buttons remain usable and visually balanced.
* Desktop layout remains unchanged or visually equivalent.
* No unrelated components, copy, typography, colours or animations are modified.

### 7. IMPLEMENTATION DISCIPLINE

Do not start by blindly changing CSS values.

First inspect the existing Hero implementation and explain briefly:

* what currently controls the portrait position;
* what currently controls the location text position;
* why the current mobile composition produces the vertical separation.

Then make the smallest structural/responsive correction necessary.

After implementation, run the project's existing lint/typecheck/test/build commands where applicable.

Report:

1. Files changed
2. Root cause identified
3. Changes made
4. Responsive viewport checks performed
5. Test/build output
6. Any remaining visual limitation

Do not modify unrelated parts of the website.

