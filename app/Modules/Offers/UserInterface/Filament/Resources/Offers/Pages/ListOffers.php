<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Template A (list): one primary action. An admin also gets the approval queue as the "Oczekujące" tab with its counter (FR-11) and the "Kosz" tab (FR-25); "Wszystkie" leaves the Trash out. */
class ListOffers extends ListRecords
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('offers::resources.offer.actions.create')),
        ];
    }

    /** FR-28: one Users read for the Account types of every row on the page, instead of one per row. */
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();

        app(BuyersPrices::class)->prime(collect($records)->pluck('owner_id')->unique()->values()->all());

        return $records;
    }

    /** FR-11: the queue is the Pending tab; a seller has no queue. */
    public function getTabs(): array
    {
        if (! CurrentActor::can(OffersPermission::ManageAll)) {
            return [];
        }

        return [
            'all' => Tab::make(__('offers::resources.offer.tabs.all'))
                ->modifyQueryUsing(fn (Builder $query): Builder => app(OfferQueries::class)->outsideTrash($query)),
            'pending' => Tab::make(__('offers::resources.offer.tabs.pending'))
                ->badge(fn (): int => app(OfferQueries::class)->pendingApproval()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => app(OfferQueries::class)->onlyPending($query)),
            'trash' => Tab::make(__('offers::resources.offer.tabs.trash'))
                ->modifyQueryUsing(fn (Builder $query): Builder => app(OfferQueries::class)->onlyTrash($query)),
        ];
    }
}
