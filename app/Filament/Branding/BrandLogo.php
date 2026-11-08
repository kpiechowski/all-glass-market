<?php

declare(strict_types=1);

namespace App\Filament\Branding;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * D-40: both logo versions, as one Htmlable inside Filament's `.fi-logo` box (which carries the
 * height). The theme shows the dark one on white (login, topbar) and the client's white one on the
 * navy sidebar (`components/brand-logo.css`, `filament/layout/sidebar-navy.css`).
 */
final class BrandLogo
{
    public static function html(): Htmlable
    {
        $alt = e(__('filament-panels::layout.logo.alt', ['name' => filament()->getBrandName()]));

        return new HtmlString(sprintf(
            '<img class="brand-logo brand-logo--on-light" src="%s" alt="%s"><img class="brand-logo brand-logo--on-dark" src="%s" alt="%s">',
            e(asset('images/brand/allglass-logotype.svg')),
            $alt,
            e(asset('images/brand/allglass-logotype-white.svg')),
            $alt,
        ));
    }
}
