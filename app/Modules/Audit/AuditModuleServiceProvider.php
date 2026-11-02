<?php

declare(strict_types=1);

namespace App\Modules\Audit;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Audit\Application\Providers\AuditEventServiceProvider;

class AuditModuleServiceProvider extends ModuleServiceProvider
{
    protected bool $loadsTranslations = true;

    /** @var array<class-string> */
    protected array $eventProviders = [
        AuditEventServiceProvider::class,
    ];
}
