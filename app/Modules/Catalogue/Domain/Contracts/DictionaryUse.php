<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

/** One place a dictionary value is used, for the refusal to delete it (FR-24). */
final readonly class DictionaryUse
{
    /**
     * @param  string  $place  translation key naming the place, for example `catalogue::uses.glass_models`
     * @param  int  $count  how many records there use the value; at least 1
     */
    public function __construct(
        public string $place,
        public int $count,
    ) {}
}
