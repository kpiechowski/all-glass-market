<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;

class AuditEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
    ];

    protected $observers = [];
}
