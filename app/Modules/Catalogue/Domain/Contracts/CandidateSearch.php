<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

/**
 * The candidate search (FR-22, D-49), built once. The admin's Glass model list uses it now; the
 * module interface for Offers (ticket 08) wraps this same port.
 *
 * Results are ordered Eurocode hits, then OEM hits, then vehicle hits; a model matching by several
 * routes appears once, under its best route; withdrawn models only when asked for.
 */
interface CandidateSearch
{
    /** @return list<Candidate> at most `$query->limit`, best route first */
    public function search(CandidateQuery $query): array;

    /** @return list<int> the ids of every matching model (no limit), for filtering Offers' lists */
    public function matchingIds(CandidateQuery $query): array;
}
