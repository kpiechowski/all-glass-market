<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Resources\UserJournal\Pages;

use App\Modules\Audit\UserInterface\Filament\Resources\UserJournal\UserJournalResource;
use Filament\Resources\Pages\ListRecords;

class ListUserJournals extends ListRecords
{
    protected static string $resource = UserJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
