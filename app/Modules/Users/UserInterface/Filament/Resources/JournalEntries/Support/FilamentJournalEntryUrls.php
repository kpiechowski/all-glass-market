<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Support;

use App\Modules\Users\Application\ModuleApi\JournalEntryUrls;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\JournalEntryResource;

final class FilamentJournalEntryUrls implements JournalEntryUrls
{
    public function show(int $entryId): string
    {
        return JournalEntryResource::getUrl('view', ['record' => $entryId], panel: 'admin');
    }
}
