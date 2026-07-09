    Here are my findings after analyzing both screenshots and the live site, plus the source code.



    Issue Confirmed — Two Bleeding Problems

    Issue A: Project cards bleeding into each other
    Screenshot 1 shows consecutive project cards stacking with no visible gap between them. The "Live Demo" / "Source" buttons of the first card nearly touch the next card's content. Margins are visually collapsing or absent.

    Issue B: Experience section bleeding over Projects section
    Screenshot 2 clearly shows the Experience section's background/content covering the bottom of the last project card. The "Where I've been." text and timeline overlay the project card's laptop/WordPress illustration.



    Root Cause (Found in Code)

    File: malachy-portfolio/assets/js/animations/projects.js, lines 73-81

    javascript
    ScrollTrigger.create({
        trigger: card,
        start: 'top 4rem',
        end: isLast ? 'bottom top' : 'bottom top',
        pin: true,
        pinSpacing: false,   // <--- THIS IS THE BUG
        anticipatePin: 1,
    });


    Each .proj-card is pinned (pin: true) via GSAP ScrollTrigger as the user scrolls, but pinSpacing: false tells ScrollTrigger not to
add a spacer after the pinned element. This means:

    1. Consecutive cards: When card 1 pins, the page scrolls normally — card 2 slides up under card 1's pinned position instead of appearing after it. This causes the visual stacking/bleeding between project cards.

    2. Last card → Experience section: When the last project card pins, the Experience section scrolls under/over the pinned card instead of after it. This creates the visible background overlap where the Experience timeline bleeds over the project card's illustration.



    The Fix

    Two approaches:

    Option A (recommended) — Pin the entire #projects-stack container instead of individual cards, so all cards animate inside a single pinned block, then release cleanly to the Experience section. This is the proper "stacked card reveal" pattern.

    Option B (minimal) — Change pinSpacing: false to pinSpacing: true on the last card so ScrollTrigger adds a spacer that pushes the Experience section down. But this would also affect inter-card spacing and may leave unwanted gaps.

    Would you like me to implement Option A (rewrite the pinning to use the container as the pinned element)? That would fix
    both issues cleanly.
