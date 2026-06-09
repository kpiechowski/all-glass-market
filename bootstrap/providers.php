<?php

use App\Core\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    CoreServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
];
