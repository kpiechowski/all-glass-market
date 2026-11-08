<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\QuickAccess;

use App\Core\Domain\QuickAccess\QuickAccessItem;
use Filament\Pages\Page;
use Filament\Resources\Resource as FilamentResource;

/**
 * Builds a quick-access item from one of our Filament pages or resources, so the tile shows what
 * the sidebar shows: the navigation label and icon, the page URL (a resource's index), and only
 * when the page's `canAccess()` lets the current user in. Everything is read lazily, at render.
 */
final class FilamentQuickAccessItem
{
    /**
     * @param  class-string<Page|FilamentResource>  $page
     * @param  string  $group  key of a registered `QuickAccessGroup`
     */
    public static function for(string $key, string $page, string $group, bool $suggested = false): QuickAccessItem
    {
        return new QuickAccessItem(
            key: $key,
            group: $group,
            label: fn (): string => $page::getNavigationLabel(),
            icon: fn () => $page::getNavigationIcon(),
            url: fn (): string => $page::getUrl(),
            visible: fn (): bool => $page::canAccess(),
            suggested: $suggested,
        );
    }
}
