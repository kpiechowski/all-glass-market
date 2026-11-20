<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\Observers\OfferObserver;

class OffersEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [];

    protected $observers = [
        OfferModel::class => OfferObserver::class,
    ];
}
