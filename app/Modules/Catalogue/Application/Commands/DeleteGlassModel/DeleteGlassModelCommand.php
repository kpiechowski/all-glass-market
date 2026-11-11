<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\DeleteGlassModel;

use App\Core\Domain\Actor;

final readonly class DeleteGlassModelCommand
{
    public function __construct(
        public Actor $actor,
        public int $glassModelId,
    ) {}
}
