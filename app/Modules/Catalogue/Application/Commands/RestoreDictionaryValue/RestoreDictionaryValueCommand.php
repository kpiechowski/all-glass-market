<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\RestoreDictionaryValue;

use App\Core\Domain\Actor;

final readonly class RestoreDictionaryValueCommand
{
    public function __construct(
        public Actor $actor,
        public int $valueId,
    ) {}
}
