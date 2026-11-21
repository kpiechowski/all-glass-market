<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use Carbon\CarbonImmutable;

/**
 * FR-18, FR-19, RULE-09: a Sale of one Offer. It records the date, channel, the price it was sold for, and the
 * entered price, VAT rate and buyer's price at that moment; none of them changes afterwards. A return only marks the
 * Sale returned (with reason, who and when): it stays in history. An entity of its own, holding the Offer's id.
 */
final class Sale
{
    private function __construct(
        private ?int $id,
        private int $offerId,
        private SaleChannel $channel,
        private int $salePriceGrosze,
        private EnteredPrice $enteredPrice,
        private VatRate $vatRate,
        private int $buyersPriceGrosze,
        private int $markedById,
        private CarbonImmutable $soldAt,
        private ?CarbonImmutable $returnedAt,
        private ?string $returnReason,
        private ?int $returnedById,
    ) {}

    public static function record(
        int $offerId,
        SaleChannel $channel,
        int $salePriceGrosze,
        EnteredPrice $enteredPrice,
        VatRate $vatRate,
        int $buyersPriceGrosze,
        int $markedById,
        CarbonImmutable $soldAt,
    ): self {
        if ($salePriceGrosze <= 0) {
            throw OfferRefused::salePriceIsInvalid();
        }

        return new self(null, $offerId, $channel, $salePriceGrosze, $enteredPrice, $vatRate, $buyersPriceGrosze, $markedById, $soldAt, null, null, null);
    }

    /** @internal for the mapper */
    public static function reconstitute(
        int $id,
        int $offerId,
        SaleChannel $channel,
        int $salePriceGrosze,
        EnteredPrice $enteredPrice,
        VatRate $vatRate,
        int $buyersPriceGrosze,
        int $markedById,
        CarbonImmutable $soldAt,
        ?CarbonImmutable $returnedAt,
        ?string $returnReason,
        ?int $returnedById,
    ): self {
        return new self($id, $offerId, $channel, $salePriceGrosze, $enteredPrice, $vatRate, $buyersPriceGrosze, $markedById, $soldAt, $returnedAt, $returnReason, $returnedById);
    }

    /** FR-19: the Sale stays in history, marked returned; the handler checks that an admin does it. */
    public function markReturned(string $reason, int $returnedById, CarbonImmutable $now): void
    {
        if ($this->isReturned()) {
            throw OfferRefused::saleAlreadyReturned();
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw OfferRefused::returnReasonRequired();
        }

        $this->returnedAt = $now;
        $this->returnReason = $reason;
        $this->returnedById = $returnedById;
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function offerId(): int
    {
        return $this->offerId;
    }

    public function channel(): SaleChannel
    {
        return $this->channel;
    }

    public function salePriceGrosze(): int
    {
        return $this->salePriceGrosze;
    }

    public function enteredPrice(): EnteredPrice
    {
        return $this->enteredPrice;
    }

    public function vatRate(): VatRate
    {
        return $this->vatRate;
    }

    public function buyersPriceGrosze(): int
    {
        return $this->buyersPriceGrosze;
    }

    public function markedById(): int
    {
        return $this->markedById;
    }

    public function soldAt(): CarbonImmutable
    {
        return $this->soldAt;
    }

    public function returnedAt(): ?CarbonImmutable
    {
        return $this->returnedAt;
    }

    public function returnReason(): ?string
    {
        return $this->returnReason;
    }

    public function returnedById(): ?int
    {
        return $this->returnedById;
    }
}
