<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\WithdrawOffersOfDeactivatedSeller;

use App\Core\Domain\Actor;

/** FR-35, WF-09: the System withdraws the Active, Pending and Draft Offers of a deactivated seller. */
final readonly class WithdrawOffersOfDeactivatedSellerCommand
{
    public function __construct(
        public Actor $actor,
        public int $ownerId,
    ) {}
}
