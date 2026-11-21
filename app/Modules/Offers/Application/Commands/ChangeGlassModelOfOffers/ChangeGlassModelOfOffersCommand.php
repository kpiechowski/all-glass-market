<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ChangeGlassModelOfOffers;

use App\Core\Domain\Actor;

/**
 * FR-14: an admin changes the Glass model of the selected Offers (one id for the single action). The handler returns
 * `['done' => n, 'skipped' => n]`.
 *
 * @param  list<int>  $offerIds
 */
final readonly class ChangeGlassModelOfOffersCommand
{
    public function __construct(
        public Actor $actor,
        public array $offerIds,
        public int $glassModelId,
    ) {}
}
