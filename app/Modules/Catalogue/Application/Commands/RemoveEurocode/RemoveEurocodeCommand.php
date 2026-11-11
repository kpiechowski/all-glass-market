<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\RemoveEurocode;

use App\Core\Domain\Actor;

final readonly class RemoveEurocodeCommand
{
    public function __construct(
        public Actor $actor,
        public int $glassModelId,
        public string $eurocode,
    ) {}
}
