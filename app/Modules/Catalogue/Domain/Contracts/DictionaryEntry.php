<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

/** One dictionary value as the module interface hands it out. */
final readonly class DictionaryEntry
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $withdrawn,
    ) {}
}
