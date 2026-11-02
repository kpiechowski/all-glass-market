<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * FR-21, WF-02: a User's Account type was saved as changed. Other modules
 * (Offers, WooCommerce, Otomoto) reprice in reaction. `accountType` and `previousAccountType` are
 * 'private' or 'company'.
 */
final readonly class AccountTypeChanged
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $accountType,
        public string $previousAccountType,
        public CarbonImmutable $changedAt,
    ) {}
}
