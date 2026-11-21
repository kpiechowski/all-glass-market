<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ChangeVatRate;

use App\Core\Domain\Actor;

/** FR-30, D-54: a Super Admin sets the VAT rate, in basis points (2300 = 23%). */
final readonly class ChangeVatRateCommand
{
    public function __construct(
        public Actor $actor,
        public int $basisPoints,
    ) {}
}
