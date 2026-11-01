<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Support;

use App\Modules\Users\Application\ModuleApi\UserUrls;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;

final class FilamentUserUrls implements UserUrls
{
    public function show(int $userId): string
    {
        return UserResource::getUrl('view', ['record' => $userId], panel: 'admin');
    }
}
