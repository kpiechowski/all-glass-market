<?php

declare(strict_types=1);

namespace App\Filament\Navigation;

use App\Filament\Pages\Styleguide;
use App\Modules\Audit\UserInterface\Filament\Pages\HistoryAndLogs\HistoryAndLogs;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\DictionaryValueResource;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Resources\Resource as FilamentResource;

/**
 * D-25, D-25a: the sidebar is built here, not discovered. Three blocks: the top one holds only the
 * dashboard and is closed by a divider (`.sidebar-group--top`); the main one holds the work screens
 * right under it; the bottom one (profile, later settings) is pushed to the foot of the sidebar by
 * the theme (`.sidebar-group--bottom`). A new resource or page appears only once it is listed here.
 */
final class PanelNavigation
{
    public static function build(NavigationBuilder $builder): NavigationBuilder
    {
        $main = [
            UserResource::class,
            JournalEntryResource::class,
            HistoryAndLogs::class,
        ];

        /* D-33: the Styleguide route exists only locally, so its item may not be built elsewhere. */
        if (app()->isLocal()) {
            $main[] = Styleguide::class;
        }

        return $builder->groups([
            NavigationGroup::make()
                ->extraSidebarAttributes(['class' => 'sidebar-group--top'])
                ->items(self::items([
                    Dashboard::class,
                ])),
            NavigationGroup::make()
                ->items(self::items($main)),
            NavigationGroup::make(__('catalogue::resources.navigation.group'))
                ->items(self::items([
                    DictionaryValueResource::class,
                ])),
            NavigationGroup::make()
                ->extraSidebarAttributes(['class' => 'sidebar-group--bottom'])
                ->items(self::items([
                    EditProfile::class,
                ])),
        ]);
    }

    /**
     * Each page or resource builds its own item (label, icon, badge, active state). The builder
     * skips Filament's registration checks, so the item repeats them: hidden unless the class
     * registers navigation and the current user may open it.
     *
     * @param  list<class-string<Page|FilamentResource>>  $classes
     * @return list<NavigationItem>
     */
    private static function items(array $classes): array
    {
        return collect($classes)
            ->flatMap(fn (string $class): array => array_map(
                fn (NavigationItem $item): NavigationItem => $item->visible(
                    fn (): bool => $class::shouldRegisterNavigation() && $class::canAccess(),
                ),
                $class::getNavigationItems(),
            ))
            ->values()
            ->all();
    }
}
