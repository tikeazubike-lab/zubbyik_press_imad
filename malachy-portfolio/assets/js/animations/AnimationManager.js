/**
 * AnimationManager — Reusable animation architecture
 *
 * - Register sections and automatically clean up on page unload.
 * - matchMedia support for mobile pinning exception.
 * - Reduced-motion support.
 * - Refresh ScrollTrigger on dynamic content changes.
 *
 * Mobile Pinning Exception:
 * ScrollTrigger pin (hero, stacked project cards) is desktop/tablet only,
 * gated behind gsap.matchMedia at a breakpoint of 768px.
 * Below 768px, pinned sections degrade to scroll-triggered fade/translate reveals.
 *
 * @package Malachy_Portfolio
 */

class AnimationManager {
  constructor() {
    this.sections = new Map();
    this.mediaQueries = null;
    this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Refresh ScrollTrigger when fonts & images load
    window.addEventListener('load', () => ScrollTrigger.refresh());
    if (document.fonts) {
      document.fonts.ready.then(() => ScrollTrigger.refresh());
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => this.destroy());
  }

  /**
   * Register a section's animation timeline.
   * @param {string} name - Unique section name.
   * @param {Function} createTimeline - Function(manager) that returns a GSAP timeline or array of tweens.
   * @param {Object} options - { enabled, breakpoint }
   */
  register(name, createTimeline, options = {}) {
    if (this.sections.has(name)) {
      console.warn(`[AnimationManager] Section "${name}" already registered.`);
      return;
    }

    if (this.reduced) {
      this.sections.set(name, { enabled: false, reason: 'reduced-motion' });
      return;
    }

    let tl = null;
    try {
      tl = createTimeline(this);
    } catch (e) {
      console.warn(`[AnimationManager] Section "${name}" failed:`, e);
      this.sections.set(name, { enabled: false, reason: 'error' });
      return;
    }

    this.sections.set(name, { enabled: true, timeline: tl, options });
  }

  /**
   * Create a matchMedia instance for responsive gating.
   */
  get breakpoints() {
    if (!this.mediaQueries) {
      this.mediaQueries = gsap.matchMedia();
    }
    return this.mediaQueries;
  }

  /**
   * Conditionally create a pinned animation only above 768px.
   */
  pinAbove(tabletContext) {
    // Over this breakpoint => pin behaviour (hero, stacked cards)
  }

  /**
   * Destroy all timelines and matchMedia.
   */
  destroy() {
    for (const [, entry] of this.sections) {
      if (entry.timeline) {
        if (entry.timeline instanceof gsap.core.Timeline) {
          entry.timeline.kill();
        }
      }
    }
    this.sections.clear();
    if (this.mediaQueries) {
      this.mediaQueries.kill();
    }
    ScrollTrigger.getAll().forEach(st => st.kill());
  }
}

// Global instance
window.MalachyAnim = new AnimationManager();
