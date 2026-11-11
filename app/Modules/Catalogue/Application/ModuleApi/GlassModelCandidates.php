<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Domain\Contracts\Candidate;
use App\Modules\Catalogue\Domain\Contracts\CandidateQuery;
use App\Modules\Catalogue\Domain\Contracts\CandidateSearch;

/**
 * FR-22, D-49: the candidate search for Offers, the same behaviour as the admin list. Results are
 * ordered Eurocode, OEM, vehicle; a model appears once under its best route; withdrawn models only
 * when asked for.
 */
final class GlassModelCandidates
{
    public function __construct(private readonly CandidateSearch $search) {}

    /** @return list<Candidate> at most 20, each with the route and the value it matched */
    public function search(
        string $text,
        ?int $positionId = null,
        ?int $colourId = null,
        ?int $makeId = null,
        bool $includeWithdrawn = false,
    ): array {
        return $this->search->search(new CandidateQuery($text, $positionId, $colourId, $makeId, $includeWithdrawn));
    }

    /** @return list<int> every matching model id, no limit, for filtering Offers' lists */
    public function matchingIds(
        string $text,
        ?int $positionId = null,
        ?int $colourId = null,
        ?int $makeId = null,
        bool $includeWithdrawn = false,
    ): array {
        return $this->search->matchingIds(new CandidateQuery($text, $positionId, $colourId, $makeId, $includeWithdrawn));
    }
}
