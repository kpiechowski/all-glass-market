{{--
    Login geode (issue 09, D-44): one pair of faceted "glass" geodes in opposite page corners
    (top-left, and the same shape turned 180° bottom-right) behind the login card and the other
    simple auth pages. Registered with the SIMPLE_LAYOUT_START render hook in AdminPanelProvider;
    sized and placed by `resources/css/admin/pages/login.css`.

    Geometry: the accepted 120 x 56 topbar geode carried into a corner. There the two walls meet,
    so every layer is a pair of right triangles sharing the corner, one long along the top wall and
    short down the side wall, the other its mirror. Every vertex sits on a wall, the legs run into
    the walls and the hypotenuses of one family stay parallel. Where a pair overlaps, its
    translucent colour doubles and draws the facet. Painted outside in, legs halving the topbar
    ratio of 30 per step (viewBox 240):

        fade       240 / 120   sky-light 16 % to 0 along the diagonal
        sky-light  180 /  90   22 %
        royal      120 /  60   38 %
        royal       90 /  45   62 %   the extra step that softens the jump from navy (user's remark)
        navy        60 /  30   solid

    Decorative and hidden from assistive tech.
--}}
<svg class="login-geode-defs" width="0" height="0" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="login-geode-fade" gradientUnits="userSpaceOnUse" x1="60" y1="60" x2="120" y2="120">
            <stop offset="0" class="login-geode__fade-stop" />
            <stop offset="1" class="login-geode__fade-stop login-geode__fade-stop--end" />
        </linearGradient>

        <symbol id="login-geode" viewBox="0 0 240 240">
            <polygon class="login-geode__fade" points="0,0 240,0 0,120" />
            <polygon class="login-geode__fade" points="0,0 120,0 0,240" />
            <polygon class="login-geode__sky" points="0,0 180,0 0,90" />
            <polygon class="login-geode__sky" points="0,0 90,0 0,180" />
            <polygon class="login-geode__royal" points="0,0 120,0 0,60" />
            <polygon class="login-geode__royal" points="0,0 60,0 0,120" />
            <polygon class="login-geode__royal-deep" points="0,0 90,0 0,45" />
            <polygon class="login-geode__royal-deep" points="0,0 45,0 0,90" />
            <polygon class="login-geode__navy" points="0,0 60,0 0,30" />
            <polygon class="login-geode__navy" points="0,0 30,0 0,60" />
        </symbol>
    </defs>
</svg>

<svg class="login-geode login-geode--top-start" aria-hidden="true" focusable="false">
    <use href="#login-geode" />
</svg>

<svg class="login-geode login-geode--bottom-end" aria-hidden="true" focusable="false">
    <use href="#login-geode" />
</svg>
