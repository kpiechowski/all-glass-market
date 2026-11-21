<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

/** FR-29: how a bulk price change reads its value. */
enum PriceChangeKind: string
{
    case Amount = 'amount';
    case Percent = 'percent';
    case Fixed = 'fixed';
}
