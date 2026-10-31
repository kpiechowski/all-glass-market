<?php

declare(strict_types=1);

namespace App\Core\Domain\Events;

final readonly class RecordChanged
{
    /**
     * @param  class-string  $modelClass
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        public string $modelClass,
        public int $id,
        public array $changes,
    ) {}
}
