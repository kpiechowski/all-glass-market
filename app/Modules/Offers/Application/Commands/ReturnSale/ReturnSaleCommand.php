<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ReturnSale;

use App\Core\Domain\Actor;

/** FR-19: an admin returns the Offer's Sale with a required reason; the Offer is Active again. */
final readonly class ReturnSaleCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
        public string $reason,
    ) {}
}
