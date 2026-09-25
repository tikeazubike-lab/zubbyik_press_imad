REVIEW & CORRECTION PROMPT — Portfolio alignment pass (v2)

Context: the site has drifted from the agreed direction (modern, elegant, cinematic,
LIGHT-MODE-FIRST with dark support, GSAP ScrollTrigger throughout). Real content replaced
placeholder copy and broke proportions. Fix the defects below without redesigning the concept.

1) HERO — critical: the hero currently renders THREE stacked copies of the same
   headline + portrait, plus an orphaned chip row underneath. This is a pin/pinSpacing
   artifact from the pinned ScrollTrigger inside a section that also owns min-h-screen
   and the scrubbed portrait tween. Fix so exactly ONE hero composition is visible at
   all times:
   - Move the pinned trigger to a dedicated wrapper element, or drop `pin` and keep a
     pure scrub transform on the portrait.
   - Ensure only one <img> portrait node exists; no ghost/duplicate layers.
   - Re-check after fix at 320 / 375 / 768 / 1024 / 1440 that the hero occupies one
     viewport, and the skill marquee sits once at the bottom centre.

2) THEME — the entire page renders dark. Light mode must be the default: verify the
   inline theme script only adds `dark` when localStorage says so or the OS prefers dark,
   and that no section hardcodes ink/black backgrounds. Every colour must come from
   semantic tokens in src/styles.css (no bg-black / text-white literals).

3) TYPOGRAPHY & RHYTHM — headings read far too small versus section padding, so sections
   look empty at the top and cramped at the content. Re-anchor the scale:
   - Section eyebrow: text-xs/sm tracking-widest uppercase.
   - Section h2: clamp-based, text-3xl → md:text-5xl → lg:text-6xl, leading-tight.
   - Body: text-base md:text-lg, max-w-[62ch].
   - Section padding: py-20 md:py-28 lg:py-36 (currently py-32/py-48 is too tall for the
     content mass on mobile).

4) PROJECTS — the sticky project cards overlap each other and their text spills over the
   following section; one card shows a heading colliding with body copy. Either reduce to
   a normal responsive grid on <lg and keep the sticky/scale effect only on lg+, or give
   each sticky card its own scroll track with adequate spacing and overflow-hidden.
   Card text: min-w-0, break-words, clamp descriptions to 3 lines.

5) SKILLS — icons render as empty grey squares. Replace with real inline SVG icons
   (SVG Repo style, currentColor, aria-hidden) and give each card consistent padding,
   equal heights (grid auto-rows-fr) and a 2-col mobile / 4-col desktop layout.

6) EXPERIENCE — timeline entries run into the projects section and the milestone text is
   unbounded. Add clear section separation, constrain copy to max-w-2xl, and keep the
   scroll-grown line aligned with the dots at every breakpoint.

7) SERVICES / “Fixed-price help” BLOCK — the pricing cards are dense and unequal.
   Normalise to a 3-col (md) / 1-col (mobile) grid, equal heights, consistent CTA
   placement at the card bottom (mt-auto), and one visual accent card maximum.

8) GLOBAL HYGIENE
   - No horizontal scroll at any width; every flex child that holds text gets min-w-0,
     every icon gets shrink-0.
   - Images: explicit width/height, loading="lazy" below the fold.
   - Keep all GSAP behaviour, tune values only, and honour prefers-reduced-motion.
   - Single H1, alt text everywhere, focus-visible rings on all interactive elements.

Deliverable: one hero, light-first palette, consistent type scale and section rhythm,
no overlapping or clipped content at 320–1440px.

