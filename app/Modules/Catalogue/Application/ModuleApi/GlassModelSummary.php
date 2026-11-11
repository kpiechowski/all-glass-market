<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

/** FR-21: a Glass model as Offers shows it. Plain values only; every related thing is a name. */
final readonly class GlassModelSummary
{
    /**
     * @param  list<string>  $eurocodes  main first
     * @param  list<string>  $oemCodes
     * @param  list<string>  $equipment  names
     * @param  list<string>  $vehicles  names
     */
    public function __construct(
        public int $id,
        public string $mainEurocode,
        public array $eurocodes,
        public array $oemCodes,
        public ?string $position,
        public ?string $colour,
        public array $equipment,
        public array $vehicles,
        public bool $withdrawn,
        public Thumbnail $thumbnail,
    ) {}
}
