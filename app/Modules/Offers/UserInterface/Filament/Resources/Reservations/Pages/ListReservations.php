<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Reservations\Pages;

use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferListGlass;
use App\Modules\Offers\UserInterface\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

/** Template A (list): no header action, the page only reads. */
class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    /** FR-15: one Catalogue read for the Glass models of the page, instead of one per row. */
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();

        app(OfferListGlass::class)->prime(collect($records)->pluck('offer')->filter());

        return $records;
    }
}
