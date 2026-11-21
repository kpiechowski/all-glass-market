<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ReturnSale;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\SaleRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-19, FR-34, RULE-10: only an admin returns a Sale; the Offer is Active again and the Sale stays, marked returned. */
final class ReturnSaleCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private SaleRepository $sales,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(ReturnSaleCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);
        $adminId = $command->actor->id ?? throw new AuthorizationException;

        $offer = $this->offers->get($command->offerId);
        $offer->returnSale();

        $sale = $this->sales->findCurrentForOffer($command->offerId) ?? throw OfferRefused::saleCannotBeReturned();
        $sale->markReturned($command->reason, $adminId, CarbonImmutable::now());

        $this->offers->save($offer);
        $this->sales->save($sale);

        $this->journal->returned($command->actor, $offer);
    }
}
