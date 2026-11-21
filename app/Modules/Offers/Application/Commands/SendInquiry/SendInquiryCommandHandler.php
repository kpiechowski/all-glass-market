<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\SendInquiry;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Entities\Inquiry;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\InquiryRepository;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-21, FR-34: only an admin sends, only about an Active Offer; the sender is recorded and the owner is told. */
final class SendInquiryCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private InquiryRepository $inquiries,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(SendInquiryCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);
        $senderId = $command->actor->id ?? throw new AuthorizationException;

        $channel = $command->channel === null || $command->channel === ''
            ? null
            : (SaleChannel::tryFrom($command->channel) ?? throw OfferRefused::inquiryChannelIsUnknown());

        $offer = $this->offers->get($command->offerId);

        $this->inquiries->add(Inquiry::send(
            $command->offerId,
            $senderId,
            $channel,
            $command->note,
            CarbonImmutable::now(),
            OfferDomainPolicy::canReceiveInquiry($offer->status(), $offer->isInTrash()),
        ));

        $this->journal->inquirySent($command->actor, $offer);
    }
}
