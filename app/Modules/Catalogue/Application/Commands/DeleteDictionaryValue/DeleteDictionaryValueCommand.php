<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\DeleteDictionaryValue;

use App\Core\Domain\Actor;

final readonly class DeleteDictionaryValueCommand
{
    public function __construct(
        public Actor $actor,
        public int $valueId,
    ) {}
}
