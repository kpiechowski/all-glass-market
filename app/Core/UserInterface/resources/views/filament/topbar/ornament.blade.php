{{--
    Topbar ornament (D-44): two upright "glass panes" of light falling from the navy sidebar onto
    the white topbar, at its left edge.
      1. primary-200 fading to 70 % from left to right, 16 px;
      2. the glow of the active page tab (tabs.css), primary-100 fading to nothing, 20 px.
    The first pane ends a shade darker than the second begins, so the seam reads as a pane edge.
    Slanted cuts and a faceted "geode" were tried here and dropped; the geode goes to the login
    screen (issue 09).
    Desktop only (lg+), where the sidebar runs full height; decorative, hidden from assistive tech.
    The style lives in this component so the ornament stays one self-contained file.
--}}
<style>
    .topbar-ornament {
        display: none;
    }

    @media (min-width: 64rem) {
        .fi-topbar:has(> .topbar-ornament) {
            position: relative;
        }

        .topbar-ornament {
            display: block;
            position: absolute;
            inset-block: 0;
            inset-inline-start: 0;
            width: auto;
            height: 100%;
            aspect-ratio: 36 / 56;
            pointer-events: none;
        }

        .topbar-ornament__pane--glow { fill: url(#topbar-ornament-glow); }
        .topbar-ornament__glow-stop { stop-color: var(--primary-100); }
        .topbar-ornament__glow-stop--end { stop-opacity: 0; }
        .topbar-ornament__pane--glow-strong { fill: url(#topbar-ornament-glow-strong); }
        .topbar-ornament__glow-stop--strong { stop-color: var(--primary-200); }
        .topbar-ornament__glow-stop--strong-end { stop-opacity: 0.7; }
    }
</style>

<svg class="topbar-ornament" viewBox="0 0 36 56" shape-rendering="crispEdges" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="topbar-ornament-glow" gradientUnits="userSpaceOnUse" x1="16" y1="0" x2="36" y2="0">
            <stop offset="0" class="topbar-ornament__glow-stop" />
            <stop offset="1" class="topbar-ornament__glow-stop topbar-ornament__glow-stop--end" />
        </linearGradient>
        <linearGradient id="topbar-ornament-glow-strong" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="16" y2="0">
            <stop offset="0" class="topbar-ornament__glow-stop topbar-ornament__glow-stop--strong" />
            <stop offset="1" class="topbar-ornament__glow-stop topbar-ornament__glow-stop--strong topbar-ornament__glow-stop--strong-end" />
        </linearGradient>
    </defs>
    <rect class="topbar-ornament__pane--glow" x="16" y="0" width="20" height="56" />
    <rect class="topbar-ornament__pane--glow-strong" x="0" y="0" width="16" height="56" />
</svg>
