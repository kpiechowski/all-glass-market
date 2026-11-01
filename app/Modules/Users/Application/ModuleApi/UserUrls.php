<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

/** Where a User's detail page lives; the user interface says, so Application stays free of Filament. */
interface UserUrls
{
    public function show(int $userId): string;
}
