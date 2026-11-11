<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\UpdateGlassModel;

use App\Core\Domain\Actor;

final readonly class UpdateGlassModelCommand
{
    /**
     * @param  list<int>  $equipmentIds
     * @param  list<string>  $oemCodes
     */
    public function __construct(
        public Actor $actor,
        public int $glassModelId,
        public ?int $positionId,
        public ?int $colourId = null,
        public array $equipmentIds = [],
        public array $oemCodes = [],
    ) {}
}
