<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Resources\ChangesLog\Pages;

use App\Modules\Audit\UserInterface\Filament\Resources\ChangesLog\ChangesLogResource;
use Filament\Resources\Pages\ListRecords;

class ListChangesLogs extends ListRecords
{
    protected static string $resource = ChangesLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
