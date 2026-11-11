<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Policies;

/**
 * FR-08, RULE-09: the name proposed for a new vehicle, and the year order the form also checks.
 * Pure on values, so the entity and the form share one rule.
 */
final class VehicleNameDomainPolicy
{
    /**
     * `MAKE | MODEL | BODY | GENERATION FROM-TO | ARGIC code`: empty fields are left out and an
     * open end is written `FROM-`. Without a make there is nothing to propose.
     */
    public static function propose(
        ?string $make,
        ?string $model,
        ?string $body,
        ?string $generation,
        ?int $yearFrom,
        ?int $yearTo,
        ?string $argicCode,
    ): string {
        $make = trim((string) $make);

        if ($make === '') {
            return '';
        }

        $parts = array_filter([
            $make,
            trim((string) $model),
            trim((string) $body),
            self::generationAndYears($generation, $yearFrom, $yearTo),
            trim((string) $argicCode),
        ], fn (string $part): bool => $part !== '');

        return implode(' | ', $parts);
    }

    /** RULE-09: years from must not be after years to; an open end is fine. */
    public static function yearsAreOrdered(?int $yearFrom, ?int $yearTo): bool
    {
        return $yearFrom === null || $yearTo === null || $yearFrom <= $yearTo;
    }

    private static function generationAndYears(?string $generation, ?int $yearFrom, ?int $yearTo): string
    {
        $years = $yearFrom === null && $yearTo === null
            ? ''
            : ($yearFrom ?? '').'-'.($yearTo ?? '');

        return trim(trim((string) $generation).' '.$years);
    }
}
