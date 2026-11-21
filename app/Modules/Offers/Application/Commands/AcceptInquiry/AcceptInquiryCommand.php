<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\AcceptInquiry;

use App\Core\Domain\Actor;

/** FR-22: the owner accepts an Inquiry; the Offer is reserved in the sending admin's name. */
final readonly class AcceptInquiryCommand
{
    public function __construct(
        public Actor $actor,
        public int $inquiryId,
    ) {}
}
