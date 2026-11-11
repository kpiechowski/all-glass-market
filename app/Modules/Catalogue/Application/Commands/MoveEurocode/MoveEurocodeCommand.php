<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\MoveEurocode;

use App\Core\Domain\Actor;

final readonly class MoveEurocodeCommand
{
    public function __construct(
        public Actor $actor,
        public int $glassModelId,
        public string $eurocode,
        public int $targetGlassModelId,
    ) {}
}
