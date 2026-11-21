<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\RejectInquiry;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\InquirySenderJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Repositories\InquiryRepository;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-22, FR-34: the owner rejects an open Inquiry and its sender is told. */
final class RejectInquiryCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private InquiryRepository $inquiries,
        private OfferRepository $offers,
        private InquirySenderJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(RejectInquiryCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $inquiry = $this->inquiries->get($command->inquiryId);
        $offer = $this->offers->get($inquiry->offerId());

        $offer->ensureOwnedBy($userId);

        $inquiry->reject(CarbonImmutable::now());
        $this->inquiries->save($inquiry);

        $this->journal->rejected($command->actor, $inquiry, $offer);
    }
}
