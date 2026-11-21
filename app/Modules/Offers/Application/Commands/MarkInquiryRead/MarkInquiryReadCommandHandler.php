<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\MarkInquiryRead;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Repositories\InquiryRepository;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-22: a Sent Inquiry becomes Read when the owner opens it; anyone else opening it changes nothing. */
final class MarkInquiryReadCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private InquiryRepository $inquiries,
        private OfferRepository $offers,
    ) {}

    #[CommandHandler]
    public function handle(MarkInquiryReadCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $inquiry = $this->inquiries->get($command->inquiryId);

        if ($this->offers->get($inquiry->offerId())->ownerId() !== $userId) {
            throw OfferRefused::notYours();
        }

        $inquiry->markRead(CarbonImmutable::now());
        $this->inquiries->save($inquiry);
    }
}
