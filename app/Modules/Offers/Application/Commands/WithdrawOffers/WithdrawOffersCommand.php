<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\WithdrawOffers;

use App\Core\Domain\Actor;

/**
 * FR-24, RULE-14: bulk withdraw. Acts on each Offer as the single action does and skips those the actor may
 * not withdraw; the handler returns `['done' => n, 'skipped' => n]`.
 *
 * @param  list<int>  $offerIds
 */
final readonly class WithdrawOffersCommand
{
    public function __construct(
        public Actor $actor,
        public array $offerIds,
    ) {}
}
