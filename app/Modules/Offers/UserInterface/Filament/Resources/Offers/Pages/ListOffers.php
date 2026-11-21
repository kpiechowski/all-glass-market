<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferListGlass;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferListTabs;
use App\Modules\Offers\UserInterface\Filament\Widgets\OfferActivityChart;
use App\Modules\Offers\UserInterface\Filament\Widgets\OfferStatCards;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Template A (list): one primary action; status tabs (FR-31, FR-32), an admin's "Oczekujące" counter (FR-11) and "Kosz" (FR-25); a seller's stat cards on top (FR-32). */
class ListOffers extends ListRecords
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('offers::resources.offer.actions.create')),
        ];
    }

    /** FR-32, FR-37: a seller's own counts and the activity chart sit above "Moje oferty"; an admin has the chart alone, above "Wszystkie oferty". */
    protected function getHeaderWidgets(): array
    {
        return CurrentActor::can(OffersPermission::ManageAll) ? [OfferActivityChart::class] : [OfferStatCards::class, OfferActivityChart::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /** FR-28, FR-31: one Users read for the Account types and one Catalogue read for the Glass models of the page, instead of one per row. */
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();

        app(BuyersPrices::class)->prime(collect($records)->pluck('owner_id')->unique()->values()->all());
        app(OfferListGlass::class)->prime($records);

        return $records;
    }

    /**
     * FR-31, FR-32: one tab per status, "all" first. Every tab but the Trash leaves out deleted Offers; the
     * Trash and the approval queue's counter are an admin's (FR-11, FR-25). A seller's list is already narrowed
     * to their own Offers by the resource query, so the same tabs never show another seller's Offer.
     */
    public function getTabs(): array
    {
        $managesAll = CurrentActor::can(OffersPermission::ManageAll);
        $tabs = [
            OfferListTabs::ALL => Tab::make(__('offers::resources.offer.tabs.all'))
                ->modifyQueryUsing(fn (Builder $query): Builder => app(OfferQueries::class)->outsideTrash($query)),
        ];

        foreach (OfferListTabs::statusTabs() as $key => $status) {
            $tabs[$key] = Tab::make(__('offers::resources.offer.tabs.'.$key))
                ->modifyQueryUsing(fn (Builder $query): Builder => app(OfferQueries::class)->onlyStatus($query, $status));
        }

        if (! $managesAll) {
            return $tabs;
        }

        $tabs['pending']->badge(fn (): int => app(OfferQueries::class)->pendingApproval()->count())->badgeColor('warning');
        $tabs[OfferListTabs::TRASH] = Tab::make(__('offers::resources.offer.tabs.trash'))
            ->modifyQueryUsing(fn (Builder $query): Builder => app(OfferQueries::class)->onlyTrash($query));

        return $tabs;
    }
}
