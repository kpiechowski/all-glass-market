<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\CreateDictionaryValue;

use App\Core\Domain\Actor;

final readonly class CreateDictionaryValueCommand
{
    public function __construct(
        public Actor $actor,
        public string $dictionary,
        public string $name,
    ) {}
}
