<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/** Template A (list): one primary action. An admin also gets the approval queue as the "Oczekujące" tab with its counter (FR-11). */
class ListOffers extends ListRecords
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('offers::resources.offer.actions.create')),
        ];
    }

    /** FR-11: the queue is the Pending tab; a seller has no queue. */
    public function getTabs(): array
    {
        if (! CurrentActor::can(OffersPermission::ManageAll)) {
            return [];
        }

        return [
            'all' => Tab::make(__('offers::resources.offer.tabs.all')),
            'pending' => Tab::make(__('offers::resources.offer.tabs.pending'))
                ->badge(fn (): int => app(OfferQueries::class)->pendingApproval()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => app(OfferQueries::class)->onlyPending($query)),
        ];
    }
}
