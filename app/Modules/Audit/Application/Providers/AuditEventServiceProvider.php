<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;

class AuditEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        // Phase 2b: wire Offer events to listeners below once the Offers module exists.
        // OfferCreated::class => [OfferJournalListener::class, OfferPanelNotificationListener::class],
    ];

    protected $observers = [];
}
