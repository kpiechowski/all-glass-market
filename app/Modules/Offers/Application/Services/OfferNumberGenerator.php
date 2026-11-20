<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Services;

use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;
use RuntimeException;

/** RULE-13, D-29: draws "AG" and eight digits until one is free; the unique index backs the check. */
final class OfferNumberGenerator
{
    private const int ATTEMPTS = 20;

    public function __construct(private readonly OfferRepository $offers) {}

    public function next(): OfferNumber
    {
        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            $number = OfferNumber::fromDigits(random_int(1, 99_999_999));

            if (! $this->offers->numberIsTaken($number->value)) {
                return $number;
            }
        }

        throw new RuntimeException('No free offer number found.');
    }
}
