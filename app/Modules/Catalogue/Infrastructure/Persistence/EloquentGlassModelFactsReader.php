<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Contracts\GlassModelFacts;
use App\Modules\Catalogue\Domain\Contracts\GlassModelFactsReader;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;

/** Six reads whatever the number of ids: the models and five eager loads. */
final class EloquentGlassModelFactsReader implements GlassModelFactsReader
{
    public function read(array $ids): array
    {
        return GlassModelModel::query()
            ->with(['eurocodes', 'oemCodes', 'position', 'colour', 'equipment', 'vehicles'])
            ->whereKey($ids)
            ->orderBy('id')
            ->get()
            ->map(fn (GlassModelModel $model): GlassModelFacts => $this->facts($model))
            ->all();
    }

    private function facts(GlassModelModel $model): GlassModelFacts
    {
        $eurocodes = $model->eurocodes->sortBy([['is_main', 'desc'], ['code', 'asc']])->values();

        return new GlassModelFacts(
            id: $model->id,
            mainEurocode: (string) $eurocodes->first()?->code,
            eurocodes: $eurocodes->pluck('code')->all(),
            oemCodes: $model->oemCodes->sortBy('code')->pluck('code')->values()->all(),
            positionId: $model->position?->id,
            position: $model->position?->name,
            colourId: $model->colour?->id,
            colour: $model->colour?->name,
            equipment: $model->equipment->sortBy('name')->mapWithKeys(fn ($value): array => [$value->id => $value->name])->all(),
            vehicles: $model->vehicles->sortBy('name')->pluck('name')->values()->all(),
            withdrawn: $model->withdrawn,
        );
    }
}
