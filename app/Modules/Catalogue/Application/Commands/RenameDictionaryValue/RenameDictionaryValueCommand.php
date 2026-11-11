<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\RenameDictionaryValue;

use App\Core\Domain\Actor;

final readonly class RenameDictionaryValueCommand
{
    public function __construct(
        public Actor $actor,
        public int $valueId,
        public string $name,
    ) {}
}
