<?php

use App\AppServiceProvider;
use App\Core\CoreServiceProvider;
use App\Filament\AdminPanelProvider;

return [
    CoreServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
];
