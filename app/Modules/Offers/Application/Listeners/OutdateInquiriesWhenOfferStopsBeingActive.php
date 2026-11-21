<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Listeners;

use App\Modules\Offers\Application\Commands\OutdateInquiriesOfOffer\OutdateInquiriesOfOfferCommand;
use App\Modules\Offers\Domain\Events\OfferStoppedBeingActive;
use Ecotone\Modelling\CommandBus;

/**
 * FR-23: the one reaction to an Offer leaving Active, whatever the route. It runs synchronously, so the
 * Inquiries close in the transaction of the command that changed the Offer.
 */
final class OutdateInquiriesWhenOfferStopsBeingActive
{
    public function __construct(private CommandBus $commands) {}

    public function handle(OfferStoppedBeingActive $event): void
    {
        $this->commands->send(new OutdateInquiriesOfOfferCommand($event->actor, $event->offerId));
    }
}
