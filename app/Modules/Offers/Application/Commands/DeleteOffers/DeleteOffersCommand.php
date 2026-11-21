<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\DeleteOffers;

use App\Core\Domain\Actor;

/**
 * FR-25, RULE-14: bulk delete into the Trash (admin). Acts on each Offer as the single action does and skips
 * those that may not go there; the handler returns `['done' => n, 'skipped' => n]`.
 *
 * @param  list<int>  $offerIds
 */
final readonly class DeleteOffersCommand
{
    public function __construct(
        public Actor $actor,
        public array $offerIds,
    ) {}
}
