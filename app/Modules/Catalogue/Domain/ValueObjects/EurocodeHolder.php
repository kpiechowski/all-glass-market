<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\ValueObjects;

/** The Glass model that already holds a Eurocode (RULE-01), as a refusal needs to name it. */
final readonly class EurocodeHolder
{
    public function __construct(
        public int $glassModelId,
        public string $mainEurocode,
    ) {}
}
