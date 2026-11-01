<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * FR-21, WF-02: a User's Account type was saved as changed. Other modules
 * (Offers, WooCommerce, Otomoto) reprice in reaction. `accountType` is
 * 'private' or 'company'.
 */
final readonly class AccountTypeChanged
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $accountType,
    ) {}
}
