<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ChangePriceOfOffers;

use App\Core\Domain\Actor;

/**
 * FR-29: changes the entered price of the selected Offers. `$kind` is a case value of `PriceChangeKind`; `$value` is
 * in grosze for `amount` (signed) and `fixed`, and in hundredths of a percent for `percent` (signed). The handler
 * returns `['done' => n, 'skipped' => n]`.
 *
 * @param  list<int>  $offerIds
 */
final readonly class ChangePriceOfOffersCommand
{
    public function __construct(
        public Actor $actor,
        public array $offerIds,
        public string $kind,
        public int $value,
    ) {}
}
