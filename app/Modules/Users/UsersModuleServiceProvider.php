<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Users\Application\Providers\UsersEventServiceProvider;

class UsersModuleServiceProvider extends ModuleServiceProvider
{
    protected bool $loadsTranslations = true;

    /** @var array<class-string> */
    protected array $eventProviders = [
        UsersEventServiceProvider::class,
    ];
}
