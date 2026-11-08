{{--
    Topbar ornament (D-44): one upright "glass pane" of light falling from the navy sidebar onto the
    white topbar, at its left edge: primary-200 fading to 70 % from left to right, 16 px wide, ending
    in one straight vertical cut.
    Slanted cuts, a second softer glow pane and a faceted "geode" were tried here and dropped; the
    geode goes to the login screen (issue 09).
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
            aspect-ratio: 16 / 56;
            pointer-events: none;
        }

        .topbar-ornament__pane { fill: url(#topbar-ornament-glow); }
        .topbar-ornament__stop { stop-color: var(--primary-200); }
        .topbar-ornament__stop--end { stop-opacity: 0; }
    }
</style>

<svg class="topbar-ornament" viewBox="0 0 16 56" shape-rendering="crispEdges" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="topbar-ornament-glow" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="16" y2="0">
            <stop offset="0" class="topbar-ornament__stop" />
            <stop offset="1" class="topbar-ornament__stop topbar-ornament__stop--end" />
        </linearGradient>
    </defs>
    <rect class="topbar-ornament__pane" x="0" y="0" width="16" height="56" />
</svg>
