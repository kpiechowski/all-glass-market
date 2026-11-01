<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

/**
 * Where a Journal entry opens in the panel. Implemented by the panel's UI so
 * that the notification can link to it; not meant for other modules.
 */
interface JournalEntryUrls
{
    public function show(int $entryId): string;
}
