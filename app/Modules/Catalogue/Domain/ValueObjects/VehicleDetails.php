<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\VehicleRefused;
use App\Modules\Catalogue\Domain\Policies\VehicleNameDomainPolicy;

/**
 * The plain fields of a vehicle besides its make, body and name (FR-07, RULE-09): model,
 * production years from-to (to may be open), generation and the four-digit ARGIC code.
 * Empty text becomes null.
 */
final readonly class VehicleDetails
{
    public const int MAX_TEXT_LENGTH = 255;

    public const int MIN_YEAR = 1000;

    public const int MAX_YEAR = 9999;

    private function __construct(
        public ?string $model,
        public ?int $yearFrom,
        public ?int $yearTo,
        public ?string $generation,
        public ?string $argicCode,
    ) {}

    public static function from(?string $model, ?int $yearFrom, ?int $yearTo, ?string $generation, ?string $argicCode): self
    {
        $argicCode = self::clean($argicCode);

        if ($argicCode !== null && preg_match('/^\d{4}$/', $argicCode) !== 1) {
            throw VehicleRefused::argicCodeIsInvalid();
        }

        foreach ([$yearFrom, $yearTo] as $year) {
            if ($year !== null && ($year < self::MIN_YEAR || $year > self::MAX_YEAR)) {
                throw VehicleRefused::yearIsInvalid();
            }
        }

        if (! VehicleNameDomainPolicy::yearsAreOrdered($yearFrom, $yearTo)) {
            throw VehicleRefused::yearsAreOutOfOrder();
        }

        return new self(
            self::text($model, VehicleRefused::modelIsTooLong(...)),
            $yearFrom,
            $yearTo,
            self::text($generation, VehicleRefused::generationIsTooLong(...)),
            $argicCode,
        );
    }

    /** @param  callable(int): VehicleRefused  $tooLong */
    private static function text(?string $value, callable $tooLong): ?string
    {
        $value = self::clean($value);

        if ($value !== null && mb_strlen($value) > self::MAX_TEXT_LENGTH) {
            throw $tooLong(self::MAX_TEXT_LENGTH);
        }

        return $value;
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
