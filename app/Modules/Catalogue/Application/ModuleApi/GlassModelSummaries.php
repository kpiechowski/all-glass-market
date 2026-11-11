<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Domain\Contracts\GlassModelFacts;
use App\Modules\Catalogue\Domain\Contracts\GlassModelFactsReader;

/**
 * FR-21, D-48: the Glass model summary for Offers' lists, view, approval and publishing.
 *
 * Events for listeners (D-51), all in `Catalogue\Domain\Events`, each carrying only an id and
 * `occurredAt`; whoever reacts reads the summary or the dictionary name:
 * - `GlassModelChanged`: Eurocodes, main Eurocode, OEM codes, position, colour, equipment or
 *   fitment changed, also when a vehicle or a position, colour or equipment value it uses changed.
 * - `GlassModelWithdrawn` and `GlassModelRestored`.
 * - `ProducerChanged` and `ProducerWithdrawn` (producer id).
 * Creating a Glass model and deleting an unused entry announce nothing.
 */
final class GlassModelSummaries
{
    public function __construct(
        private readonly GlassModelFactsReader $facts,
        private readonly GlassModelThumbnails $thumbnails,
    ) {}

    public function of(int $glassModelId): ?GlassModelSummary
    {
        return $this->ofMany([$glassModelId])[$glassModelId] ?? null;
    }

    /**
     * @param  list<int>  $glassModelIds
     * @return array<int, GlassModelSummary> by id; ids that match no model are left out
     */
    public function ofMany(array $glassModelIds): array
    {
        $summaries = [];

        foreach ($this->facts->read(array_values(array_unique($glassModelIds))) as $facts) {
            $summaries[$facts->id] = $this->summary($facts);
        }

        return $summaries;
    }

    private function summary(GlassModelFacts $facts): GlassModelSummary
    {
        return new GlassModelSummary(
            id: $facts->id,
            mainEurocode: $facts->mainEurocode,
            eurocodes: $facts->eurocodes,
            oemCodes: $facts->oemCodes,
            position: $facts->position,
            colour: $facts->colour,
            equipment: array_values($facts->equipment),
            vehicles: $facts->vehicles,
            withdrawn: $facts->withdrawn,
            thumbnail: $this->thumbnails->for($facts->positionId, $facts->colourId, $facts->equipment),
        );
    }
}
