<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ApproveOffer;

use App\Core\Domain\Actor;

/** FR-09, FR-10: an admin approves; a Glass model chosen (or created) while approving is selected for the Offer. */
final readonly class ApproveOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
        public ?int $glassModelId = null,
    ) {}
}
