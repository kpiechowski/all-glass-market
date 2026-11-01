<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Support;

use Illuminate\Support\Facades\Lang;

final class JournalKindLabel
{
    /** A kind with no translation shows as its own name. */
    public static function for(string $kind): string
    {
        $key = 'users::resources.journal_entry.kinds.'.$kind;

        return Lang::has($key) ? __($key) : $kind;
    }
}
