<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\SendOfferForApproval;

use App\Core\Domain\Actor;

/** FR-06, FR-13, WF-01: the owner sends a Draft or Rejected Offer on. */
final readonly class SendOfferForApprovalCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
    ) {}
}
