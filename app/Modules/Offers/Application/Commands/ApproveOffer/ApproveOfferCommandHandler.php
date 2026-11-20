<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ApproveOffer;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Application\Services\OfferContentAssembler;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-09, RULE-03: Pending or Rejected to Active, only with a Glass model; the owner gets a Journal entry. */
final class ApproveOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OfferContentAssembler $contents,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(ApproveOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);

        $offer = $this->offers->get($command->offerId);

        $offer->approve(
            CarbonImmutable::now(),
            $command->glassModelId,
            $command->glassModelId === null || $this->contents->glassModelIdCanBePicked($command->glassModelId),
        );
        $this->offers->save($offer);

        $this->journal->approved($command->actor, $offer);
    }
}
