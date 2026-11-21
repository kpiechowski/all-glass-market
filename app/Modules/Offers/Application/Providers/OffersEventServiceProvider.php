<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;
use App\Modules\Offers\Application\Listeners\AnnounceBuyersPricesOnAccountTypeChange;
use App\Modules\Offers\Application\Listeners\OutdateInquiriesWhenOfferStopsBeingActive;
use App\Modules\Offers\Domain\Events\OfferStoppedBeingActive;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;
use App\Modules\Offers\Infrastructure\Persistence\Observers\InquiryObserver;
use App\Modules\Offers\Infrastructure\Persistence\Observers\OfferObserver;
use App\Modules\Offers\Infrastructure\Persistence\Observers\ReservationObserver;
use App\Modules\Offers\Infrastructure\Persistence\Observers\SaleObserver;
use App\Modules\Users\Domain\Events\AccountTypeChanged;

class OffersEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        AccountTypeChanged::class => [AnnounceBuyersPricesOnAccountTypeChange::class],
        OfferStoppedBeingActive::class => [OutdateInquiriesWhenOfferStopsBeingActive::class],
    ];

    protected $observers = [
        OfferModel::class => OfferObserver::class,
        ReservationModel::class => ReservationObserver::class,
        SaleModel::class => SaleObserver::class,
        InquiryModel::class => InquiryObserver::class,
    ];
}
