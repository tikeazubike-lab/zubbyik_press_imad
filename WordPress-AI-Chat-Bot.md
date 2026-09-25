# WordPress AI Chat Bot — Design & UX Specification

## Context
A conversational/lead-capturing floating chatbot widget for a WordPress site, deployed as a single PHP file (`inc/ai-chat-bot.php`) that enqueues its own CSS/JS. No build tools, no npm, no React — vanilla JS + CSS only, compatible with shared WordPress hosting.

## Design Reference
The UI/UX should closely follow the EPM AiAssistant widget pattern already built in this project (`estate-portfolio-manager/src/components/AiAssistant.tsx`). That widget has three stages:

1. **Launcher** — a fixed-position floating button (bottom-right corner) with a subtle glow/pulse animation
2. **Input bar** — a pill-shaped text input with send button that appears when launcher is clicked
3. **Panel** — a full conversation panel with message history, suggestion chips, loading indicators, and a pinned input bar

## Design Requirements

### Layout & Positioning
- Fixed position: `bottom: 24px; right: 24px; z-index: 9999`
- Three-stage progressive disclosure: launcher button → compact input → full panel
- Panel width: `380px`, max-height: `500px`
- Responsive: on screens < 480px, panel goes full-width with `max-width: 100vw`

### Launcher Button
- Design attached [@chat-ai.png]
- Primary accent color with subtle animated glow/pulse
- Sparkle/chat icon (lucide `Sparkles` or equivalent inline SVG)
- Hover: scale up slightly, intensify glow
- CSS-only pulse animation (no JS interval)

### Input Bar
- Pill shape, `border-radius: 9999px`
- Background: semi-transparent surface color
- Border: primary accent at 25% opacity
- Shadow: subtle outer glow at 15% opacity
- Text input with placeholder: "Ask about your portfolio..." (or configurable per site)
- Send button: circular, filled with accent color, disabled when input empty
- Keyboard: Enter to send, Escape to close
- Width: `320px` in standalone mode, full width when embedded in panel

### Conversation Panel
- Rounded corners: `16px` (`border-radius: 16px`)
- Backdrop blur: `backdrop-filter: blur(12px)`
- Background: surface color at 95% opacity
- Border: subtle border color
- Shadow: `0 20px 60px -12px rgba(0,0,0,0.6)`
- Header: accent icon + "Assistant" title + close button
- Messages area: scrollable, auto-scroll to bottom on new message
- Suggestion chips: pill-shaped buttons above input (only before first message)
- Pinned input at bottom (same InputBar component, embedded variant)

### Messages
- User messages: right-aligned, accent background at 15% opacity
- Bot messages: left-aligned, plain surface
- Text size: `14px`
- Loading indicator: three animated dots (CSS `animate-pulse` with staggered `animation-delay`)
- Loading dots sit on the left (bot side) before response arrives

### Empty State
- When panel opens with no messages: show suggestion chips only
- Suggestion chips: rounded pills with `border`, muted text, hover → accent border

### Colors (CSS Custom Properties)
All colors must use CSS custom properties (not hardcoded hex/oklch) so the theme designer can override them:

```css
--accent: #8b5cf6              /* primary accent */
--accent-rgb: 139, 92, 246     /* for rgba() usage */
--bg-surface: #ffffff           /* light */
--bg-surface: #1a1b23           /* dark */
--text-primary: #1a1a1a / #e6edf3
--text-muted: #9ca3af
--border: #e5e7eb / #2d2d3d
Dark Mode
- Detect prefers-color-scheme: dark via CSS media query
- Support a .dark class on <html> for manual toggle
- Both default, no JS theme toggle needed in the widget itself
Transitions & Animation
- Stage transitions: animate-in slide-in-from-right-2 fade-in duration-200 equivalent
- Panel opens with slide-up + fade (200ms)
- Launcher pulse: CSS @keyframes pulse with 2s infinite
- Loading dots: three dots, each with 150ms delay stagger
Click-Outside Behavior
- Click-outside the widget while panel/input is open → collapse to launcher
- Preserves draft text and message history (no data loss on accidental close)
- Escape key also closes
Accessibility
- aria-label on all interactive elements
- Button states visible (focus ring, disabled opacity)
- Role attributes on live regions
Technical Constraints (WordPress Shared Hosting)
- Single PHP file: inc/ai-chat-bot.php
- Enqueue stylesheet and script using wp_enqueue_style / wp_enqueue_script
- No build step — vanilla CSS file + vanilla JS file (or inline in PHP)
- JS must handle: DOM manipulation, event listeners, message state management, API calls
- CSS must handle: all layout, themes (light/dark), animations, responsive
- No React, no npm, no bundler
- Backend communication: WordPress REST API endpoint or external API via fetch()
- Bot logic can be PHP-side (REST endpoint) or external API passthrough
Files to Create
inc/
  ai-chat-bot.php          ← PHP: class, shortcode, enqueue, REST endpoint
assets/
  css/
    ai-chat-bot.css        ← All styles (no build step)
  js/
    ai-chat-bot.js         ← All JavaScript (vanilla ES6, no framework)
Non-Goals (for this spec)
- Chat bot logic/lead capture itself — design and UX only
- Backend API implementation — the CSS/JS calls a configurable apiUrl
- Analytics, persistence, or CRM integration
