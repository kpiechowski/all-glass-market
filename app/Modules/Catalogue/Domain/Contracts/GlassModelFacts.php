<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

/** What the module interface reads of one Glass model: plain values, every name resolved. */
final readonly class GlassModelFacts
{
    /**
     * @param  list<string>  $eurocodes  main first
     * @param  list<string>  $oemCodes  as stored
     * @param  array<int, string>  $equipment  value names by value id, in name order
     * @param  list<string>  $vehicles  vehicle names, in name order
     */
    public function __construct(
        public int $id,
        public string $mainEurocode,
        public array $eurocodes,
        public array $oemCodes,
        public ?int $positionId,
        public ?string $position,
        public ?int $colourId,
        public ?string $colour,
        public array $equipment,
        public array $vehicles,
        public bool $withdrawn,
    ) {}
}
