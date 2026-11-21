<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Events\GlassModelRestored;
use App\Modules\Catalogue\Domain\Events\GlassModelWithdrawn;
use App\Modules\Catalogue\Domain\Events\ProducerChanged;
use App\Modules\Catalogue\Domain\Events\ProducerWithdrawn;
use App\Modules\Offers\Application\Listeners\AnnounceBuyersPricesOnAccountTypeChange;
use App\Modules\Offers\Application\Listeners\AnnounceCandidatesWhenBuyersPriceChanges;
use App\Modules\Offers\Application\Listeners\AnnounceCandidatesWhenGlassModelChanges;
use App\Modules\Offers\Application\Listeners\AnnounceCandidatesWhenProducerChanges;
use App\Modules\Offers\Application\Listeners\OutdateInquiriesWhenOfferStopsBeingActive;
use App\Modules\Offers\Application\Listeners\WithdrawOffersWhenSellerIsDeactivated;
use App\Modules\Offers\Domain\Events\OfferBuyersPriceChanged;
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
use App\Modules\Users\Domain\Events\UserDeactivated;

class OffersEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        AccountTypeChanged::class => [AnnounceBuyersPricesOnAccountTypeChange::class],
        OfferBuyersPriceChanged::class => [AnnounceCandidatesWhenBuyersPriceChanges::class],
        GlassModelChanged::class => [AnnounceCandidatesWhenGlassModelChanges::class],
        GlassModelWithdrawn::class => [AnnounceCandidatesWhenGlassModelChanges::class],
        GlassModelRestored::class => [AnnounceCandidatesWhenGlassModelChanges::class],
        ProducerChanged::class => [AnnounceCandidatesWhenProducerChanges::class],
        ProducerWithdrawn::class => [AnnounceCandidatesWhenProducerChanges::class],
        UserDeactivated::class => [WithdrawOffersWhenSellerIsDeactivated::class],
        OfferStoppedBeingActive::class => [OutdateInquiriesWhenOfferStopsBeingActive::class],
    ];

    protected $observers = [
        OfferModel::class => OfferObserver::class,
        ReservationModel::class => ReservationObserver::class,
        SaleModel::class => SaleObserver::class,
        InquiryModel::class => InquiryObserver::class,
    ];
}
