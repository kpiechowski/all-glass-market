<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\SendInquiry;

use App\Core\Domain\Actor;

/** FR-21: an admin asks the owner of an Active Offer about it, with an optional channel and a note. */
final readonly class SendInquiryCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
        public ?string $channel,
        public string $note,
    ) {}
}
