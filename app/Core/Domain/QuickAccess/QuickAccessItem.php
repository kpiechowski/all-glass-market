<?php

declare(strict_types=1);

namespace App\Core\Domain\QuickAccess;

use BackedEnum;
use Closure;
use InvalidArgumentException;

/**
 * One place in the panel a user can pin as a quick-access tile (issue 13) or find in the topbar
 * hub (issue 12). Only the key is ever stored; label, icon, URL and visibility are resolved from
 * code when the tile is drawn, so a URL never comes from user input and a moved page keeps its
 * saved tiles. A module builds its items for its own Filament pages with
 * `Core\UserInterface\Filament\QuickAccess\FilamentQuickAccessItem::for()`.
 */
final readonly class QuickAccessItem
{
    /**
     * @param  string  $key  stable, stored in users' preferences: `{module}.{place}`, lowercase, never rename it
     * @param  string  $group  key of a `QuickAccessGroup` registered before the item
     * @param  Closure(): string  $label
     * @param  Closure(): (string|BackedEnum|null)  $icon
     * @param  Closure(): string  $url
     * @param  Closure(): bool  $visible  whether the current user may open the place
     * @param  bool  $suggested  offered first by the future onboarding step; unused for now
     */
    public function __construct(
        public string $key,
        public string $group,
        private Closure $label,
        private Closure $icon,
        private Closure $url,
        private Closure $visible,
        public bool $suggested = false,
    ) {
        if (preg_match('/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', $key) !== 1) {
            throw new InvalidArgumentException("The quick-access key [{$key}] must read `module.place` in lowercase.");
        }
    }

    public function label(): string
    {
        return ($this->label)();
    }

    public function icon(): string|BackedEnum|null
    {
        return ($this->icon)();
    }

    public function url(): string
    {
        return ($this->url)();
    }

    public function isVisible(): bool
    {
        return ($this->visible)();
    }
}
