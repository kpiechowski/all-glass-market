<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\ChangeMainEurocode;

use App\Core\Domain\Actor;

final readonly class ChangeMainEurocodeCommand
{
    public function __construct(
        public Actor $actor,
        public int $glassModelId,
        public string $eurocode,
    ) {}
}
