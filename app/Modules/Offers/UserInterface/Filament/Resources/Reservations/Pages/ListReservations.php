<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Reservations\Pages;

use App\Modules\Offers\UserInterface\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\ListRecords;

/** Template A (list): no header action, the page only reads. */
class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;
}
