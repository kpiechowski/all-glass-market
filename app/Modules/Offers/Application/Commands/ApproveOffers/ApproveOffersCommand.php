<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ApproveOffers;

use App\Core\Domain\Actor;

/**
 * FR-11, RULE-14: bulk approve. Acts on each Offer as the single action does and skips those it may
 * not approve; the handler returns `['approved' => n, 'skipped' => n]`.
 *
 * @param  list<int>  $offerIds
 */
final readonly class ApproveOffersCommand
{
    public function __construct(
        public Actor $actor,
        public array $offerIds,
    ) {}
}
