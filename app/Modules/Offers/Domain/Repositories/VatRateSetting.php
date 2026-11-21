<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Repositories;

use App\Modules\Offers\Domain\ValueObjects\VatRate;

/** FR-30, ADR 0007: the VAT rate, a typed setting of Offers. */
interface VatRateSetting
{
    /** The rate a Super Admin set, or the initial one while none was set. */
    public function current(): VatRate;

    public function change(VatRate $rate): void;
}
