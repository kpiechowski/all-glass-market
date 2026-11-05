<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;
use App\Core\Domain\Events\RecordChanged;
use App\Modules\Audit\Application\Listeners\RecordChangeInHistory;

class AuditEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        RecordChanged::class => [RecordChangeInHistory::class],
    ];

    protected $observers = [];
}
