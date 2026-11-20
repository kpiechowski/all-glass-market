<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;

/**
 * RULE-02, FR-02: what an Offer says about its glass: the id of a Glass model, or the seller's
 * identification hint (any text), never both and never glass parameters. A Draft may hold neither (FR-06).
 */
final readonly class GlassReference
{
    public const int HINT_MAX_LENGTH = 255;

    private function __construct(
        public ?int $glassModelId,
        public ?string $identificationHint,
    ) {}

    public static function from(?int $glassModelId, ?string $identificationHint): self
    {
        $hint = $identificationHint === null ? '' : trim($identificationHint);
        $hint = $hint === '' ? null : $hint;

        if ($glassModelId !== null && $hint !== null) {
            throw OfferRefused::glassModelAndHintTogether();
        }

        if ($hint !== null && mb_strlen($hint) > self::HINT_MAX_LENGTH) {
            throw OfferRefused::hintIsTooLong(self::HINT_MAX_LENGTH);
        }

        return new self($glassModelId, $hint);
    }
}
