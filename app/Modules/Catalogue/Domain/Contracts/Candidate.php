<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

use App\Modules\Catalogue\Domain\Enums\MatchRoute;

/** One result of the candidate search: the model, the best route it was found by and the value that matched (the Eurocode, OEM code as stored, or vehicle name). */
final readonly class Candidate
{
    public function __construct(
        public int $glassModelId,
        public MatchRoute $route,
        public string $matched,
    ) {}
}
