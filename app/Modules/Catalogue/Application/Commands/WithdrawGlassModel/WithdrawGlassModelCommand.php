<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\WithdrawGlassModel;

use App\Core\Domain\Actor;

final readonly class WithdrawGlassModelCommand
{
    public function __construct(
        public Actor $actor,
        public int $glassModelId,
    ) {}
}
