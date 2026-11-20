<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;

/** RULE-13, D-29: "AG" and eight digits; assigned once, never changed. */
final readonly class OfferNumber
{
    private function __construct(public string $value) {}

    public static function from(string $number): self
    {
        if (preg_match('/^AG\d{8}$/', $number) !== 1) {
            throw OfferRefused::numberIsInvalid();
        }

        return new self($number);
    }

    public static function fromDigits(int $digits): self
    {
        return self::from(sprintf('AG%08d', $digits));
    }
}
