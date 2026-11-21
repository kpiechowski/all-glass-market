<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\ModuleApi;

use App\Modules\Offers\Domain\Enums\OwnerAccountType;
use App\Modules\Offers\Domain\Policies\BuyersPricePolicy;
use App\Modules\Offers\Domain\Repositories\VatRateSetting;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use App\Modules\Users\Application\ModuleApi\AccountTypes;

/**
 * FR-28, RULE-08: what a buyer pays, always by the owner's current Account type and the current VAT
 * rate. A list calls `prime()` once with its owners and then asks per row without further reads.
 */
final class BuyersPrices
{
    /** @var array<int, string> Account type by owner id, as last primed */
    private array $primed = [];

    private ?VatRate $primedRate = null;

    public function __construct(
        private AccountTypes $accountTypes,
        private VatRateSetting $setting,
    ) {}

    /**
     * One Users read for many owners; replaces any earlier answer for them.
     *
     * @param  list<int>  $ownerIds
     */
    public function prime(array $ownerIds): void
    {
        $this->primed = array_replace($this->primed, $this->accountTypes->ofMany($ownerIds));
        $this->primedRate = $this->setting->current();
    }

    /** The VAT rate in basis points (2300 = 23%), for display. */
    public function vatRateBasisPoints(): int
    {
        return $this->setting->current()->basisPoints;
    }

    /** The buyer's price in grosze, or null while the Offer has no entered price or its owner is unknown. */
    public function forOffer(int $ownerId, ?int $enteredPriceGrosze): ?int
    {
        if ($enteredPriceGrosze === null) {
            return null;
        }

        $owner = OwnerAccountType::tryFrom($this->primed[$ownerId] ?? $this->accountTypes->of($ownerId) ?? '');

        return $owner === null
            ? null
            : BuyersPricePolicy::buyersPriceGrosze($enteredPriceGrosze, $owner, $this->primedRate ?? $this->setting->current());
    }
}
